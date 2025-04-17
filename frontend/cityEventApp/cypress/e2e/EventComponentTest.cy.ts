// Rayne's tests for displaying event images
import { loginHelper } from './LoginHelper'

/*
describe('Test that images are visible when an event has them', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures EventWithMedia')
  });

  beforeEach(() => {
    // Visit the home page of your web application
    cy.visit('/');
  });

  // load the fixtures for these tests


  // find some specific events made with fixtures in back end

  it('should have some event information visible, but not all', () => {
    cy.get('.eventTitle', { timeout: 10000 }).contains('Dance Competition').should('be.visible')
    cy.get('.eventDescription').should('not.exist')
    cy.get('.imageGallery').should('not.exist')
    cy.get('.imageThumbnail').eq(2).should('be.visible')
  })

  it('should show the image gallery, description, and extended link when an event is expanded', () => {
    //ensure that the event info is on the page
    //expand the event
    cy.intercept('/events/media?*').as('getMedia')
    cy.get('.event-item .eventButton', { timeout: 10000 }).eq(2).click()


    cy.get('.event-item .eventDescription').should('be.visible')
    cy.get('.p-galleria').should('exist')
    cy.get('.eventLink').should('be.visible')

    // collapse the event again
    cy.get('.event-item .eventButton').eq(2).click()
    cy.get('.event-item .eventDescription').should('not.exist')
    cy.get('.p-galleria').should('not.exist')
  })

  it('shows no image gallery when an event has no images', () => {
    cy.get('.eventTitle', { timeout: 10000 }).contains('Flea Market').should('be.visible')
    cy.get('.event-item .eventButton').eq(0).click()
    cy.get('.event-item .eventDescription').should('be.visible')
    cy.get('.p-galleria').should('not.exist')
    // collapse the event again
    cy.get('.event-item .eventButton').eq(0).click()
  })
  after(()=>{
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  })

});

// end of rayne's tests

*/

//Tests for sharing via social media (story 46)
describe('Post event link on Facebook and X', () => {
  beforeEach(() => {
    Cypress.on('uncaught:exception', (err, runnable) => {
      return false;
    });
    // Visit the home page of the application
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    cy.visit('/');
  });
  it("Should generate a link for Facebook that leads to the individual link's event details page", () =>{
    // click on the share button on the Event with the title 'Karaoke contest with prizes!'

    cy.contains('#eventFeed', 'Karaoke contest with prizes!')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });


    //assert that the modal appears
    cy.get('#shareModal', { timeout: 1000}).should('be.visible')
        .within(() =>{
          // check if the #facebook element has the correct href
          cy.get("#facebook")
              .should('have.attr', 'href')
              .then((href) => {
                const frontendUrl = Cypress.env('FRONTEND_URL'); // Get from Cypress env
                //const expectedEventUrl = encodeURIComponent(`${frontendUrl}/event/81`);
                const expectedEventUrl = `${frontendUrl}/event/83`;
                // TODO morgain HERE! might be 83

                // Verify the generated URL contains the expected event page link
                expect(href).to.include('https://www.facebook.com/sharer/sharer.php');
                expect(href).to.include(`u=http://${expectedEventUrl}`); // Check the event link
              });
        });
  })

  it("Should generate a link for X that leads to the individual link's event details page", () =>{
    // click on the share button on the Event with the title 'Karaoke contest with prizes!'
    cy.contains('#eventFeed', 'Karaoke contest with prizes!')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });

    //assert that the modal appears
    cy.get('#shareModal', { timeout: 1000}).should('be.visible')
        .within(() =>{
          //cy.wait(500)
          // check if the #x element has the correct href
          cy.get("#x", { timeout: 10000 })
              .should('have.attr', 'href')
              .then((href) => {
                const frontendUrl = Cypress.env('FRONTEND_URL'); // Get from Cypress env
                const expectedText = encodeURIComponent(`Check out this event!`);
                const expectedUrl = encodeURIComponent(`http://${frontendUrl}/event/83`)

                // Verify the generated URL contains the expected event page link
                expect(href).to.include('https://twitter.com/intent/tweet');
                expect(href).to.include(`text=${expectedText}`);
                expect(href).to.include(`url=${expectedUrl}`);
              });
        });
  })

  it("Should copy the event link to the clipboard", () => {
    // Open the share modal by clicking the share button on the event
    cy.contains('#eventFeed', 'Karaoke contest with prizes!')
        .within(() => {
          cy.get('.shareButton').click();
        });

    // Ensure the share modal is visible, then click the copy button
    cy.get('#shareModal', { timeout: 1000}).should('be.visible')
        .within(() => {
          //cy.wait(500)
          cy.get('#copyLink', { timeout: 10000 }).click();
        });

    // Construct the expected event URL using the FRONTEND_URL from Cypress environment
    const frontendUrl = Cypress.env('FRONTEND_URL');
    const expectedLink = `http://${frontendUrl}/event/83`;

    // Read the clipboard text, verify it, and then navigate to that link
    cy.window().then((win) => {
      return win.navigator.clipboard.readText();
    }).then((copiedLink) => {
      // Assert that the copied link matches the expected event URL
      expect(copiedLink).to.eq(expectedLink);

      // Visit the copied link to test navigation to the event details page
      cy.visit(copiedLink);
    });

    //cy.wait(1000)
    // Once on the event details page, assert that it loads correctly
    cy.contains('Karaoke contest with prizes!', { timeout: 10000 });
  });
})
//End of tests for sharing via social media

