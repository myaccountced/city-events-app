// zac's tests for posting events
import { loginHelper } from './LoginHelper'

describe("Visit the home page and press the button that goes to the post event page", () =>{
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    });

    it("Login and check that the post new event button appears", () =>{
        cy.visit("/")
        //before logging in, the post event page should just redirect to sign in page
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();
        cy.url().should('eq', 'http://localhost:5173/signin');

        cy.contains('button', 'Sign In').click()

        cy.get('#identifier').type('username1');
        cy.get('#password').type('@Password1');
        cy.get('button[type="submit"]').click();
        cy.url( { timeout: 10000 }).should('not.include', '/signin');
        cy.intercept('/events/media?*').as('getMedia');
        //cy.wait('@getMedia');
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();
        cy.url().should('eq', 'http://localhost:5173/postevent');
    })
})

describe("Various post event input error messages", () => {
    beforeEach(() => {
        cy.visit('/')
        cy.contains('button', 'Sign In').click()

        loginHelper('username1', '@Password1');

        // click on Post New Event in navabr
        cy.get('.p-menubar-item-link').eq(5).click()
    });


    it("Check for various errors", () => {
        //test that the title field is required
        //cy.get('#eventTitle').type('')
        cy.get('#eventDescription', { timeout: 10000 }).type('Test Description')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('17:00')
        cy.get('#eventEndTime').type('17:00')
        cy.get(`[data-cy=category-tag-music]`).first().click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').type('https://www.google.ca')

        cy.contains('button', 'Post Event').click()
        cy.contains('#errorTitle', "You must enter in a title for the event")


        //Test that an invalid title is entered
        cy.get('#eventTitle').clear().type('T3st 3vent!')
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorTitle', "The event title cannot contain numbers or special characters")

        //Test for user does not enter in a description
        cy.get('#eventTitle').clear().type('Test Event')
        cy.get('#eventDescription').clear()
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorDescription', 'The description must be between 10 and 250 characters long.')

        //Test that a user enters a description that is less than 10 characters
        cy.get('#eventDescription').clear().type('test')
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorDescription', 'The description must be between 10 and 250 characters long.')

        //test that a description of 10 characters long succeeds
        cy.get('#eventTitle').clear().type('Test Event')
        cy.get('#eventDescription').clear().type('0123456789')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('17:00')
        cy.get('#eventEndTime').type('17:00')
        // cy.get(`[data-cy=category-tag-music]`).click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').type('https://www.google.ca')
        cy.contains('button', 'Post Event').click()
        cy.contains('h1', 'Your event has been submitted for moderator review.')

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(5000)
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();

        //Test a description that is 250 is allowed
        cy.get('#eventTitle', { timeout: 10000 }).clear().type('Test Event')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('17:00')
        cy.get('#eventEndTime').type('17:00')
        cy.get(`[data-cy=category-tag-music]`).first().click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').type('https://www.google.ca')
        cy.get('#eventDescription').clear().type('a'.repeat(250));
        cy.contains('button', 'Post Event').click()
        cy.contains('h1', 'Your event has been submitted for moderator review.')

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(5000)
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();

        //Test that description that is longer than 250 characters fails
        cy.get('#eventDescription', { timeout: 10000 }).clear().type('a'.repeat(251));
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorDescription', 'The description must be between 10 and 250 characters long.')

        //User does not enter in a location - produces error
        cy.get('#eventDescription').clear().type('0123456789')
        cy.get('#eventLocation').clear()
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorLocation', 'You must enter in a city for the event.')

        //User enters in invalid city - produces error
        cy.get('#eventLocation').clear().type('S4skat00n')
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorLocation', 'You must enter in a valid city name for the event.')

        //User does not enter in start date -- produces error
        cy.get('#eventLocation').clear().type('Saskatoon')
        cy.get('#eventStartDate').clear()
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorStartDate', 'You must enter a start date.')

        //user enters a start date that is before today's date -- produces error
        cy.get('#eventStartDate').clear().type('2020-01-01')
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorStartDate', 'You must enter a start date that is after today.')

        //User does not enter an end date - should succeed as it is optional
        cy.get('#eventTitle').clear().type('Test Event')
        cy.get('#eventDescription').type('0123456789')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        //cy.get('#eventEndDate').type('')
        cy.get('#eventStartTime').type('17:00')
        cy.get('#eventEndTime').type('17:00')
        cy.get(`[data-cy=category-tag-music]`).first().click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').type('https://www.google.ca')

        cy.contains('button', 'Post Event').click()
        cy.contains('h1', 'Your event has been submitted for moderator review.')

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(5000)
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();

        //User enters an end date before the start date- produces error
        cy.get('#eventStartDate', { timeout: 10000 }).type('2026-01-01')
        cy.get('#eventEndDate').clear().type('2025-12-31')
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorEndDate', 'The end date must come after the start date.')

        //user does not enter a start time - produces an error
        cy.get('#eventEndDate').clear().type('2026-01-02')
        cy.get('#eventStartTime').clear()
        cy.contains('button', 'Post Event').click()
        cy.contains('#errorStartTime', 'You must enter in the starting time of the event.')


        //user does not enter in an end time -- success
      cy.get('#eventTitle').clear().type('Test Event')
        cy.get('#eventDescription').type('0123456789')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('17:00')
        //cy.get('#eventEndTime').type('')
        cy.get(`[data-cy=category-tag-music]`).first().click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').type('https://www.google.ca')

        cy.contains('button', 'Post Event').click()
        cy.contains('h1', 'Your event has been submitted for moderator review.')

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(5000)
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();

        // user does not include any external links
        cy.get('#eventTitle', { timeout: 10000 }).clear().type('Test Event')
        cy.get('#eventDescription').type('0123456789')
        cy.get('#eventLocation').type('Saskatoon')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('17:00')
        cy.get('#eventEndTime').type('18:00')
        cy.get(`[data-cy=category-tag-music]`).first().click();
        //cy.get('#eventCategory').select('Music')
        cy.get('#eventAudience').select('Family Friendly')
        cy.get('#eventLink').clear()
        cy.contains('button', 'Post Event').click()
        cy.contains('h1', 'Your event has been submitted for moderator review.')

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(5000)
        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();
    })
})

