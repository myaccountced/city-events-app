// ------------------Story 40 - Moderator managed reported events------------------
describe('Non-moderator Fails to Navigate to the Reported Events Page', () => {
    it('tries to access the Moderation page but fails', () => {
        cy.visit('http://localhost:5173/');               // Visit the site
        cy.get('#signInOut').click();                 // Click the 'Sign In' button
        cy.get('#identifier').type('username1'); // Type valid username
        cy.get('#password').type('@Password1');  // Type valid password
        cy.get('button[type="submit"]').click();
        cy.url( { timeout: 7000 } ).should('eq', 'http://localhost:5173/');

        cy.visit('http://localhost:5173/moderator/reported');             // Go to the moderation page
        cy.url().should('eq', 'http://localhost:5173/'); // Should remain in the Event page
    })
})

describe('Event Details and Event Report Details Are Displayed. Testing clear reports functionality', () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    });

    // Testing - Moderator Navigates to the Reported Events Page
    // Testing - Events Shown Are Events Reported Three Times
    // Testing - Event details are displayed
    // Testing - Event reports details
    it('signs in as moderator and check the order and the exact data of the upcoming reported events', () => {
        // Testing - Moderator Navigates to the Reported Events Page
        cy.visit('http://localhost:5173/');               // Visit the site
        cy.get('#signInOut').click();                 // Click the 'Sign In' button
        cy.get('#identifier').type('moderator'); // Type valid moderator username
        cy.get('#password').type('ABC123def');   // Type valid moderator password
        cy.get('button[type="submit"]').click();
        cy.url({ timeout: 7000 }).should('eq', 'http://localhost:5173/');

        cy.visit('http://localhost:5173/moderator/reported');     // Go to the moderation page
        cy.get('table').should('exist'); // Check if the table exist

        // Testing - Events Shown Are Events Reported Three Times
        cy.get('table').find('tbody').find('tr').eq(0).find('td').eq(1).should('have.text', 'RepEvent2');
        cy.get('table').find('tbody').find('tr').eq(1).find('td').eq(1).should('have.text', 'RepEvent3');
        cy.get('table').find('tbody').find('tr').eq(2).find('td').eq(1).should('have.text', 'RepEvent1');

        // Testing - Event details and report details are displayed
        const events = [
            { row: 0, description: 'Description of the event', links: 'Link', creator: 'creator', reportDetails: [
                    'Reason: Spam, reported at 2025-12-01 12:00',
                    'Reason: Illegal activity, reported at 2025-12-02 12:00',
                    'Reason: Hurts my pride, reported at 2025-12-03 12:00',
                ], images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                ], startDate: '2025-12-12'},
            { row: 1, description: 'Description of the event', links: 'Link', creator: 'creator', reportDetails: [
                    'Reason: Spam, reported at 2025-12-01 12:00',
                    'Reason: Misleading location or time, reported at 2025-12-02 12:00',
                    'Reason: Misleading location or time, reported at 2025-12-03 12:00',
                ], images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                ], startDate: '2025-12-12'},
            { row: 2, description: 'Description of the event', links: 'Link', creator: 'creator', reportDetails: [
                    'Reason: False Information, reported at 2025-12-01 12:00',
                    'Reason: This hurts people, reported at 2025-12-02 12:00',
                    'Reason: Harassment or abuse, reported at 2025-12-03 12:00',
                ], images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                ], startDate: '2025-12-13'},
        ];

        events.forEach((event, index) => {
            const rowIndex = event.row;
            // Check event details
            cy.get('table').find('tbody').find('tr').eq(rowIndex).within(() => {
                cy.get('td').eq(2).should('have.text', 'Arts and Culture');
                cy.get('td').eq(3).should('have.text', 'Youth');
                cy.get('td').eq(4).should('have.text', 'Saskatoon');
                cy.get('td').eq(5).should('have.text', `${event.startDate}`);
                cy.get('td').eq(6).should('have.text', '2025-12-20');
            });

            // Click button
            cy.get('table').find('tbody').find('tr').eq(rowIndex).find('td').eq(0).find('button').click();

            // Check description, links, creator
            cy.get('table').find('tbody').find('tr').eq(rowIndex + 1).within(() => {
                cy.get('td').eq(0).find('div').eq(0).within(() => {
                    cy.get('p').eq(0).should('have.text', `Description: ${event.description}`);
                    cy.get('p').eq(1).should('have.text', `Links: ${event.links}`);
                    cy.get('p').eq(2).should('have.text', `Creator: ${event.creator}`);

                    // Check images
                    event.images.forEach((imgSrc, i) => {
                        cy.get('span').eq(i).find('img').should('have.attr', 'src', imgSrc);
                    });

                    // Check report details
                    event.reportDetails.forEach((report, i) => {
                        cy.get('p').eq(i + 4).should('have.text', report);
                    });
                });
            });

            cy.get('table').find('tbody').find('tr').eq(rowIndex).find('td').eq(0).find('button').click();
        });
    })
})

describe('Successful and Failed Clear of the Reports of an Event', () => {
    it('clears the reports of the first event successfully and tries the next one but fails', () => {
        // Sign-in and navigate to the moderator reported page
        cy.visit('http://localhost:5173/');
        cy.get('#signInOut').click();
        cy.get('#identifier').type('moderator');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();
        cy.url({ timeout: 7000 }).should('eq', 'http://localhost:5173/');
        cy.visit('http://localhost:5173/moderator/reported');

        // Success
        cy.get('table').find('tbody').find('tr').eq(0).find('td').eq(7).find('span').click();
        cy.get('span.p-button-label').contains('Proceed').closest('button').click(); // Click Proceed in the confirmation box
        // First row would be RepEvent3 instead of RepEvent2
        cy.get('table').find('tbody').find('tr').eq(0).find('td').eq(1).should('have.text', 'RepEvent3');
        // Check the success message
        cy.get('div.p-toast-detail').contains('Reports for RepEvent2 have been cleared').should('be.visible');

        // Failed attempt
        cy.intercept('DELETE', '/api/clear_reports', {
            statusCode: 500, // Simulate backend failure
            body: { success: false, message: 'Cannot clear the event for now. Try again later' }
        }).as('clearReportsFailure');

        cy.get('table').find('tbody').find('tr').eq(0).find('td').eq(7).find('span').click();
        cy.get('span.p-button-label').contains('Proceed').closest('button').click(); // Click Proceed in the confirmation box
        cy.wait('@clearReportsFailure'); // Wait for the failed intercept
        cy.get('div.p-toast-detail').contains('Try deleting again later').should('be.visible');
    })
})

describe('There Are No Upcoming Reported Events', () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures EventFixtureNoEvent')
    });

    it('displays an error message that informs the user that there is no upcoming reported events', () => {
        // Sign in as a moderator and navigate to the Reported tab
        cy.visit('http://localhost:5173/');
        cy.get('#signInOut').click();
        cy.get('#identifier').type('moderator2');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();
        cy.url({ timeout: 7000 }).should('eq', 'http://localhost:5173/');

        cy.visit('http://localhost:5173/moderator/reported');     // Go to the moderation page
        cy.get('h1[id="noEventsMessage"]').should('exist');

        // Both of these throw cypress errors
        // cy.exec('cd ../../backend/cityEventApp && php bin/console --env=test app:load-test-fixtures')
        // cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    })
    after(()=>{
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    })

})