// Beginning of Story 48 - Registered User Shares Event via Email and Text TEST
// SMS Link Testing
describe('Share Event Text Message Test', () => {
    it('resets the database and reload its data for consistent data', () => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    });

  it('verifies the sms link for the event titled: Karaoke contest with prizes!', () => {
      cy.visit('/'); // Visit the app

    // Click on the share button on the Event with the title 'Karaoke contest with prizes!'
    cy.contains('#eventFeed', 'Karaoke contest with prizes!')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });

    cy.get('.shareModal').should('be.visible');
    const expectedSmsLink = `SMS:%2B%20?body=Check%20out%20this%20awesome%20event%20happening%20in%20the%20city%F0%9F%8E%89%3A%20Karaoke%20contest%20with%20prizes!.%20You%20can%20find%20more%20details%20here%3A%20http%3A%2F%2Flocalhost%3A5173%2Fevent%2F83`
    cy.get('#smsLink').should('have.attr', 'href', expectedSmsLink)

      cy.get('#smsLink').trigger('click');    // Fake click the Text Message label so we can check if Sharing Modal closes after the clicking
      cy.get('.shareModal').should('not.exist'); // Sharing modal should be closed
  });

  it('verifies the sms link for the event titled: Steak night for couples.', () => {
      cy.visit('/'); // Visit the app

    // Click on the share button on the Event with the title 'Karaoke contest with prizes!'
    cy.contains('#eventFeed', 'Steak night for couples.')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });

    cy.get('.shareModal').should('be.visible');
    const expectedSmsLink = `SMS:%2B%20?body=Check%20out%20this%20awesome%20event%20happening%20in%20the%20city%F0%9F%8E%89%3A%20Steak%20night%20for%20couples..%20You%20can%20find%20more%20details%20here%3A%20http%3A%2F%2Flocalhost%3A5173%2Fevent%2F84`
    cy.get('#smsLink').should('have.attr', 'href', expectedSmsLink)

      cy.get('#smsLink').trigger('click');    // Fake click the Text Message label so we can check if Sharing Modal closes after the clicking
      cy.get('.shareModal').should('not.exist'); // Sharing modal should be closed
  });
});

