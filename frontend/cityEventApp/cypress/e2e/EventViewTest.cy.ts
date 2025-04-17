// Zac's tests for viewing images in the event view list
import {getAllCategories} from "../../src/components/interfaces/Category";
import { loginHelper } from './LoginHelper.ts';

describe('VisitHomePage with no events in database', () => {
  it('visit the root and should show a message that there are no events posted', () => {

    // set up the intercept
    cy.intercept('GET', '/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventStartDate&sortOrder=ASC', {
      statusCode: 200,
      body: []
    }).as('getEvents')

    // go to the page that makes the request
    cy.visit("/")

    // wait for the intercepted request
    cy.wait('@getEvents')

    cy.contains('h2','Sorry, There are currently no events posted')

  })
})
// 'http://127.0.0.1:8001/events?offset=0&limit=20'
describe('VisitHomePage with many events in database', () => {

  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });

  it('visit the root and should show a scroll bar and when you scroll, the next few events are shown', () => {
    //visit the home page
    cy.visit("/")
    cy.get('.eventContainer .event', { timeout: 10000 })
        .should('have.length.at.least', 20);

    // wait for the initial events
    //cy.wait('@initialEvents');

    // test to see if the first 20 are displayed
    cy.get('.event-item').should('have.length', 20);

    // scroll down
    cy.scrollTo('bottom');
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000);
    cy.scrollTo('bottom');
    cy.get('.eventContainer .event', { timeout: 20000 })
        .should('have.length.at.least', 40);

    // scroll down
    cy.scrollTo('bottom');
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(1000);
    cy.get('.eventContainer .event', { timeout: 10000 })
        .should('have.length.at.least', 40);

  })
})

// Look intercept method
// Test specifics of HTML
// test infinite scroll
// only load in a small amount,

// end of zac's tests


