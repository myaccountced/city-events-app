import { loginHelper } from './LoginHelper'

describe('My Events Page Functionality', () => {
  const backendMediaURL = 'http://127.0.0.1:8001/uploads/'
  before(() => {
    //cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:schema:drop --force & php bin/console doctrine:schema:create & php bin/console app:load-test-fixtures')

    // set up intercepts to track if any unexpected calls for media are made
    cy.intercept('GET', '/events/media?eventID=*', (req) => {
      assert.fail("Unexpected media fetch request");
    }).as('unexpectedMediaRequest')

    // set up intercepts to track if any unexpected calls for bookmarks are made
    cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
      assert.fail("Unexpected Bookmark fetch request");
    }).as('unexpectedBookmarkRequest')
  })
    describe('No Event for both future and past', () => {
      it('should show appropriate message when user has no future events', () => {
        // Simulate user with no events, moderator
        cy.visit('/')

        // Visit My Events page
        loginHelper('username11', '@Password1')
        cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').click();

        // Verify "no events" message is displayed
        cy.get('.no-events').contains("You don't have any future events.").should('be.visible')
        // Click Past Events button
        cy.get('.selectButton').click();

        cy.get('.no-events').contains("You don't have any past events.").should('be.visible')
      })
    })

})