// Email Link testing
describe('Share Event Email Test', () => {
    it('resets the database and reload its data for consistent data', () => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    });

  it('verifies the email link for the event titled: Karaoke contest with prizes!', () => {
      cy.visit('/'); // Visit the app

    // Click on the share button on the Event with the title 'Karaoke contest with prizes!'
    cy.contains('#eventFeed', 'Karaoke contest with prizes!')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });

    cy.get('.shareModal').should('be.visible');
    const expectedEmailLink = `mailto:?subject=Checkout%20this%20City%20Event%F0%9F%8E%89%3A%20Karaoke%20contest%20with%20prizes!&body=Hi%20there!%0A%0ALocation%3A%20Saskatoon%0ADate%3A%202024-01-01%20-%202026-03-01%0ADescription%3A%20Description%20D%0A%0AFor%20more%20details%20and%20to%20get%20all%20the%20information%20you%20need%2C%20click%20here%3A%20http%3A%2F%2Flocalhost%3A5173%2Fevent%2F83%0A%0ABest%20regards%2C`
    cy.get('#emailLink').should('have.attr', 'href', expectedEmailLink)

      cy.get('#emailLink').trigger('click');  // Fake click the Email label so we can check if Sharing Modal closes after the clicking
      cy.get('.shareModal').should('not.exist'); // Sharing modal should be closed
  });

  it('verifies the email link for the event titled: Steak night for couples.', () => {
      cy.visit('/'); // Visit the app

    // Click on the share button on the Event with the title 'Karaoke contest with prizes!'
    cy.contains('#eventFeed', 'Steak night for couples.')
        .within(() => {
          cy.get('.shareButton').click(); // Click the share button inside this event
        });

    cy.get('.shareModal').should('be.visible');
    const expectedEmailLink = `mailto:?subject=Checkout%20this%20City%20Event%F0%9F%8E%89%3A%20Steak%20night%20for%20couples.&body=Hi%20there!%0A%0ALocation%3A%20Regina%0ADate%3A%202024-01-01%20-%202026-03-05%0ADescription%3A%20Description%20E%0A%0AFor%20more%20details%20and%20to%20get%20all%20the%20information%20you%20need%2C%20click%20here%3A%20http%3A%2F%2Flocalhost%3A5173%2Fevent%2F84%0A%0ABest%20regards%2C`
    cy.get('#emailLink').should('have.attr', 'href', expectedEmailLink)

      cy.get('#emailLink').trigger('click');  // Fake click the Email label so we can check if Sharing Modal closes after the clicking
      cy.get('.shareModal').should('not.exist'); // Sharing modal should be closed
  });
});
// End of Story 48 - Registered User Shares Event via Email and Text TEST



// Story 50 - Registered User Creates Recurring Event Series (deleting event/series)
describe('Registered user deletes an instance in the series and a series', () => {
    it('deletes an instance and a series and check the Upcoming Events tabs and Past Events tab for confirmation that the deletion succeeded', () => {
        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/')
        cy.visit('/myevents') // Go to My Events page
        cy.scrollTo('bottom')

        // There should be 3 events titled, 'Delete Series Test Event With a Past Event'.
        cy.get('.eventTitle:contains("Delete Series Test Event With a Past Event")').should('be.visible').should('have.length', 3)
        // Delete the first instance
        cy.get('.deleteButton').eq(4).click();    // Click the trash icon
        cy.get('#deleteEventDeleteEventModal').click(); // Click the Delete Event button
        cy.get('#proceedConfirmationModal').click();    // Click the Proceed button
        cy.wait(6000)
        cy.scrollTo('bottom')
        // There should be 2 events titled, 'Delete Series Test Event With a Past Event'.
        cy.get('.eventTitle:contains("Delete Series Test Event With a Past Event")').should('be.visible').should('have.length', 2)

        // Delete the series
        cy.get('.deleteButton').eq(5).click();    // Click the trash icon
        cy.get('#deleteSeriesDeleteEventModal').click();// Click the Delete Series button
        cy.get('#proceedConfirmationModal').click();    // Click the Proceed button
        cy.wait(6000)
        cy.scrollTo('bottom')
        // There should be 0 events titled, 'Delete Series Test Event With a Past Event'.
        cy.get('.eventTitle:contains("Delete Series Test Event With a Past Event")').should('not.exist')
        // Check that the event titled, 'Delete Series Test Event With a Past Event', in the Past Events tab has not disappeared
        cy.get('.selectButton').click();
        cy.get('.eventTitle:contains("Delete Series Test Event With a Past Event")').should('be.visible').should('have.length', 1)
    })
})