describe('Event Sorting', () => {
  const backendURL = Cypress.env('API_BASE_URL');
  // load in the appropriate fixtures
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures EventFixtureTenEvents')
  });
  beforeEach(() => {
    cy.visit('/');
  });
  after(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });


  it('should have clickable column headers that trigger sorting', () => {
    cy.intercept('/events/media?*').as('getMedia')
    // Test Start Date sorting
    cy.get('div.sortStartDate').contains('Start Date').should('exist').click();
    // have to wait for the api calls to finish
    cy.intercept('/events/media?*').as('getMedia')
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000);
    //cy.wait('@getMedia');

    cy.get('.sortStartDate', { timeout: 10000 }).contains('Start Date').find('i').should('have.class', 'bi-caret-down-fill');
    cy.get('div.sortStartDate').contains('Start Date').click();
    cy.intercept('/events/media?*').as('getMedia')
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
   //cy.wait('@getMedia');')
    cy.get('div.sortStartDate', { timeout: 10000 }).contains('Start Date').find('i').should('have.class', 'bi-caret-up-fill');

    // Test Title sorting
    cy.get('div.sortTitle').contains('Title').should('exist').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
   //cy.wait('@getMedia');')
    cy.get('div.sortTitle', { timeout: 10000 }).contains('Title').find('i').should('have.class', 'bi-caret-down-fill');
    cy.get('div.sortTitle').contains('Title').should('exist').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
    cy.intercept('/events/media?*').as('getMedia')
   //cy.wait('@getMedia');')
    cy.get('div.sortTitle', { timeout: 10000 }).contains('Title').find('i').should('have.class', 'bi-caret-up-fill');

    // Test Location sorting
    cy.get('div.sortLocation').contains('Location').should('exist').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
   //cy.wait('@getMedia');')
    cy.get('div.sortLocation', { timeout: 10000 }).contains('Location').find('i').should('have.class', 'bi-caret-down-fill');
    cy.get('div.sortLocation').contains('Location').should('exist').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
    cy.intercept('/events/media?*').as('getMedia')
   //cy.wait('@getMedia');')

    cy.get('div.sortLocation', { timeout: 10000 }).contains('Location').find('i').should('have.class', 'bi-caret-up-fill');
  });

  it('should display correct sort indicators and only one at a time', () => {
    cy.intercept('/events/media?*').as('getMedia');
    // Click Start Date and verify indicator
    cy.get('div.sortStartDate').contains('Start Date').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
    cy.get('div.sortStartDate', { timeout: 10000 }).contains('Start Date').find('i').should('have.class', 'bi-caret-down-fill');
    cy.get('div.sortTitle').contains('Title').find('i').should('have.class', 'bi-arrow-down-up');
    cy.get('div.sortLocation').contains('Location').find('i').should('have.class', 'bi-arrow-down-up');

    // Click Title and verify indicator moves
    cy.get('div.sortTitle').contains('Title').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000)
   //cy.wait('@getMedia');')
    cy.get('div.sortTitle', { timeout: 10000 }).contains('Title').find('i').should('have.class', 'bi-caret-down-fill');
    cy.get('div.sortStartDate').contains('Start Date').find('i').should('have.class', 'bi-arrow-down-up');
    cy.get('div.sortLocation').contains('Location').find('i').should('have.class', 'bi-arrow-down-up');
  });



  it('should sort events by date correctly', () => {
    // Test ascending order
    cy.get('div.sortStartDate').contains('Start Date').as('dateHeader');

    // Click twice for ascending, with waiting between clicks
    cy.get('@dateHeader').click();
    // Wait for re-render
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('@dateHeader').click();

    // Wait for table to update and verify first date
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventStartDate').should('contain', '2026-01-04');

    // Test descending order
    cy.get('@dateHeader').click();

    // Wait for table to update and verify first date
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventStartDate').should('contain', '2026-01-12');
  });

  it('should sort events by title correctly', () => {
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000);
    // Set up route interceptors
    //cy.intercept('GET', '**!/events?*category=title&order=asc*').as('titleAscRequest');
    //cy.intercept('GET', '**!/events?*category=title&order=desc*').as('titleDescRequest');
    cy.intercept('GET', backendURL+'/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventTitle&sortOrder=ASC').as('titleAscRequest');
    cy.intercept('GET', backendURL+'/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventTitle&sortOrder=DESC').as('titleDescRequest');

    // Test ascending order
    cy.get('div.sortTitle', { timeout: 10000 }).contains('Title').as('titleHeader');
    cy.get('@titleHeader').click();
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('@titleHeader').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(2000);
    // Wait for request and verify ascending order
    cy.wait('@titleAscRequest').its('response.statusCode').should('eq', 200);
    cy.get('div.eventContainer div.event .event-item').should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventTitle').should('contain', 'Test 1');

    // Test descending order
    cy.get('@titleHeader').click();

    cy.wait('@titleDescRequest', { timeout: 10000 }).its('response.statusCode').should('eq', 200);
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventTitle').should('contain', 'Test 9');
  });

  it('should sort events by location correctly', () => {
    // Set up route interceptors
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000);
    cy.intercept('GET', backendURL+'/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventLocation&sortOrder=ASC').as('locationAscRequest');
    cy.intercept('GET', backendURL+'/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventLocation&sortOrder=DESC').as('locationDescRequest');

    // Test ascending order
    cy.get('div.sortLocation').contains('Location').as('locationHeader');
    cy.get('@locationHeader').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.get('div.eventContainer div.event .event-item', { timeout: 10000 }).should('exist');
    cy.get('@locationHeader').click();

    // Wait for request and verify ascending order
    cy.wait('@locationAscRequest').its('response.statusCode').should('eq', 200);
    cy.get('div.eventContainer div.event .event-item').should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventLocation').should('contain', 'Regina');

    // Test descending order
    cy.get('@locationHeader').click();
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    //cy.wait(5000);
    cy.wait('@locationDescRequest').its('response.statusCode').should('eq', 200);
    cy.get('div.eventContainer div.event .event-item').should('exist');
    cy.get('div.eventContainer div.event .event-item').first().find('.eventLocation').should('contain', 'Warman');
  });

});
// end of kim's tests

//region Story 34 - Chau

