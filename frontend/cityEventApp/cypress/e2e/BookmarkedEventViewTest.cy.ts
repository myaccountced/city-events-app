import { getAudience } from '../../src/components/interfaces/Audience'
import { getCategory } from '../../src/components/interfaces/Category'

describe('BookmarkedEvents End-to-End tests', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures ModeratorFixture AppUserFixture ' +
      'EventFixtures')
    cy.visit('/')

    // set up intercepts to track if any unexpected calls for media are made
    cy.intercept('GET', '/events/media?eventID=*', (req) => {
      assert.fail("Unexpected media fetch request");
    }).as('unexpectedMediaRequest')

    // set up intercepts to track if any unexpected calls for bookmarks are made
    cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
      assert.fail("Unexpected Bookmark fetch request");
    }).as('unexpectedBookmarkRequest')
  })
  after(() => {
    // These tests need, at least, the EventFixtureTenEvents fixture
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });

  it('should only display My Bookmarks button to signed in users', () => {
    // bookmark page should redirect to sign in
    cy.get('.p-menubar-item-link').eq(4).click();
    cy.url().should('eq', 'http://localhost:5173/signin');

    cy.get('#signInOut').click();

    // sign in as a user with no bookmarks
    cy.get('input[type="text"]').type('username2'); // Enter username
    cy.get('input[type="password"]').type('@Password2'); // Enter password
    cy.intercept('/auth/signin').as('signIn');
    cy.get('button[type="submit"]').click();
    //cy.wait(3000);
    cy.wait('@signIn')

    cy.intercept('/events?*').as('getEvents')
    cy.intercept('/events/bookmarks?*').as('getBookmarks')
    cy.intercept('/events/media?*').as('getMedia')

    //cy.wait(2000);

    // go back to events page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();



    // so now we should be signed in
    // and the bookmarks button should be available
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').should('be.visible').click();
    cy.url().should('eq', 'http://localhost:5173/bookmarks');

  })

  it('should have bookmark icons default to being an empty outline', () => {
    cy.intercept('/events?*').as('getEvents')
    cy.visit('/');

    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.get('#signInOut').click();

    // sign in as a user with no bookmarks
    cy.get('input[type="text"]').type('username2'); // Enter username
    cy.get('input[type="password"]').type('@Password2'); // Enter password
    cy.intercept('/auth/signin').as('signIn');
    cy.get('button[type="submit"]').click();
    //cy.wait(3000);
    cy.wait('@signIn')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.get('.event-item .bookmarkButton span').should('have.class', 'p-button-icon pi pi-bookmark')
  })

  it('should show message when no events are bookmarked.', () => {
    cy.intercept('/events?*').as('getEvents')
    cy.visit('/');

    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.get('#signInOut').click();

    // sign in as a user with no bookmarks
    cy.get('input[type="text"]').type('username2'); // Enter username
    cy.get('input[type="password"]').type('@Password2'); // Enter password
    cy.intercept('/auth/signin').as('signIn');
    cy.get('button[type="submit"]').click();

    cy.wait('@signIn')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').click();

    // should say no events are bookmarked
    cy.get('h2').should('have.text', 'You have no events bookmarked.');

  })

  it('should have bookmark icons toggle when they are clicked and bookmarks should appear on the My Bookmarked Events page', () => {
    // go back to events page list and sign in
    cy.intercept('/events?*').as('getEvents')
    cy.visit('/');

    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.get('#signInOut').click();

    cy.get('input[type="text"]').type('username2'); // Enter username
    cy.get('input[type="password"]').type('@Password2'); // Enter password
    cy.intercept('/auth/signin').as('signIn');
    cy.get('button[type="submit"]').click();

    cy.wait('@signIn')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    cy.intercept('/events/bookmarks?*').as('getBookmarks')
    cy.intercept('/events/media?*').as('getMedia')

    // bookmark some events and check that the icon updates
    cy.intercept('POST', '/events/bookmarks').as('postBookmark');
    cy.intercept('DELETE', '/events/bookmarks').as('deleteBookmark');

    cy.get('.event-item .bookmarkButton span.p-button-icon').first().click();

    cy.get('.event-item .bookmarkButton span.p-button-icon').first().should('have.class', 'p-button-icon pi pi-bookmark-fill');

    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(1).click();
    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(1).should('have.class', 'p-button-icon pi pi-bookmark-fill');

    cy.wait('@postBookmark');

    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(2).click();

    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(2).should('have.class', 'p-button-icon pi pi-bookmark-fill');

    cy.wait('@postBookmark');

    // check that clicking on it again changes it
    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(1).click();

    cy.get('.event-item .bookmarkButton span.p-button-icon').eq(1).should('have.class', 'p-button-icon pi pi-bookmark');

    cy.wait('@deleteBookmark');

    cy.intercept('/bookmarks').as('showBookmarkPage');

    // check if they appear in the my bookmarks list

    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').click();


    // should not show the header for "you have no bookmarked events"
    cy.get('h2').should('not.exist');

    // now 2 events should be bookmarked
    cy.get('.event-item').should('have.length', 2);
    // go back to main page
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

  })

  it('should have separate bookmarks for different users', () => {

    cy.intercept('/auth/signin').as('signIn');
    cy.visit('/');

    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();
    //sign out of the current account
    cy.get('#signInOut').click();


    // sign in as a different user
    cy.get('#identifier').type('Moderator'); // Type valid username
    cy.get('#password').type('ABC123def'); // Type valid password
    cy.get('button[type="submit"]').click();

    cy.wait('@signIn')
    cy.get('.p-menubar-item-link').eq(0).should('have.text', 'Events').click();

    //then go to my bookmarks
    cy.get('.p-menubar-item-link').eq(4).should('have.text', 'My Bookmarked Events').click();
    // there should not be any even if the other user does
    cy.get('h2').should('have.text', 'You have no events bookmarked.');
  })

  // have to have a signed in user
  // so sign in as a user
  // then book mark some events

  // then check that they are in the bookmarks
  // unbookmark some things

  // then sign out

  // sign in as a different user
  // show that there are no bookmarks in there
  after(()=>{
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  })
})