describe('Registered user deletes a series without a past event', () => {
    it('deletes a series and check that the deletion succeeded', () => {
        // Log-in as a registered user
        cy.visit('/signin')                               // Go to sign in page
        cy.get('#identifier').type('username1')  // Type valid registered user username
        cy.get('#password').type('@Password1')   // Type valid registered user password
        cy.get('button[type="submit"]').click()       // Submit
        cy.url().should('eq', 'http://localhost:5173/')

        cy.visit('/myevents') // Go to My Events page
        cy.scrollTo('bottom')

        // There should be events titled, 'Delete Series Test Event Without a Past Event'.
        cy.get('.eventTitle:contains("Delete Series Test Event Without a Past Event")').should('be.visible').should('have.length', 2)
        // Delete the series
        cy.get('.deleteButton').eq(3).click();    // Click the trash icon
        cy.get('#deleteSeriesDeleteEventModal').click();// Click the Delete Series button
        cy.get('#proceedConfirmationModal').click();    // Click the Proceed button
        // There should be 0 events titled, 'Delete Series Test Event Without a Past Event'.
        cy.wait(6000)
        cy.scrollTo('bottom')
        cy.get('.eventTitle:contains("Delete Series Test Event Without a Past Event")').should('not.exist')
    })
})
// End Story 50 - Registered User Creates Recurring Event Series (deleting event/series)



//REGION
// (Rayne) Story 57 - Premium user exports event to calendar

