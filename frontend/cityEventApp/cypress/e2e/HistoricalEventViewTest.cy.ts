const dayjs = require('dayjs')
describe('Historical Events Page E2E Tests', () => {
  /*it('should display message when there are no historical events to view', () => {
    // go to past events page
    cy.visit('/')
    cy.get('#pastevent-li').click();
    cy.get('h2').should('have.text', 'There are no past events to view');
  })*/

  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures HistoricalEventFixture')

    // set up intercepts to track if any unexpected calls for media are made
    cy.intercept('GET', '/events/media?eventID=*', (req) => {
      assert.fail("Unexpected media fetch request");
    }).as('unexpectedMediaRequest')

    // set up intercepts to track if any unexpected calls for bookmarks are made
    cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
      assert.fail("Unexpected Bookmark fetch request");
    }).as('unexpectedBookmarkRequest')
  })

  beforeEach(() => {
    // check that message displays when there are no events to view
    /*cy.visit('/')
    cy.get('#pastevent-li').click();
    cy.get('h2').should('have.text', 'There are no past events to view');*/

    //cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures HistoricalEventFixture')
    cy.visit('/')
    cy.get('.p-menubar-item-link').eq(3).should('have.text', 'Past Events').click();
    cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);
  })

  it('should only display events with a date before the current date', () => {
    // get the current date
    //const currentDate = dayjs().format('YYYY-MM-DD')

    cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')
    //cy.wait('@getMedia');
    //cy.wait('@getMedia');
    //cy.wait('@getBookmarks')
    cy
      .get('.event-item .eventStartDate', { timeout: 15000 })
      .invoke('text')
      .then(dateText => {
        const date = dateText.split('-');
        const year = date[0];
        const month = date[1];
        const day = date[2];
        const currEventDate = new Date(year, month - 1, day);
        const currDate = new Date();
        // event should be before than the current date
        expect(currEventDate).to.be.lte(currDate);
      });

  })

  it('should apply the searched terms to the location field', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('Praireland{enter}');
    //cy.get('input[placeholder="Search"]').type('Praireland').type('{enter}');
    // search for "Praireland"
    // cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')

    //check that events appear with praireland in the location value
    // may have to wait for search results
    cy.url({ timeout: 10000 }).should('contain','http://localhost:5173/historic?searchString=Praireland');
    cy.get('.event-item').should('have.length', 2);
    cy.get('.event-item .eventLocation').contains('Praireland');
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the title field', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('Concert{enter}');
    //cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')

    // may have to wait for search results
    cy.url( { timeout: 10000 }).should('contain','http://localhost:5173/historic?searchString=Concert');
    cy.get('.event-item').should('have.length', 2);
    cy.get('.event-item .eventTitle').contains('Concert')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the category', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('Arts and Culture{enter}');
    cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')
    //cy.wait('@getMedia');
    //cy.wait('@getBookmarks')
    // may have to wait for search results
    cy.url().should('contain','http://localhost:5173/historic?searchString=Arts+and+Culture');
    cy.get('.event-item').should('have.length', 5);
    cy.get('.event-item .eventCategory').contains('Arts and Culture')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should apply the searched terms to the description field', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('pet friendly{enter}');
    cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')
    //cy.wait('@getMedia')
    //cy.wait('@getBookmarks')
    // may have to wait for search results
    cy.url({ timeout: 10000 }).should('contain','http://localhost:5173/historic?searchString=pet+friendly');
    cy.get('.event-item', { timeout: 10000 }).should('have.length', 1);
    //cy.wait('@getMedia')
    cy.get('.event-item .eventButton', { timeout: 10000 }).first().click()
    cy.get('.event-item .eventDescription').contains('pet friendly')
    //clear search results
    cy.get('#clearSearch').click()
  })

  it('should display message when no events match the search criteria', () => {
    // click into search bar
    cy.get('input[placeholder="Search"]').type('haunted house{enter}');
    cy.intercept('/events/media?*').as('getMedia')
    //cy.intercept('/events/bookmarks?*').as('getBookmarks')
    //cy.wait('@getMedia')

    // may have to wait for search results
    cy.url({ timeout: 10000 }).should('contain','http://localhost:5173/historic?searchString=haunted+house');
    cy.get('.event-item').should('have.length', 0);
    cy.get('h2').contains('There are no events that match the given search criteria.');
    //clear search results
    cy.get('#clearSearch').click()
  })
})