describe('Filter Events by Clicking Category Component', () => {
  // load in the appropriate fixtures
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  });

  beforeEach(() => {
    cy.visit('/'); // Visit the event page
    cy.get('.eventContainer .event', { timeout: 15000 })
        .should('have.length.at.least', 20);
    cy.scrollTo('bottom'); // Scroll to the bottom

    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000);

    cy.scrollTo('bottom'); // again just to make sure it actually reaches the bottom
    cy.get('.eventContainer .event', { timeout: 100000 })
        .should('have.length.at.least', 40);
  });

  const category = getAllCategories()[0]; // only pick the first category

  it(`should filter events by clicking "${category.name}" category`, () => {
    const categoryTag = `category-tag-${category.name.toLowerCase().replace(/\s/g, '-')}`;
    const categoryQueryParam = category.name.replace(/\s/g, '+');

    // Find the first event that contains the selected category tag and click it
    cy.get('.eventContainer .event').filter((index, event) => {
      return Cypress.$(event).find(`[data-cy=${categoryTag}]`).length > 0;
    }).first().within(() => {
      cy.get(`[data-cy=${categoryTag}]`).first().click();
    });

    // Test that the URL update according to the category selected
    cy.url().should('include', `category=${categoryQueryParam}`);
    cy.intercept('/events/media?*').as('getMedia');
    //cy.wait('@getMedia');

    // Wait dynamically until at least 20 events load
    cy.get('.eventContainer .event', { timeout: 20000 })
        .should('have.length.at.least', 20);

    // Assert all displayed events belong to the selected category
    cy.get('.eventContainer .event').each(($event) => {
      cy.wrap($event).within(() => {
        // Verify the category tag matches the selected category
        cy.get('[data-cy^=category-tag]')
            .should('have.attr', 'data-cy', categoryTag);

        // Verify the icon
        cy.get('[data-cy^=event-category-icon]')
            .should('have.class', category.icon.replace('pi ', ''));

        // Verify the background color
        cy.get('[data-cy^=category-tag]')
            .should('have.css', 'background-color', category.bgColorTesting);
      });
    });
  });
});




describe('Filter Events by Clicking Category Component', () => {
  // load in the appropriate fixtures
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  });
  beforeEach(() => {
    cy.visit('/'); // Visit the event page

    cy.get('div.sortTitle', { timeout: 10000 }).contains('Title').as('titleHeader');
    cy.get('@titleHeader').click();

    cy.get('.eventContainer .event', { timeout: 15000 })
        .should('have.length.at.least', 20);
    cy.scrollTo('bottom'); // Scroll to the bottom

    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000);

    cy.scrollTo('bottom'); // again just to make sure it actually reaches the bottom
    cy.get('.eventContainer .event', { timeout: 100000 })
        .should('have.length.at.least', 40);
  });


  const categories = getAllCategories();

  // Iterate over each category and create a test case
  categories.forEach((category) => {
    it(`should filter events by clicking "${category.name}" category`, () => {

      const categoryTag = `category-tag-${category.name.toLowerCase().replace(/\s/g, '-')}`;
      const categoryQueryParam = category.name.replace(/\s/g, '+'); // Convert spaces to "+"

      if (categoryTag == 'category-tag-education') {
        cy.get('#searchEvents input[type="text"]').type('Event Title 622').type('{enter}')
      }

      if (categoryTag == 'category-tag-nature-and-outdoors') {
        cy.get('#searchEvents input[type="text"]').type('nature').type('{enter}')
      }

      // Find the first event that contains the selected category tag and click it
      cy.get('.eventContainer .event').filter((index, event) => {
        return Cypress.$(event).find(`[data-cy=${categoryTag}]`).length > 0;
      }).first().within(() => {
        cy.get(`[data-cy=${categoryTag}]`).first().click();
      });

      cy.get('@titleHeader').click();

      // Test that the URL update according to the category selected
      cy.url().should('include', `category=${categoryQueryParam}`,{ timeout: 20000 });
      cy.intercept('/events/media?*').as('getMedia');
      //cy.wait('@getMedia');

      // Wait dynamically until at least 20 events load OR timeout after 1000ms
      cy.get('.eventContainer .event', { timeout: 20000 })
          .should('have.length.at.least', 20);

      // Assert all displayed events belong to the selected category
      cy.get('.eventContainer .event').each(($event) => {
        cy.wrap($event).within(() => {
          // Verify the category tag matches the selected category
          cy.contains('[data-cy^=category-tag]', category.name)
          //cy.get('[data-cy^=category-tag]')
              .should('have.attr', 'data-cy', categoryTag);

          // Verify the icon
          cy.get('[data-cy^=event-category-icon]')
              .should('have.class', category.icon.replace('pi ', '')); // Remove 'pi ' prefix for class comparison

          // Verify the background color
          cy.get(`[data-cy=${categoryTag}]`)
              .should('have.css', 'background-color', category.bgColorTesting)
        });
      });
    });
  });
});



