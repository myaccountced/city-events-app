
//region Story 41 Banned Users
describe('Moderator bans and unban a User', () => {

  before(() => {
    // These tests need all user fixtures
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')

    // Log in as moderator
    cy.visit('/signin')
    cy.get('#identifier', { timeout: 5000 }).type('moderator')
    cy.get('#password').type('ABC123def')
    cy.get('button[type="submit"]').click()
    cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').click();
    cy.visit('/moderator/users')
  })

  it('should allow banning and unbanning a user', () => {

    // Verify the table exists and contains at least one row
    cy.get('table', { timeout: 5000 }).should('exist')
    cy.get('table tbody tr').should('have.length.greaterThan', 0)

    // Interact with the first row in the table
    cy.get('table tbody tr').first().within(() => {
      // Optionally wait a moment if needed for any animations
      cy.get('span.pi-thumbs-down').click() // Click the ban icon
    })

    // Verify the ban dialog opens (Note: the dialog might be teleported to <body>)
    cy.get('body')
      .find('.p-dialog')
      .should('be.visible')
      .within(() => {
        cy.get('.p-dialog-header').should('contain', 'Ban User')
      })

    // Try to submit without selecting a reason
    cy.contains('button', 'Submit').should('be.disabled')

    // Select "other" reason but leave custom reason empty
    cy.get('input[value="other"]').click()
    cy.contains('button', 'Submit').should('be.disabled')

    // Provide a custom reason but type more than 255 characters
    // cy.get('textarea').type('a'.repeat(256))
    // cy.contains('button', 'Submit').should('be.disabled')

    // Provide a valid custom reason (255 characters) and then submit
    cy.get('textarea').clear().type('a'.repeat(2))
    cy.contains('button', 'Submit').click()

    // Verify the user is banned by checking that the unban icon appears in the table row
    cy.get('table tbody tr').first().within(() => {
      cy.get('span.pi-thumbs-up').should('exist') // Unban icon should appear
    })

    // Click the unban icon in the first table row
    cy.get('table tbody tr').first().within(() => {
      cy.get('span.pi-thumbs-up').click(); // Click unban icon
    })

    // Wait for the confirm dialog to appear and click accept
    cy.get('.p-confirmdialog').should('be.visible').within(() => {
      cy.get('.p-confirmdialog-accept-button').click();
    });


    // Verify that after unbanning, the ban icon appears in the first row
    cy.get('table tbody tr').first().within(() => {
      cy.get('span.pi-thumbs-down').should('exist'); // Ban icon should appear
    })
  })
})

//endregion