// */
// Story 47 - Refactored Filtering (Morgan)
describe("Advanced Filtering by Multiple Filtering Properties for Histroical Events", () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures' +
        ' HistoricalEventFixture');
    cy.visit('/');
  })

  it('Single-click filters and Advanced filters on Historical View', () => {
// REGION START single-click filters work for categories
    cy.visit('/')
    cy.get('.p-menubar-item-link').eq(3).should('have.text', 'Past Events').click();
    cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);

    cy.contains(".eventCategory", "Health and Wellness", { timeout: 10000 }).should('exist');
    cy.get('#filterDisplayText').should('have.text', 'No filters applied');

    cy.get('.eventCategory .p-tag').contains("Health and Wellness").click();
    cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Health and Wellness');

    cy.get('.event-item')
        .should('have.length', 4)
        .each(($el) => {
          cy.wrap($el, { timeout: 10000 }).contains('.eventCategory', 'Health and Wellness', { timeout: 10000 });
        });

// REGION END



// REGION START single-click filters work for locations

    cy.get('.eventLocation .p-tag').contains("Saskatoon").click();

    cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Saskatoon');

    cy.get('.event-item')
        .should('have.length', 20)
        .each(($el) => {
          cy.wrap($el).contains('.eventLocation', 'Saskatoon');
        });

// REGION END


// REGION START single-click filters work for dates

    cy.get('.eventStartDate .p-tag').contains("2024-01-01").click();

    cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', '2024-01-01');

    cy.get('.event-item')
        .should('have.length', 20)
        .each(($el) => {
          cy.wrap($el).contains('.eventStartDate', '2024-01-01');
        });

// REGION END


// REGION START single-click filters work for audiences

    cy.get('.eventAudience .p-tag').contains("General").click();

    cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'General');

    cy.get('.eventAudience .p-tag')
        .should('have.length.at.least', 20)
        .each(($el) => {
          cy.wrap($el).should('contain.text', 'General');
        });

// REGION END


// REGION START advanced filter keeps single-click filter

    cy.get('.modal').should('not.exist');
    cy.get('#filterButton').click();
    cy.get('.modal').should('be.visible');

    // MENUS:
    cy.contains('.p-tree-node', "Audiences").as("AudienceMenu");
    cy.contains('.p-tree-node', "Categories").as("CategoryMenu");

    cy.get('@CategoryMenu').within((el) => {
      //cy.wrap(el).get('button').click();
      cy.wrap(el).get('.p-tree-node-children').should('not.exist');
    })

    cy.get('@AudienceMenu').within((el) => {
      //cy.wrap(el).get('button').click();
      cy.wrap(el).get('.p-tree-node-children').should('exist');
    })

    // The previous single-click filter is still applied:
    cy.contains(".p-tree-node-content", "General").within((el) => {
      cy.wrap(el).get('input').should('be.checked')
    })

    // No others should be checked, but they also appear
    cy.contains(".p-tree-node-content", "Adult Only").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked')
    })
    cy.contains(".p-tree-node-content", "Family Friendly").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked')
    })
    cy.contains(".p-tree-node-content", "Youth").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked')
    })
    cy.contains(".p-tree-node-content", "Teens and Up").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked')
    })

    // Opening and CLosing menus
    cy.get('@AudienceMenu').within((el) => {
      cy.wrap(el).get('button').first().click();
      cy.wrap(el).get('.p-tree-node-children').should('not.exist');
    })

    cy.get('@CategoryMenu').within((el) => {
      cy.wrap(el).get('button').first().click();
      cy.wrap(el).get('.p-tree-node-children').should('exist');
    })

    // opening category menu:
    cy.contains(".p-tree-node-content", "Arts and Culture").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked').check();
      cy.wrap(el).get('input').should('be.checked');
    })

    cy.contains(".p-tree-node-content", "Sports").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked');
    })

    cy.contains(".p-tree-node-content", "Education").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked');
    })

    cy.contains(".p-tree-node-content", "Music").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked');
    })

    // APPLYING FILTERS:
    cy.get('#applyFilters').click();
    cy.get('.modal').should('not.exist');

    // The event results are filtered on two things:
    cy.get('#filterDisplayText', { timeout: 10000 })
        .should('contain.text', 'General')
        .and('contain.text', 'Arts and Culture');

    // There SHOULD be 22 events in the database that are both Arts and Culture and Teens and Up
    cy.get('.event-item')
        .should('have.length', 3)
        .each(($el) => {
          cy.wrap($el).contains('.eventCategory', 'Arts and Culture');
          cy.wrap($el).contains('.eventAudience', 'General');
        });

// REGION END



