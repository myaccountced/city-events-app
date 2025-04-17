describe('Event Interactions End-to-End tests', () => {
  // Helper function to find a specific event and get its container
  const findEventCard = (title: string) => {
    return cy.contains('h1.eventTitle', title).parents('.p-card.collapseView.event-item')
  }

  it('should show interest and attendance counts to guest user but buttons are not shown, and display buttons to registered user', () => {
    // Visit the home page
    cy.visit('/')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click()

    // Verify interest and attendance counters are visible and show the correct text
    cy.contains('.interest-counter span', '1 person interested', { timeout: 4000 }).should(
      'be.visible'
    )
    cy.contains('.attendance-counter span', '1 person will attend', { timeout: 4000 }).should(
      'be.visible'
    )

    cy.contains('h1.eventTitle', 'Soccer tournament with round robin') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click() // Click it

    // Verify that interest and attendance buttons are not visible for unregistered users
    cy.get('button:contains("I am interested in this event")').should('not.exist')
    cy.get('button:contains("I want to attend this event")').should('not.exist')

    // Visit the signin page and submit
    cy.visit('/signin')
    cy.get('#identifier', { timeout: 5000 }).type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click()

    cy.contains('h1.eventTitle', 'Soccer tournament with round robin') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click() // Click it

    cy.get('.expandView').should('be.visible')

    // Verify that interest and attendance buttons are visible for registered users
    cy.get('button:contains("I am interested in this event")').should('exist')
    cy.get('button:contains("I want to attend this event")').should('exist')
  })

  it('should handle zero counts correctly', () => {
    // Visit the home page
    cy.visit('/')

    // Click on the event with title "Steak night for couples."
    cy.contains('h1.eventTitle', 'Steak night for couples.').click()

    // Verify the text for zero counts on the UI
    cy.get('.interest-counter span').should('contain.text', 'No one interested yet')
    cy.get('.attendance-counter span').should('contain.text', 'No one attending yet')
  })

  it('should handle multiple counts correctly', () => {
    // Visit the home page
    cy.visit('/')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click()

    // Click on the Karaoke event
    findEventCard('Karaoke contest with prizes!').click()

    // Verify the UI text for multiple counts
    cy.contains('.interest-counter span', '2 people interested', { timeout: 700 }).should(
      'be.visible'
    )
    cy.contains('.attendance-counter span', '2 people will attend', { timeout: 700 }).should(
      'be.visible'
    )
  })

  it('should allow registered users to toggle interest from 0 to 1', () => {
    // Sign in as a user
    cy.visit('/signin')
    cy.get('#identifier').type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.contains('h1.eventTitle', 'Steak night for couples.') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click(); // Click it

    // Verify initial count is zero
    cy.get('.interest-counter span').should('contain.text', 'No one interested yet')

    // Click specifically on the first button in the event interaction buttons section
    cy.get('.expandView .event-interaction-buttons button')
      .eq(0) // First button is for interest
      .should('contain.text', 'I am interested in this event')
      .should('have.class', 'standard-button')
      .click()

    // Verify that the button appearance has changed
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'active-button')

    // Verify counter has increased from 0 to 1
    cy.get('.interest-counter span').should('contain.text', '1 person interested')

    // Click the interest button again to toggle off
    cy.get('button:contains("I am interested in this event")').click()

    // Verify button returns to standard state
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'standard-button')

    // Verify counter has decreased back to 0
    cy.get('.interest-counter span').should('contain.text', 'No one interested yet')
  })

  it('should allow registered users to toggle attendance from 0 to 1', () => {
    // Sign in as a user
    cy.visit('/signin')
    cy.get('#identifier').type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.contains('h1.eventTitle', 'Steak night for couples.') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click(); // Click it

    // Verify initial count is zero
    cy.get('.attendance-counter span').should('contain.text', 'No one attending yet')

    // Click specifically on the attend button in the event interaction buttons section
    cy.get('.expandView .event-interaction-buttons button')
      .eq(1) // Second button is for attendance
      .should('contain.text', 'I want to attend this event')
      .should('have.class', 'standard-button')
      .click()

    // Verify that the button appearance has changed
    cy.get('button:contains("I want to attend this event")')
      .should('have.class', 'active-button')

    // Verify counter has increased from 0 to 1
    cy.get('.attendance-counter span').should('contain.text', '1 person will attend')

    // Click the attendance button again to toggle off
    cy.get('button:contains("I want to attend this event")').click()

    // Verify button returns to standard state
    cy.get('button:contains("I want to attend this event")')
      .should('have.class', 'standard-button')

    // Verify counter has decreased back to 0
    cy.get('.attendance-counter span').should('contain.text', 'No one attending yet')
  })

  it('should allow registered users to toggle interest in an event from 1 to 2', () => {
    // Sign in as a user
    cy.visit('/signin')
    cy.get('#identifier').type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.contains('h1.eventTitle', 'Soccer tournament with round robin') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click(); // Click it

    // Click specifically on the first button in the event interaction buttons section
    cy.get('.expandView .event-interaction-buttons button')
      .eq(0) // First button is for interest
      .should('contain.text', 'I am interested in this event')
      .should('have.class', 'standard-button')
      .click()

    // Verify that the button appearance has changed
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'active-button')

    // Verify counter has increased from 1 to 2
    cy.get('.interest-counter span').should('contain.text', '2 people interested')

    // Click the interest button again to toggle off
    cy.get('button:contains("I am interested in this event")').click()

    // Verify button returns to standard state
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'standard-button')

    // Verify counter has decreased back to 1
    cy.get('.interest-counter span').should('contain.text', '1 person interested')
  })

  it('should allow registered users to toggle attendance for an event from 1 to 2', () => {
    // Sign in as a user
    cy.visit('/signin')
    cy.get('#identifier').type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.contains('h1.eventTitle', 'Soccer tournament with round robin') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click(); // Click it

    // Click specifically on the attend button in the event interaction buttons section
    cy.get('.expandView .event-interaction-buttons button')
      .eq(1) // Second button is for attendance
      .should('contain.text', 'I want to attend this event')
      .should('have.class', 'standard-button')
      .click()

    // Verify that the button appearance has changed
    cy.get('button:contains("I want to attend this event")')
      .should('have.class', 'active-button')

    // Verify counter has increased from 1 to 2
    cy.get('.attendance-counter span').should('contain.text', '2 people will attend')

    // Click the attendance button again to toggle off
    cy.get('button:contains("I want to attend this event")').click()

    // Verify button returns to standard state
    cy.get('button:contains("I want to attend this event")')
      .should('have.class', 'standard-button')

    // Verify counter has decreased back to 1
    cy.get('.attendance-counter span').should('contain.text', '1 person will attend')
  })

  it('should handle mutually exclusive interaction states', () => {
    // Sign in as a user
    cy.visit('/signin')
    cy.get('#identifier').type('username2')
    cy.get('#password').type('@Password2')
    cy.get('button[type="submit"]').click()

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.contains('h1.eventTitle', 'Karaoke contest with prizes!') // Find the title
      .closest('.event-item') // Go up to the parent card container
      .find('.eventButton span.p-button-icon') // Find the button *within* this specific card
      .click(); // Click it

    // First select "Interested"
    cy.get('.expandView .event-interaction-buttons button')
      .eq(0) // First button is for interest
      .should('contain.text', 'I am interested in this event')
      .should('have.class', 'standard-button')
      .click()

    // Verify "Interested" button is active
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'active-button')
    cy.get('.interest-counter span').should('contain.text', '3 people interested')

    // Now select "Attending" - should switch from Interested to Attending
    cy.get('button:contains("I want to attend this event")').click()

    // Verify "Attending" is now active and "Interested" is inactive
    cy.get('button:contains("I want to attend this event")')
      .should('have.class', 'active-button')
    cy.get('button:contains("I am interested in this event")')
      .should('have.class', 'standard-button')

    // Verify counts have updated properly
    cy.get('.interest-counter span').should('contain.text', '2 people interested')
    cy.get('.attendance-counter span').should('contain.text', '3 people will attend')

    // Now select "Attending" - restore it back to 2
    cy.get('button:contains("I want to attend this event")').click()
  })
})
