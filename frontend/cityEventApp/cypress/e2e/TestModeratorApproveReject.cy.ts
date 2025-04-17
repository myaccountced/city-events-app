describe('Moderator Attempts Approval and Rejection', () => {
  beforeEach(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    cy.visit('/signin');
    cy.get('#identifier').type('moderator'); // Type valid moderator username
    cy.get('#password').type('ABC123def'); // Type valid moderator password
    cy.get('button[type="submit"]').click();
    // set up intercepts to track if any unexpected calls for media are made
    cy.intercept('GET', '/events/media?eventID=*', (req) => {
      assert.fail("Unexpected media fetch request");
    }).as('unexpectedMediaRequest')

    // set up intercepts to track if any unexpected calls for bookmarks are made
    cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
      assert.fail("Unexpected Bookmark fetch request");
    }).as('unexpectedBookmarkRequest')
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(3000)

    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(3000)
    cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
  })

  it('No reason for rejection', () => {
    cy.get('#pendingTable tbody tr').first().within(() => {
      cy.get('.reject').should('not.exist')
      cy.get('.p-datatable-row-toggle-button', { timeout: 10000 }).should('exist').click();
    })
    cy.get('.rejectBtn').click()
    cy.get('.reject').should('exist')
    cy.get('.errorMessage').should('not.exist')
    cy.get('.submitBtn').click()
    cy.get('.errorMessage p').should('exist').should('contain', 'Reason for rejection is required')
    cy.get('.rejectReason').type('          ')
    cy.get('.submitBtn').click()
    cy.get('.errorMessage p').should('exist').should('contain', 'Reason for rejection is required')
  })

  it('Reason for rejection to short', () => {
    cy.get('#pendingTable tbody tr').first().within(() => {
      cy.get('.reject').should('not.exist')
      cy.get('.p-datatable-row-toggle-button', { timeout: 10000 }).should('exist').click();
    })
    cy.get('.rejectBtn').click()
    cy.get('.reject').should('exist')
    cy.get('.errorMessage').should('not.exist')
    cy.get('.rejectReason').type('AAAAAAAAA')
    cy.get('.submitBtn').click()
    cy.get('.errorMessage p').should('exist').should('contain', 'Reason for rejection must be between 10 and 255 characters!')
  })

  it('Reason for rejection to long', () => {
    cy.get('#pendingTable tbody tr').first().within(() => {
      cy.get('.reject').should('not.exist')
      cy.get('.p-datatable-row-toggle-button', { timeout: 10000 }).should('exist').click();
    })
    cy.get('.rejectBtn').click()
    cy.get('.reject').should('exist')
    cy.get('.errorMessage').should('not.exist')
    const text = 'A'.repeat(256);
    cy.get('.rejectReason').type(text)
    cy.get('.submitBtn').click()
    cy.get('.errorMessage p').should('exist').should('contain', 'Reason for rejection must be between 10 and 255 characters!')
  })

  it('Successful Rejection', () => {
    cy.get('#pendingTable tbody tr').first().within(() => {
      cy.get('.reject').should('not.exist')
      cy.get('.p-datatable-row-toggle-button', { timeout: 10000 }).should('exist').click();
    })
    cy.get('.rejectBtn').click()
    cy.get('.reject').should('exist')
    cy.get('.errorMessage').should('not.exist')
    cy.get('.rejectReason').type('Rejection Reason')
    cy.get('.submitBtn').click()
    cy.get('.reject').should('not.exist')
    cy.get('.success-message').should('exist').should('contain.text', ' rejected')
  })

  it('Successful Approval', () => {
    cy.get('#pendingTable tbody tr').first().within(() => {
      cy.get('.reject').should('not.exist')

      cy.get('.p-datatable-row-toggle-button', { timeout: 10000 } ).should('exist').click();
    })
    cy.get('.approveBtn').click();
    cy.get('.success-message').should('exist').should('contain.text', ' approved')
  })
  after(()=>{
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  })
})

//legacy tests - non-moderators cannot be able to access these buttons
/*describe('Non-Moderator Attempts Approval and Rejection', () => {
  beforeEach(() => {
    cy.visit('/signin');
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000)
    cy.get('#event-li a').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000)
  })

  it('Non-Mod denied Rejection', () => {
    cy.get('.event').first().within(() => {
      cy.get('.reject').should('not.be.visible')
      cy.get('.eventButton').click()
      cy.get('.rejectBtn').should('not.exist')
      cy.get('.reject').should('not.be.visible')
    })
  })

  it('Non-Mod denied Approval', () => {
    cy.get('.event').first().within(() => {
      cy.get('.reject').should('not.be.visible')
      cy.get('.eventButton').click()
      cy.get('.approveBtn').should('not.exist')
      cy.get('.reject').should('not.be.visible')
    })
  })
})*/