// Story 60 Tests
describe("Efficient Fetching Tests", () => {
  beforeEach(() => {

    Cypress.on('uncaught:exception', (err) => {
      console.error('Unhandled Exception:', err);
      return false;
    });
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures EventFixtures')
    // Set up intercepts to track requests that were made

    cy.intercept('GET', '/api/subscription/user2', {
      statusCode: 200,
      body: [
        {
          message: 'No subscription for this user',
          isPremium: false,
          expireDate: null
        }]
    }).as('getSubscription')

    // set up intercepts to track if any unexpected calls for media are made
    cy.intercept('GET', '/events/media?eventID=*', (req) => {
      assert.fail("Unexpected media fetch request");
    }).as('unexpectedMediaRequest')

    // set up intercepts to track if any unexpected calls for bookmarks are made
    cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
      assert.fail("Unexpected Bookmark fetch request");
    }).as('unexpectedBookmarkRequest')
  })

  it('Should only make requests for getting events and getting the user\'s subscription status', () => {
    // need to sign in first
    cy.visit('/signin');
    cy.get('#identifier').type("user2");
    cy.get('#password').type("@Password1");
    cy.get('button[type="submit"]').click();

    // check that for valid calls that they were made
    //cy.get('@getEvents.all').should('have.length', 1);
    cy.get('@getSubscription.all').should('have.length', 1);

    // Wait for events to finish fetching

    cy.get('.imageThumbnail')
        .should('have.attr', 'src')
        .and('equal', 'http://127.0.0.1:8001/uploads/p1.jpg');

    cy.wait(500);

    cy.get('.bookmarkButton').should('be.visible');
    cy.get('.event-item .bookmarkButton span.p-button-icon').should('have.class', 'pi pi-bookmark-fill');

    //wait for any unexpected requests, if there are any
    cy.wait(2000);
  })
})
//endregion

//region cedric
// Story 40 - Moderator managed reported events
describe('Reported Event is Removed from the Event List After Being Reported for Three Times', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures EventFixtureTenEvents ModeratorFixture')
  })
  beforeEach(() => {
    cy.visit('http://localhost:5173/');
  })
  it('signs in using a moderator account', () => {
    loginHelper('Moderator', 'ABC123def')
    //cy.wait(2000);
    cy.url().should('eq', 'http://localhost:5173/');
  })

  it('reports an event three times', () => {
    // 1st report

    cy.get('div.eventContainer div.event .event-item').eq(4).find('.report-button').click(); // Event title: Test 5
    cy.get('#reason-Spam').click();                                 // Select a reason
    cy.get('#submitButton').click();
    //cy.wait('@SuccessfulSubmit');                         // Wait for the mocked API call to complete
    cy.get('.success-message').should('exist'); // Verify a success message is displayed
    cy.get('#okButton').click();                       // Click the OK button to close the Report form

    // 2nd report
    cy.get('div.eventContainer div.event .event-item').eq(4).find('.report-button').click(); // Event title: Test 5
    cy.get('#reason-FalseInformation').click();                    // Select a reason
    cy.get('#submitButton').click();
    //cy.wait('@SuccessfulSubmit');                         // Wait for the mocked API call to complete
    cy.get('.success-message').should('exist'); // Verify a success message is displayed
    cy.get('#okButton').click();                       // Click the OK button to close the Report form

    // 3rd report
    cy.get('div.eventContainer div.event .event-item').eq(4).find('.report-button').click(); // Event title: Test 5
    cy.get('#reason-Illegalactivity').click();                     // Select a reason
    cy.get('#submitButton').click();
    //cy.wait('@SuccessfulSubmit');                         // Wait for the mocked API call to complete
    cy.get('.success-message').should('exist'); // Verify a success message is displayed
    cy.get('#okButton').click();                       // Click the OK button to close the Report form
  });

  it('checks if the event titled Test 5 still exists', () => {     // Reload
    cy.get('div.eventContainer div.event .event-item').eq(4).find('.eventTitle').should('not.have.text', 'Test 5'); // It should not exist
  })
})
//end region



