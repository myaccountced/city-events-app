// region Hayden's sotry 49 tests
describe('Event Update Functionality', () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create && php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures && php bin/console doctrine:database:drop --force --env=test && php bin/console doctrine:database:create --env=test && php bin/console doctrine:schema:create --env=test && php bin/console app:load-test-fixtures --env=test')
    })

    beforeEach(() => {
        cy.visit('/')
        cy.contains('button', 'Sign In').click()

        cy.get('#identifier').type('Moderator2');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();

        cy.wait(1500)
    });


    it('should allow update of pending event', () => {
        // Visit my events page
        cy.visit('/myevents')

        cy.wait(1500)

        // Click edit for pending event
        cy.get('#edit1109').contains("Edit").click()

        // Confirm edit
        cy.get('.p-button-label').contains('Yes').click()

        // Verify pre-populated form fields
        cy.get('#eventTitle').should('not.be.empty')
        cy.get('#eventDescription').should('not.be.empty')
        cy.get('#eventLocation').should('not.be.empty')

        // Update event details
        cy.get('#eventTitle').clear().type('A New Pending Title')
        cy.get('#eventDescription').clear().type('A New Test Description')
        cy.get('#eventLocation').clear().type('A New Saskatoon')
        cy.get('#eventStartDate').clear().type('2026-01-02')
        cy.get('#eventEndDate').clear().type('2026-01-02')
        cy.get('#eventStartTime').clear().type('19:00')
        cy.get('#eventEndTime').clear().type('21:00')

        // Select category
        cy.get(`[data-cy=category-tag-music]`).first().click()
        //un-select others category
        cy.get(`[data-cy=category-tag-others]`).first().click()

        // Select audience
        cy.get('#eventAudience').select('General')

        // Add external link
        cy.get('#eventLink').clear().type('https://www.google.com')

        // Submit the update
        cy.contains('button', 'Update Event').click()

        // Verify successful submission and redirection
        cy.url().should('include', '/myevents')
        cy.contains('Your event has been updated successfully').should('be.visible')
    })

    it('should allow update of approved event', () => {
        // Visit my events page
        cy.visit('/myevents')

        cy.wait(1500)

        // Click edit for approved event
        cy.get('#edit1109').contains("Edit").click()

        // Confirm edit
        cy.get('.p-button-label').contains('Yes').click()

        // Verify pre-populated form fields
        cy.get('#eventTitle').should('not.be.empty')
        cy.get('#eventDescription').should('not.be.empty')
        cy.get('#eventLocation').should('not.be.empty')

        // Update event details
        cy.get('#eventTitle').clear().type('A New Test Title')
        cy.get('#eventDescription').clear().type('A New Test Description')
        cy.get('#eventLocation').clear().type('A New Saskatoon')
        cy.get('#eventStartDate').clear().type('2026-01-22')
        cy.get('#eventEndDate').clear().type('2026-01-22')
        cy.get('#eventStartTime').clear().type('19:00')
        cy.get('#eventEndTime').clear().type('21:00')

        // Select a new category
        cy.get(`[data-cy=category-tag-music]`).first().click()
        //un-select others category
        cy.get(`[data-cy=category-tag-others]`).first().click()

        // Select audience
        cy.get('#eventAudience').select('General')

        // Add external link
        cy.get('#eventLink').clear().type('https://www.google.com')

        // Submit the update
        cy.contains('button', 'Update Event').click()

        // Proceed with update
        cy.get('button[type="submit"]').click()

        // Verify redirection to pending events
        cy.visit("/moderator/pending")
        cy.scrollTo('bottom')
        cy.get('.event-title').contains('A New Test Title').should('exist')
    })


    it('should handle validation errors', () => {
        // Visit my events page
        cy.visit('/myevents')

        cy.wait(1500)

        // Click edit for an existing event
        cy.get('#edit1109').contains("Edit").click()

        // Confirm edit
        cy.get('.p-button-label').contains('Yes').click()

        // Clear required fields
        cy.get('#eventTitle').clear()
        cy.get('#eventDescription').clear()
        cy.get('#eventLocation').clear()
        cy.get('#eventStartDate').clear()
        cy.get('#eventStartTime').clear()

        // Try to submit
        cy.contains('button', 'Update Event').click()

        // Verify validation errors
        cy.get('.error-message').should('have.length.greaterThan', 0)
        cy.get('#errorTitle').should('contain', 'You must enter in a title')
        cy.get('#errorDescription').should('contain', 'description must be between')
        cy.get('#errorLocation').should('contain', 'You must enter in a city')
        cy.get('#errorStartDate').should('contain', 'You must enter a start date')
        cy.get('#errorStartTime').should('contain', 'You must enter in the starting time')
    })
})

describe("User can Delete an event", () => {
    beforeEach(() => {
        cy.visit('/')
        cy.contains('button', 'Sign In').click()

        cy.get('#identifier').type('Moderator2');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();

        cy.wait(1500)
    });
    after(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create && php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures && php bin/console doctrine:database:drop --force --env=test && php bin/console doctrine:database:create --env=test && php bin/console doctrine:schema:create --env=test && php bin/console app:load-test-fixtures --env=test')
        cy.reload()
    })

    it('should cancel deletion when reject button is clicked', () => {
        // Visit my events page
        cy.visit('/myevents')

        // Wait for events to load
        cy.wait(1500)

        // Store the initial number of events
        cy.get('#eventFeed').then($events => {
            const initialEventCount = $events.length

            // Click delete button on approved event
            cy.get('#delete1109').contains("Delete").click()

            // Click cancel/reject button
            cy.get('.p-button-label').contains('No').click()


            // Verify number of events remains the same
            cy.get('#eventFeed').should('have.length', initialEventCount)
        })
    })

    it('should successfully delete an event', () => {
        // Visit my events page
        cy.visit('/myevents')

        // Wait for events to load
        cy.wait(1500)

        // Store the initial number of events
        cy.get('#eventFeed').then($events => {
            const initialEventCount = $events.length

            // Click delete button on first event
            cy.get('#delete1109').contains("Delete").click()

            // Confirm deletion
            cy.get('.p-button-label').contains('Yes').click()

            //reload page
            cy.reload()

            // Verify number of events has decreased
            cy.get('#eventFeed').should('have.length', initialEventCount - 1)
        })
    })
})
//endregion