// end of zac's tests

//region story 41 banned user cannot post an event
describe("Banned user cannot post an event", () => {
    it('should prevent banned user to post an event', () => {
        cy.visit('/')
        cy.contains('button', 'Sign In').click()

        cy.get('#identifier').type('username11');
        cy.get('#password').type('@Password1');
        cy.get('button[type="submit"]').click();

        cy.url().should('not.include', '/signin', { timeout: 5000 });
        cy.intercept('/events/media?*').as('getMedia');
        //cy.wait('@getMedia');
        cy.get('.event-item', { timeout: 10000 });

        cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();
        cy.contains("div", "You are banned, cannot post at the moment.", { timeout: 20000 })
    })
})
//endregion



// Story 50 - Registered User Creates Recurring Event Series
describe('Registered user does not pick a Recurrence Type after checking the Recurring Event checkbox', () => {
    it('validates the null recurrence type', () => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')

        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/', { timeout: 5000 })

        // Post an event
        cy.visit('/postevent')
        cy.get('#eventTitle').clear().type('Zombie Cosplay Weekly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.contains('button', 'Post Event').click()
        // Error message should appear
        cy.contains('Pick whether the event is a weekly, bi-weekly, or a monthly event.').should('be.visible');
    })
})

describe('Registered user assigns an event as a monthly recurring event with a start date of January 31st', () => {
    it('Posts a monthly event with a start date of January 31st and check its details', () => {
        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/')

        // Post the event
        cy.visit('/postevent')
        cy.get('#eventTitle').clear().type('Zombie Cosplay Monthly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-31')
        cy.get('#eventEndDate').type('2026-02-01')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.contains('button', '📅🌙MONTHLY').click()
        cy.contains('button', 'Post Event').click()
        cy.wait(6000)
        cy.url().should('eq', 'http://localhost:5173/')

        // Check the dates
        cy.visit('/myevents')
        cy.scrollTo('bottom')

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-31') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-02-01'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-02-28'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-03-01'); // Check if the end date contains the expected date
    })
})