describe(('Export Event To Google Calendar'), () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures EventWithMedia AppUserFixture PremiumUserFixture HistoricalEventFixture')
    cy.visit('/')
  })
  beforeEach(() => {
    cy.visit('/');
  })
  it('requires that a user must be premium to export an event', () => {
    // show that button is not visible when a guest
    // expand event first so that the export button is visible
    cy.get('.event-item .eventButton').eq(0).click()
    cy.get('.event-item').get('#exportEvent').should('not.exist');
    // not an option when signed in as a registered account
    loginHelper('username1', '@Password1');
    cy.visit('/');
    cy.get('.event-item .eventButton').eq(0).click()
    cy.get('.event-item').get('#exportEvent').should('not.exist');
    cy.get('#signInOut').click(); // sign out
    // show that it is visible when they are premium
    loginHelper('premium', 'ABC123def');
    cy.visit('/');
    cy.get('.event-item .eventButton').eq(0).click()
    cy.get('.event-item').get('#exportEvent').should('exist');
  })

  it('should only be able to export events that are current', () => {
    loginHelper('premium', 'ABC123def');

    cy.visit('/historic')
    // this event should not have an export button because it is not current
    cy.get('.event-item .eventButton').eq(0).click()
    cy.get('.event-item').get('#exportEvent').should('not.exist');

    cy.visit('/');
    // this one will have an export button because it is current
    cy.get('.event-item .eventButton').eq(1).click()
    cy.get('.event-item').get('#exportEvent').should('exist');
  })

  /*it('export an event given the google credentials', () => {

    loginHelper('premium', 'ABC123def');
    cy.visit('/');

    cy.get('.event-item .eventButton').eq(1).click()
    cy.get('.event-item .eventTitle').eq(1).should('have.text', 'Dance Competition').get('#exportEvent').click();

   /!* // mock choosing one-tap account or signing in
    // intercept post request to backend to verify the account credentials
    cy.intercept('POST', '/auth/verify-creds', {
      statusCode: 200,
      body: {
        token: 'fake-jwt-token',
        userId: '123',
        username: 'Zuevents',
        scope: ''
      }
    }).as('verifyCreds')

    // should be properly verified
    cy.wait('@verifyCreds').its('response.statusCode').should('equal', 200)*!/

    cy.intercept('POST', 'https://content.googleapis.com/calendar/v3/calendars/primary/events?alt=json', {
      statusCode: 200,
      body: {
        summary: "Dance Competition",
        location: "Lloydminster",
        description: "Dance till you drop at this annual competition!",
        start: {
          dateTime: "2025-08-06T18:00:00.000Z"
        },
        end: {
          dateTime: "2025-08-07T01:00:00.000Z"
        },
        source: {
          title: 'Motodo',
          url: "http://localhost:5173/event/8939"
        },
        eventType: 'default'
      }
    }).as('sendCalendarExport');

    cy.wait('@sendCalendarExport').its('response.statusCode').should('equal', 200);
  })*/

  /*it('properly formats the request sent to the Google Calendar API', () => {
    loginHelper('premium', 'ABC123def');
    cy.visit('/');



    /!*!// mock choosing one-tap account or signing in
    // intercept post request to backend to verify the account credentials
    cy.intercept('POST', '/auth/verify-creds', {
      statusCode: 200,
      body: {
        token: 'fake-jwt-token',
        userId: '123',
        username: 'Zuevents',
        scope: ''
      }
    }).as('verifyCreds')

    // should be properly verified
    cy.wait('@verifyCreds').its('response.statusCode').should('equal', 200)*!/

    cy.intercept('POST', 'https://content.googleapis.com/calendar/v3/calendars/primary/events?alt=json', {
      statusCode: 200,
      body: {
        summary: "Dance Competition",
        location: "Lloydminster",
        description: "Dance till you drop at this annual competition!",
        start: {
          dateTime: "2025-08-06T18:00:00.000Z"
        },
        end: {
          dateTime: "2025-08-07T01:00:00.000Z"
        },
        source: {
          title: 'Motodo',
          url: "http://localhost:5173/event/8939"
        },
        eventType: 'default'
      }
    }).as('sendCalendarExport');

    cy.intercept('GET', 'https://apis.google.com/js/!*', {
      statusCode: 302
    }).as('chooseAccount')




    cy.get('.event-item .eventButton').eq(1).click()
    cy.get('.event-item .eventTitle').eq(1).should('have.text', 'Dance Competition').get('#exportEvent').click();

    cy.wait('@chooseAccount')

    cy.wait(5000);

    //cy.intercept('POST', 'https://content.googleapis.com/calendar/v3/calendars/zuevents@gmail.com/events').as('sendCalendarExport');

    cy.wait('@sendCalendarExport').its('request.body').should('include', 'location').should('have.text', 'Lloydminster');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'end.dateTime').should('have.text', '2025-08-07T01:00:00.000Z');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'start.dateTime').should('have.text', '2025-08-06T18:00:00.000Z');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'eventType').should('have.text', 'default');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'source.url').should('have.text', 'http://localhost:5173/event/8939');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'summary').should('have.text', 'Dance Competition');


    // check that this also works for events with no specified end time
    cy.get('.event-item').should('have.text', 'Flea Market').get('#exportEvent').click();

    cy.wait('@sendCalendarExport').its('request.body').should('include', 'location').should('have.text', 'Prince Albert');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'endTimeUnspecified').should('have.text', 'true');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'start.dateTime').should('have.text', '2025-06-08 12:00:00');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'eventType').should('have.text', 'default');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'kind').should('have.text', 'calendar#event');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'source.url').should('have.text', 'https://localhost:5173/events/39flea+market');
    cy.wait('@sendCalendarExport').its('request.body').should('include', 'summary').should('have.text', 'Flea Market');
  })*/

  /*it('displays a confirmation message to the user after an event is exported', () => {
    // successful export
    loginHelper('premium', 'ABC123def');
    cy.visit('/');

    /!*cy.get('.event-item').should('have.text', 'Dance Competition').get('#exportEvent').click();

    // mock choosing one-tap account or signing in
    // intercept post request to backend to verify the account credentials
    cy.intercept('POST', '/auth/verify-creds', {
      statusCode: 200,
      body: {
        token: 'fake-jwt-token',
        userId: '123',
        username: 'Zuevents',
        scope: ''
      }
    }).as('verifyCreds')*!/

    cy.request({
      url: 'https://content.googleapis.com/calendar/v3/calendars/primary/events?alt=json',
      method: 'POST',
      headers: {Authorization : 'Bearer ya29.a0AZYkNZgMeK-L6mxw_QIH5mQspiz83dk6ozk5njZsR5URiyOap_GZmnZqLFbP5LcWPP_' +
          'EGbFqJq9BR4OBz1ZvxDTDeOs5gW0lRTlyP88YhSB534kB7K4DSxuzmeOA4lZi2LXF3I4uGsUO_tdlr6et138moZYGt3aiR0JSNrx5VwaCgY' +
          'KAecSARMSFQHGX2Mi-0senwX8Nl7spiwoGcROIw0177'},
      body: {
        summary: "Dance Competition",
        location: "Lloydminster",
        description: "Dance till you drop in this annual competition!",
        start: {
          dateTime: "2025-08-06T18:00:00.000Z"
        },
        end: {
          dateTime: "2025-08-07T01:00:00.000Z"
        },
        source: {
          title: 'Motodo',
          url: "http://localhost:5173/event/8939"
        },
        eventType: 'default'
      }
    }).then((response) => {
      expect(response.body).to.have.property('kind', 'calendar#event')
      expect(response.body).to.have.property('status', 'confirmed')
      expect(response.body).to.have.property('description', 'Dance till you drop in this annual competition!')
      expect(response.body).to.have.property('location', 'Lloydminster')
      expect(response.body).to.have.property('organizer').to.have.property('email', 'zueventsproject@gmail.com')

    })

    /!*cy.intercept('POST', 'https://content.googleapis.com/calendar/v3/calendars/primary/events?alt=json', (req) => {
      req.body = {
        summary: "Dance Competition",
        location: "Lloydminster",
        description: "Dance till you drop at this annual competition!",
        start: {
          dateTime: "2025-08-06T18:00:00.000Z"
        },
        end: {
          dateTime: "2025-08-07T01:00:00.000Z"
        },
        source: {
          title: 'Motodo',
          url: "http://localhost:5173/event/8939"
        },
        eventType: 'default'
      }
    }).as('verifyEvent');

    // should be properly verified
    cy.wait('@verifyEvent').its('response.statusCode').should('equal', 200)
    cy.wait('@verifyEvent').its('response.kind').should('contain.text', 'calendar#event')
    cy.wait('@verifyEvent').its('response.status').should('contain.text', 'confirmed')
    cy.wait('@verifyEvent').its('response.description').should('contain.text', 'Dance till you drop in this annual competition!')
    cy.wait('@verifyEvent').its('response.location').should('contain.text', 'Lloydminster')
    cy.wait('@verifyEvent').its('response.organizer.email').should('contain.text', 'zueventsproject@gmail.com')*!/

    //now check that the toast message is visible
    cy.get('#globalToast').should('have.text', 'The event was successfully exported to your Google Calendar.');

    // unsuccessful export
    cy.visit('/');

    /!*cy.get('.event-item').should('have.text', 'Flea Market').get('.calendar-export').click();*!/

    // this will fail because the start dateTime is invalid
    cy.intercept('POST', 'https://content.googleapis.com/calendar/v3/calendars/primary/events?alt=json', {
      body: {
        summary: "Dance Competition",
        location: "Lloydminster",
        description: "Dance till you drop at this annual competition!",
        start: {
          dateTime: "2025-08-06"
        },
        end: {
          dateTime: "2025-08-07T01:00:00.000Z"
        },
        source: {
          title: 'Motodo',
          url: "http://localhost:5173/event/8939"
        },
        eventType: 'default'
      }
    }).as('verifyBadExport')

    // should be properly verified
    cy.wait('@verifyBadExport').its('response.statusCode').should('equal', 400)

    //now check that the toast message is visible
    cy.get('#globalToast').should('contain', 'Unable to export the event to your Google Calendar at this time. Please try again later.');
  })*/
})
