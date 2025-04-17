import { loginHelper } from './LoginHelper.ts';

const AD_AFTER_THIS_MANY = 20;

describe('Banner ad on each page', () => {
  before(() => {
    // These tests need all of the user fixtures
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  });

  it('user sees banner ads', () => {
    checkEachPageForBannerAds(true);
    loginHelper('username1', '@Password1');
    checkEachPageForBannerAds(true);
  })

  it('premium users does not see banner ads', () => {
    checkEachPageForBannerAds(true);
    loginHelper('premium', 'ABC123def');
    checkEachPageForBannerAds(false);
    cy.get('#signInOut').click();
    checkEachPageForBannerAds(true);
  })

  it('moderators do not see banner ads', () => {
    checkEachPageForBannerAds(true);
    loginHelper('moderator', 'ABC123def');
    checkEachPageForBannerAds(false);
    cy.get('#signInOut').click();
    checkEachPageForBannerAds(true);
  })
})

describe('Event ads every ' + AD_AFTER_THIS_MANY + ' events', () => {
  before(() => {
    // These tests need, at least, the lotsOfEvents fixture, and all the user fixtures (users, mods, premium)
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });

  it('registered user sees an ad every '+ AD_AFTER_THIS_MANY + ' events', () => {
    cy.visit('/');
    cy.scrollTo('bottom');
    checkEventListForAds(true);
    loginHelper('username1', '@Password1');
    cy.visit('/');
    cy.intercept('/events/media?*').as('getMedia')

    cy.scrollTo('bottom');

    checkEventListForAds(true);

    cy.scrollTo('bottom');
    checkEventListForAds(true);
  })

  it('premium user does not see ads among events', () => {
    cy.visit('/')
    cy.get('.event-ad', { timeout: 10000 }).should('exist');
    loginHelper('premium', 'ABC123def');
    cy.visit('/')
    cy.intercept('/events/media?*').as('getMedia')

    cy.get('.event').should('exist');
    cy.get('.event-ad').should('not.exist');
    cy.scrollTo('bottom');

    cy.get('.event-ad', { timeout: 10000 }).should('not.exist');
    cy.visit('/signin')
    cy.get('#signInOut').click();
    cy.visit('/')

    cy.get('.event-ad').should('exist');
  })

  it('moderator does not see ads among events', () => {
    cy.intercept('/auth/signin').as('signIn');
    cy.visit('/')
    cy.intercept('/events/media?*').as('getMedia')

    cy.get('.event-ad', { timeout: 10000 }).should('exist');
    loginHelper('moderator', 'ABC123def');
    cy.wait('@signIn');
    cy.visit('/')

    cy.get('.event', { timeout: 10000 }).should('exist');
    cy.get('.event-ad').should('not.exist');
    cy.scrollTo('bottom');

    cy.get('.event-ad', { timeout: 10000 }).should('not.exist');
    cy.visit('/signin')
    cy.get('#signInOut').click();
    cy.visit('/')

    cy.get('.event-ad').should('exist');
  })

})

describe('Ads appear at the end of the list', () => {
  before(() => {
    // These tests need, at least, the EventFixtureTenEvents fixture
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures EventFixtureTenEvents')
    //cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });
  after(() => {
    // These tests need, at least, the EventFixtureTenEvents fixture
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });

  it('Events are filtered until only 10 remain', () => {
    cy.visit('/');

    cy.intercept('/events/media?*').as('getMedia')


    cy.scrollTo('bottom');

    cy.get('#eventFeed > *', { timeout: 10000 }).each(($el, index) => {
      // There are only 10 events in this fixture, so the 11th element (index 10) in the list SHOULD BE an ad
      if (index == 10) {
        cy.get($el).within(() => {
          cy.get('.event-ad').should('exist')
        })
      } else {
        cy.get($el).within(() => {
          cy.get('.event-ad').should('not.exist')
        })
      }
    })
  })
})


/**
 * This helper function checks every page in the web application, checking whether ads appear.
 * @param seeAds true to see ads, false to not see ads
 */
function checkEachPageForBannerAds(seeAds)
{
  const chainer = seeAds ? 'exist' : 'not.exist';

  cy.visit('/')
  cy.get('.banner-ad', { timeout: 10000 }).should(chainer)
  cy.visit('/registration')
  cy.get('.banner-ad', { timeout: 10000 }).should(chainer)

  // This one works with guests?
  cy.visit('/upload')
  cy.get('.banner-ad', { timeout: 10000 }).should(chainer)
  // This one won't work with guests, redirects to signin
  cy.visit('/postevent')
  cy.get('.banner-ad', { timeout: 10000 }).should(chainer)
  cy.visit('/signin')
  cy.get('.banner-ad', { timeout: 10000 }).should(chainer)
}


/**
 * This helper function checks that there are only every ads every AD_AFTER_THIS_MANY events,
 * and that they only appear according to seeAds.
 * @param seeAds true if the user should see ads, false if they shouldn't
 */
function checkEventListForAds(seeAds)
{
  const chainer = seeAds ? 'exist' : 'not.exist';

  cy.get('#eventFeed > *').each(($el, index) => {
    if (index % (AD_AFTER_THIS_MANY + 1) == AD_AFTER_THIS_MANY) {
      cy.get($el).within(() => {
        cy.get('.event-ad').should(chainer)
      })
    } else {
      cy.get($el).within(() => {
        cy.get('.event-ad').should('not.exist')
      })
    }
  })
}