describe('Registered user assigns events as recurrent events with an instance number of 2', () => {
    beforeEach(() => {
        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/')
    });

    it('Posts a weekly, bi-weekly, and monthly events', () => {
        // Reset the database
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')

        cy.visit('/postevent')

        // Post a weekly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Weekly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.get('#eventInstanceNumberCB').clear().type('1') // This should default to 2
        cy.contains('button', '📅WEEKLY').click()
        cy.contains('button', 'Post Event').click()

        cy.wait(5000)
        cy.visit('/postevent')

        // Post a bi-weekly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Bi Weekly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.contains('button', '📅2️⃣BI-WEEKLY').click()
        cy.contains('button', 'Post Event').click()

        cy.wait(5000)
        cy.visit('/postevent')

        // Post a monthly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Monthly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.contains('button', '📅🌙MONTHLY').click()
        cy.contains('button', 'Post Event').click()
        cy.wait(6000)
    })

    it('checks the details of the recurring events', () => {
        cy.visit('/myevents') // Go to My Events page
        cy.scrollTo('bottom')

        // Check if the start dates and end dates are correct
        // Weekly
        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-08'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-09'); // Check if the end date contains the expected date

        // Bi-weekly
        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-15'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-16'); // Check if the end date contains the expected date

        // Monthly
        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-02-01'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(1)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-02-02'); // Check if the end date contains the expected date
    })
})

describe('Registered user assigns events as recurrent events with an instance number of 12', () => {
    beforeEach(() => {
        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/')
    });

    it('Posts a weekly, bi-weekly, and monthly events', () => {
        // Reset the database
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')

        cy.visit('/postevent')

        // Post a weekly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Weekly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.get('#eventInstanceNumberCB').clear().type('13') // This should default to 12
        cy.contains('button', '📅WEEKLY').click()
        cy.contains('button', 'Post Event').click()

        cy.wait(5000)
        cy.visit('/postevent')

        // Post a bi-weekly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Bi Weekly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.get('#eventInstanceNumberCB').clear().type('12')
        cy.contains('button', '📅2️⃣BI-WEEKLY').click()
        cy.contains('button', 'Post Event').click()

        cy.wait(5000)
        cy.visit('/postevent')

        // Post a monthly event
        cy.get('#eventTitle').clear().type('Zombie Cosplay Monthly Competition')
        cy.get('#eventDescription').clear().type('This is a zombie cosplay competition! Where you dress up as zombie, duh.')
        cy.get('#eventLocation').clear().type('Cemetery')
        cy.get(`[data-cy=category-tag-arts-and-culture]`).first().click()
        cy.get('#eventAudience').select('Youth')
        cy.get('#eventStartDate').type('2026-01-01')
        cy.get('#eventEndDate').type('2026-01-02')
        cy.get('#eventStartTime').type('12:00')
        cy.get('#eventEndTime').type('13:00')
        cy.get('#recurringEventCheckbox').click()
        cy.get('#eventInstanceNumberCB').clear().type('12')
        cy.contains('button', '📅🌙MONTHLY').click()
        cy.contains('button', 'Post Event').click()
        cy.wait(6000)
    })

    it('checks the details of the recurring events', () => {
        cy.visit('/myevents')
        cy.scrollTo('bottom')

        // Check if the start dates and end dates are correct
        // Weekly
        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-03-19'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Weekly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-03-20'); // Check if the end date contains the expected date

        // Bi-weekly
        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-06-04'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Bi Weekly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-06-05'); // Check if the end date contains the expected date

        // Monthly
        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-01-01') // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(0)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-01-02'); // Check if the end date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventStartDate') // Find the start date element
            .should('contain.text', '2026-12-01'); // Check if the start date contains the expected date

        cy.get('.eventTitle:contains("Zombie Cosplay Monthly Competition")').eq(11)
            .parents('.p-card-body') // Traverse up to the parent card container
            .find('.eventEndDate') // Find the end date element
            .should('contain.text', '2026-12-02'); // Check if the end date contains the expected date
    })
})
// End Story 50 - Registered User Creates Recurring Event Series