// REGION START sorting also works when filtering
    cy.get('.sortTitle').click();

    cy.get('.event-item', { timeout: 10000 }).should('have.length', 3);

    // Event Title 999 SHOULD be the first event to show up?
    cy.get('.event-item').first()
        .should('contain.text', 'Event Title 9')
        .and('contain.text', 'Arts and Culture')
        .and('contain.text', 'General');

    cy.get('.sortTitle').click();

    cy.get('.event-item', { timeout: 10000 }).should('have.length', 3);

    // Event Title 144 SHOULD be the first event to show up?
    cy.get('.event-item').first()
        .should('contain.text', 'Event Title 18')
        .and('contain.text', 'Arts and Culture')
        .and('contain.text', 'General');
// REGION END



// REGION START clearing filters

    cy.get('#clearFilters').click();
    //cy.visit('/historic')
    cy.get('#filterDisplayText').should('have.text', 'No filters applied');
    cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);
    // These two options that were not filtered on should appear again
    cy.contains(".eventCategory", "Technology", { timeout: 10000 }).should('exist');
    cy.contains(".eventAudience", "Family Friendly", { timeout: 10000 }).should('exist');

// REGION END



// REGION START scrolling also works when filtering
    cy.scrollTo('bottom');

    cy.get('.event-item', { timeout: 20000 }).should('have.length', 35);

    // This should be the last item loaded?
    cy.get('.event-item').last()
        .should('contain.text', 'RV Show')
        .and('contain.text', 'Sports')
        .and('contain.text', 'Family Friendly');
// REGION END



// REGION START select filters with no events

    cy.get('#filterButton').click();

    cy.get('@AudienceMenu').within((el) => {
      cy.wrap(el).get('.p-tree-node-children').should('not.exist');
      cy.wrap(el).get('button').first().click();
      cy.wrap(el).get('.p-tree-node-children').should('exist');
    })

    // Selecting 2 filters with no results
    cy.contains(".p-tree-node-content", "Adult Only").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked').check();
      cy.wrap(el).get('input').should('be.checked');
    })

    cy.contains(".p-tree-node-content", "Family Friendly").within((el) => {
      cy.wrap(el).get('input').should('not.be.checked').check();
      cy.wrap(el).get('input').should('be.checked');
    })

    cy.get('#applyFilters').click();

    cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Adult Only').and('contain.text', 'Family Friendly');
    cy.get('.eventContainer h2').should('be.visible').and('contain.text', 'Sorry, There are currently no events posted.');

// REGION END



//REGION START advanced filter by location
    cy.get('#filterButton').click();

    cy.get('@CategoryMenu').within((el) => {
      cy.wrap(el).get('.p-tree-node-children').should('not.exist');
    })

    cy.contains(".p-tree-node-content", "Adult Only").within((el) => {
      cy.wrap(el).get('input').should('be.checked').check();
      cy.wrap(el).get('input').uncheck();
      cy.wrap(el).get('input').should('not.be.checked');
    })

    cy.contains(".p-tree-node-content", "Family Friendly").within((el) => {
      cy.wrap(el).get('input').should('be.checked').check();
      cy.wrap(el).get('input').uncheck();
      cy.wrap(el).get('input').should('not.be.checked');
    })

    cy.get('#locationFilterInput').type('Praireland')

    cy.get('#applyFilters').click();

    cy.get('#filterDisplayText', { timeout: 10000 })
        .should('contain.text', 'Praireland');

    cy.get('.event-item')
        .should('have.length', 1)
        .each(($el) => {
          cy.wrap($el).contains('.location', 'Praireland');
        });

// REGION END


//REGION START advanced filter by date
    cy.get('#filterButton').click();

    cy.get('#locationFilterInput').clear();
    //cy.get('#locationFilterInput').type('Moose Jaw');

    cy.get('#startDateInput').type('2024-11-1{leftArrow}1{rightArrow}{del}');
    //cy.get('#endDateInput').type('2024-11-10');

    cy.get('#applyFilters').click();

    cy.get('#filterDisplayText', { timeout: 10000 })
        .should('contain.text', 'Starting on 2024-11-10')

    cy.get('.event-item')
        .should('have.length', 5)
        .each(($el) => {
          cy.wrap($el).contains('.startDate', '2024-11-10');
        });

    cy.get('#filterButton').click();

    cy.get('#endDateInput').type('2024-11-1{rightArrow}2{leftArrow}{leftArrow}{leftArrow}{del}');

    cy.get('#applyFilters').click();

    cy.get('#filterDisplayText', { timeout: 10000 })
        .should('contain.text', 'Starting on 2024-11-10')
        .and('contain.text', 'Ending on 2024-11-12')

    cy.get('.event-item')
        .should('have.length', 4)
        .each(($el) => {
          cy.wrap($el).contains('.startDate', '2024-11-10');
          cy.wrap($el).contains('.endDate', '2024-11-12');
        });

// REGION END
  })

  after(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures');
  })
})