//region Rayne's story 54 - theme and layout rework, add search functionality

describe('Apply Search Criteria to List of Events', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    cy.visit('/')
  })

  beforeEach(() => {
    cy.visit('/')
  })

  it('should apply the searched terms to the location field', () => {
    // click into search bar
    cy.get('#searchEvents input[type="text"]').type('lloydminster{enter}');
    // search for "Praireland"
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.wait('@getMedia')

    //enter
    //check that events appear with praireland in the location value
    // may have to wait for search results
    cy.url({ timeout: 10000 }).should('contain','http://localhost:5173/?searchString=lloydminster');
    cy.get('.event-item').should('have.length', 1);
    cy.get('.event-item .eventLocation').contains('Lloydminster');
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the title field', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('night{enter}');
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.wait('@getMedia')
    // may have to wait for search results
    cy.url().should('contain','http://localhost:5173/?searchString=night');
    cy.get('.event-item').should('have.length', 1);
    cy.get('.event-item .eventTitle').contains('night')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the category', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('Arts and Culture{enter}');
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.wait('@getMedia')
    // may have to wait for search results
    cy.url().should('contain','http://localhost:5173/?searchString=Arts+and+Culture');
    cy.get('.event-item').should('have.length', 20);
    cy.get('.event-item .eventCategory').contains('Arts and Culture')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the description field', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('competition{enter}');
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.wait('@getMedia')
    // may have to wait for search results
    cy.url().should('contain','http://localhost:5173/?searchString=competition');
    cy.get('.event-item').should('have.length', 2);
    //cy.wait('@getMedia')
    cy.get('.event-item .eventButton').first().click()
    cy.get('.event-item .eventDescription').contains('competition')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should display message when no events match the search criteria', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('haunted house{enter}');
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.wait('@getMedia')

    // may have to wait for search results
    cy.url({ timeout: 10000 }).should('contain','http://localhost:5173/?searchString=haunted+house');
    cy.get('.event-item').should('have.length', 0);
    cy.get('h2').contains('There are no events that match the given search criteria.');
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should clear other filters when search criteria is applied', () => {
    // click on a category tag
    cy.get('.eventContainer .event').filter((index, event) => {
      return Cypress.$(event).find(`[data-cy=category-tag-arts-and-culture]`).length > 0;
    }).first().within(() => {
      cy.get(`.p-tag [data-cy=category-tag-arts-and-culture]`).click();
    });
    // so now it is being filtered by a category

    // the url should change
    cy.url().should('contain','http://localhost:5173/?category=Arts+and+Culture');

    cy.get('#searchEvents input[type="text"]').type('Praireland{enter}');
    cy.url().should('contain','http://localhost:5173/?searchString=Praireland');

    // check that the current active filter display morgan is making says there are none selected
    cy.get('#filterDisplayText').should('have.text', 'No filters applied');

  })

  it('should clear search criteria when other filters are applied', () => {
    // enter search criteria
    cy.get('#searchEvents input[type="text"]').type('event{enter}');
    cy.url().should('contain','http://localhost:5173/?searchString=event');

    // apply a category filter
    cy.get('.eventContainer .event').filter((index, event) => {
      return Cypress.$(event).find(`[data-cy=category-tag-others]`).length > 0;
    }).first().within(() => {
      cy.get(`.p-tag [data-cy=category-tag-others]`).click();
    });

    // so now the search should be cleared
    cy.get('input[placeholder="Search"]').should('be.empty');
    cy.url().should('not.contain', 'searchString');

  })
})


