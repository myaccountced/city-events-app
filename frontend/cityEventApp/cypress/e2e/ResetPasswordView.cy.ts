describe('Password Recovery', () => {
    it('should send a password reset email', () => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')

        cy.visit("http://localhost:5173/signin");

        cy.contains(".forgotPassword", "Forgot Password").click();

        // Navigate to the password reset page
        cy.origin('http://127.0.0.1:8001', () => {
            cy.url().should('include', 'http://127.0.0.1:8001/reset-password');

            // Type in the user's email
            cy.get("#reset_password_request_form_email").type("zueventsproject@gmail.com");

            // Click the submit button
            cy.get("button").click();
            // Ensure the user is redirected to check-email page
            cy.url().should('include', 'http://127.0.0.1:8001/reset-password/check-email');
            cy.contains('p', 'If an account matching your email exists, then an email was just sent that contains a link that you can use to reset your password.');
        });

        //Add an explicit wait to ensure the email submission is completed before checking MailHog
        cy.wait(2000); // Give some time for the backend to process the request

        // Wait for MailHog to receive the email
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    if (!emails.length) return false;

                    const email = emails.find(e =>
                        e.Content.Headers.Subject.some(subj => subj.includes('Your password reset request'))
                    );

                    if (!email) return false;

                    // Extract the email body
                    return cy.wrap(email.Content.Body).then((body) => {
                        // Remove soft line breaks (`=\n` or `=\r\n` in some cases)
                        const cleanedBody = body.replace(/=\r?\n/g, '');

                        // Extract the full reset link
                        const urlMatch = cleanedBody.match(/http:\/\/127\.0\.0\.1:8001\/reset-password\/reset\/[\w-]+/);

                        if (!urlMatch) {
                            console.error("❌ Reset link not found in email.");
                            return false;
                        }

                        const fullLink = urlMatch[0];

                        // Store and visit the reset link
                        cy.wrap(fullLink).as('resetLink');
                        return cy.visit(fullLink);
                    });
                });
        }, { timeout: 20000, interval: 2000 });

        cy.origin('http://127.0.0.1:8001', () => {
            cy.url().should('include', '/reset-password/reset');
            cy.contains('h1', 'Reset your password');

            // Enter mismatched passwords
            cy.get('#change_password_form_plainPassword_first').type("@Password1234");
            cy.get('#change_password_form_plainPassword_second').type("@Password123");
            cy.get('button').click();
            cy.contains('li', 'The password fields must match.');

            // Enter weak passwords
            cy.get('#change_password_form_plainPassword_first').clear().type("pass");
            cy.get('#change_password_form_plainPassword_second').clear().type("pass");
            cy.get('button').click();
            cy.contains('li', 'Your password should be at least 12 characters');
            cy.contains('li', 'The password strength is too low. Please use a stronger password.');

            // Enter a strong password
            cy.get('#change_password_form_plainPassword_first').clear().type("@Password1234");
            cy.get('#change_password_form_plainPassword_second').clear().type("@Password1234");
            cy.get('button').click();
        })



        // Try to log in with the new password
        cy.visit("http://localhost:5173/signin");
        cy.get('#identifier').type("zueventsproject@gmail.com");
        cy.get('#password').type("@Password1234");
        cy.get('button[type="submit"]').click();

        // Should successfully log in
        cy.url().should('include', '/');
    });



    it('should reject an expired reset token', () => {
        cy.visit("http://localhost:5173/signin")

        cy.contains(".forgotPassword", "Forgot Password").click()
        // should navigate to the password reset page

        cy.origin('http://127.0.0.1:8001', () => {
            cy.url().should('include', 'http://127.0.0.1:8001/reset-password')
            // Type in the user's email
            cy.get("#reset_password_request_form_email").type("zueventsproject@gmail.com")

            // Click the submit button
            cy.get("button").click()

            // should go to this page no matter what
            cy.url().should('include', 'http://127.0.0.1:8001/reset-password/check-email')

            cy.contains('p', 'If an account matching your email exists, then an email was just sent that contains a link that you can use to reset your password.')
        })
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    if (!emails.length) return false;

                    const email = emails.find(e =>
                        e.Content.Headers.Subject.some(subj => subj.includes('Your password reset request'))
                    );

                    if (!email) return false;

                    // Extract the email body
                    return cy.wrap(email.Content.Body).then((body) => {
                        // Remove soft line breaks (`=\n` or `=\r\n` in some cases)
                        const cleanedBody = body.replace(/=\r?\n/g, '');

                        // Extract the full reset link
                        const urlMatch = cleanedBody.match(/http:\/\/127\.0\.0\.1:8001\/reset-password\/reset\/[\w-]+/);

                        if (!urlMatch) {
                            console.error("Reset link not found in email.");
                            return false;
                        }
                        cy.exec('cd ../../backend/cityEventApp && php bin/console app:expire-reset-token')

                        const fullLink = urlMatch[0];

                        // Store and visit the reset link
                        cy.wrap(fullLink).as('resetLink');
                        return cy.visit(fullLink);
                    });
                });
        }, { timeout: 20000, interval: 2000 });
        cy.origin('http://127.0.0.1:8001', () => {

            // Should be redirected to an error page or see an error message
            cy.contains('.alert-danger', 'There was a problem validating your password reset request - The link in your email is expired. Please try to reset your password again.');

            // Should not allow setting a new password
            cy.get('#change_password_form_plainPassword_first').should('not.exist');
            cy.get('#change_password_form_plainPassword_second').should('not.exist');
        })



    });
    after(()=>{
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    })
})