describe('Consistent Theme and Layout Throughout the App', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    cy.visit('/')
  })
  beforeEach(() => {
    cy.visit('/')
  })
  it('should not display a profile picture when acting as a guest user', () => {
    cy.get('.profilePicture').should('not.have.text', 'u');
  })

  it('should display a profile picture when signed in as a registered user', () => {
    // log in to account
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('#rememberMe').check(); // Check the 'Remember Me' checkbox
    cy.get('button[type="submit"]').click();

    cy.get('.profilePicture').should('be.visible');
    cy.get('.profilePicture').should('have.text', 'u');
  })

  it('should be able to toggle between light and dark mode', () => {
    cy.get('#themeToggler').click();

    // then check that its in dark mode
    cy.get('body').should('have.css', 'color', 'rgb(255, 255, 255)');

    // check multiple pages
    cy.get('.p-menubar-item-link').eq(3).should('have.text', 'Past Events').click();
    cy.get('body').should('have.css', 'color', 'rgb(255, 255, 255)');
    cy.get('.p-menubar-item-link').eq(1).should('have.text', 'Profile').click();
    cy.get('body').should('have.css', 'color', 'rgb(255, 255, 255)');

    // go back to light mode
    cy.get('#themeToggler').click();
    cy.get('body').should('have.css', 'color', 'rgb(0, 0, 0)');
  })

  it('should have the scroll to top option visible on the needed pages', () => {
    // while on main events page...
    cy.scrollTo('bottom');
    cy.get('.p-scrolltop').should('be.visible');
    cy.get('.p-scrolltop').click();
    cy.window().its('scrollY').should('equal', 0);
    // should also appear on my events page, past events, bookmarked events
    cy.get('.p-menubar-item-link').eq(3).should('have.text', 'Past Events').click();
    cy.scrollTo('bottom');
    cy.get('.p-scrolltop').should('be.visible');

    // log in so the other pages can be accessed
    loginHelper("username1", "@Password1");
    cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').click();
    cy.scrollTo('bottom');
    cy.get('.p-scrolltop').should('be.visible');

    cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').click();
    cy.scrollTo('bottom');
    cy.get('.p-scrolltop').should('be.visible');

  })

  it('should display navigation options in the navbar', () => {
    // before signing in, "Events", "Past Events", "Profile", "My Events", "My Bookmarks" are all visible
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').should('be.visible');
    cy.get('.p-menubar-item-link').eq(1).should('have.text', 'Profile').should('be.visible');
    cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').should('be.visible');
    cy.get('.p-menubar-item-link').eq(3).should('have.text', 'Past Events').should('be.visible');
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').should('be.visible');
    cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').should('be.visible');

    // moderator tool option is not visible
    cy.get('.p-menubar-item-link').eq(6).should('not.exist');

    // these pages should just redirect to the sign in page (profile, my events, bookmarks)
    cy.get('.p-menubar-item-link').eq(1).should('have.text', 'Profile').click();
    cy.url().should('eq', 'http://localhost:5173/signin');
    cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').click();
    cy.url().should('eq', 'http://localhost:5173/signin');
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').click();
    cy.url().should('eq', 'http://localhost:5173/signin');
    // sign in as moderator
    loginHelper('Moderator', 'ABC123def');

    cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').click();

    //now the pages go to the actual page they should
    cy.get('.p-menubar-item-link').eq(1).should('have.text', 'Profile').click();
    cy.url().should('eq', 'http://localhost:5173/profile');
    cy.get('.p-menubar-item-link').eq(2).should('have.text', 'My Events').click();
    cy.url().should('eq', 'http://localhost:5173/myevents');
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').click();
    cy.url().should('eq', 'http://localhost:5173/bookmarks');
    // moderator tools page option is also now visible
    cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').should('be.visible');
  })
})

//endregion


