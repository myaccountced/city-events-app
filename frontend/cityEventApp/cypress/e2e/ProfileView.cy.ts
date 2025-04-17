


//region Chau's Story15-user-upgrade-account-to-premium
describe('Subscription Component in Normal Profile Page', () => {
  beforeEach(() => {
    // These tests need all of the user fixtures
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    // Visit the signin page
    cy.visit('http://localhost:5173/signin');

    // Input username and password of a normal user
    cy.get('#identifier').type('nUserS15'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password

    // Click the signin button
    cy.get('button[type="submit"]').click();
    // redirect to event page
    cy.url({ timeout: 10000 }).should('eq', 'http://localhost:5173/');
  });

  it('should display the SubscriptionPlan component', () => {
    cy.visit('http://localhost:5173/profile');
    // Verify the subscription plan component is present
    cy.get('div.subscription-container').should('exist');
  });

  it('should not display premium status for normal user', () => {
    // Ensure no premium symbol nor status are displayed initially
    cy.get('img[alt="Premium_Icon"]').should('not.exist');
    cy.get('#premium-message').should('not.exist')
  });


  it('should upgrade to premium and display 30 days for 1-month option', () => {
    cy.visit('http://localhost:5173/profile');
    cy.get('#username', { timeout: 10000 }).should('contain.text', 'nUserS15')

    // Select the 1-Month option
    cy.get('#card-1').click()

    // Click confirm button
    cy.get('button[type="submit"]').click();

    // Verify the profile page displays the premium symbol and premium account message
    cy.get('img[alt="Premium_Icon"]', { timeout: 15000 }).should('be.visible');
    cy.get('#premium-message').should('be.visible')
        .and('contain', '30 days remaining');
  });

  it('should upgrade to premium and display 365 days for 1-year option', () => {
    cy.visit('http://localhost:5173/profile');
    cy.get('#username', { timeout: 10000 }).should('contain.text', 'nUserS15')

    // Select the 1-Year option
    cy.get('#card-2').click()

    // Click confirm button
    cy.get('button[type="submit"]').click();

    // cy.wait(5000)
    // Verify the profile page displays the premium symbol and premium account message
    cy.get('img[alt="Premium_Icon"]', { timeout: 15000 }).should('be.visible');
    cy.get('#premium-message').should('be.visible')
        .and('contain', '365 days remaining');
  });
});

describe('Premium User Profile Page', () => {
  beforeEach(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:schema:drop --force && ' +
        'php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    // Visit the signin page
    cy.visit('http://localhost:5173/signin');

    // Input username and password of a premium user
    cy.get('#identifier').type('premium');
    cy.get('#password').type('ABC123def');

    // Click the signin button
    cy.get('button[type="submit"]').click();
    // redirect to event page
    cy.url().should('eq', 'http://localhost:5173/');
    // Navigate to the profile page
    cy.visit('http://localhost:5173/profile');
  });

  it('should display user infor for an existing premium user', () => {
    cy.visit('http://localhost:5173/profile');
    cy.get('#username', { timeout: 10000 }).should('contain.text', 'premium')

    // Verify that the premium status is displayed with the initial remaining days
    cy.get('img[alt="Premium_Icon"]', { timeout: 10000 }).should('be.visible');
    cy.get('#premium-message', { timeout: 10000 }).should('be.visible')
        .and('contain', 'days remaining');

    // Hayden's Story 43 Test for user information
    cy.get('#username').should('contain', 'premium')
    cy.get('#userEmail').should('contain', 'premium@test.com')
  });


  it('should increase the remaining days by 30 when selecting 1-month option and clicking "Confirm"', () => {
    cy.get('#username', { timeout: 10000 }).should('contain.text', 'premium')
    //cy.wait(1000) // wait for user fetch call
    // Get the initial number of remaining days
    cy.get('#premium-message', { timeout: 10000 }).invoke('text').then((text) => {

      const initialDays = parseInt(text.match(/\d+/)[0], 10); // Extract the number of days from the text
      // Select the 1-Month option

      cy.get('#card-1').click()
      // Click the confirm button
      cy.get('button[type="submit"]').click();

      // Verify the premium status with updated remaining days
      cy.get('#premium-message').should('contain', `${initialDays + 30} days remaining`);
    });
  });

  it('should increase the remaining days by 365 when selecting 1-year option and clicking "Confirm"', () => {
    cy.get('#username', { timeout: 10000 }).should('contain.text', 'premium')
    //cy.wait(1000) // wait for user fetch call
    // Get the initial number of remaining days
    cy.get('#premium-message', { timeout: 10000 }).invoke('text').then((text) => {

      const initialDays = parseInt(text.match(/\d+/)[0], 10); // Extract the number of days from the text
      // Select the 1-Month option

      cy.get('#card-2').click()
      // Click the confirm button
      cy.get('button[type="submit"]').click();

      // Verify the premium status with updated remaining days
      cy.get('#premium-message').should('contain', `${initialDays + 365} days remaining`);
    });
  });
});
//endregion




//region Hayden's Story 43 - User views their profile
describe('Moderator Profile View Tests', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    cy.visit('http://localhost:5173/signin')
  })

  it('should display moderator info', () => {
    cy.get('#identifier').type('Moderator')
    cy.get('#password').type('ABC123def')
    cy.get('button[type="submit"]').click()

    // Wait for URL to change from signin page
    cy.url().should('not.include', '/signin')

    cy.visit('http://localhost:5173/profile')

    cy.get('img[alt="Moderator_Icon"]', { timeout: 10000 }).should('be.visible')
    cy.get('#profile-subscription').should('not.exist')
    cy.get('#username').should('contain', 'Moderator')
    cy.get('#userEmail').should('contain', 'mod1@test.com')
  })
})
//endregion



//region Chau's story53 - User receives notification based on their preference
describe('Notification Preferences', () => {
  beforeEach(() => {
    // These tests need all of the user fixtures
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:schema:drop --force & php bin/console doctrine:schema:create & php bin/console app:load-test-fixtures')
    // Visit the signin page
    cy.visit('http://localhost:5173/signin');

    // Input username and password of a normal user
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password

    // Click the signin button
    cy.get('button[type="submit"]').click();
    cy.url({ timeout: 10000 }).should('eq', 'http://localhost:5173/');

    // redirect to event page
    cy.visit('/profile'); // Navigate to the profile page
    cy.url({ timeout: 10000 }).should('eq', 'http://localhost:5173/profile');
    cy.get('#infoCard').should('be.visible');
  });

  it('User can interact with notification preferences', () => {
    // Verify UI Elements
    cy.contains('Notification Preferences').should('be.visible');
    cy.contains('Do you want to receive notifications?').should('be.visible');
    cy.get('#btnNotified input .p-toggleswitch-input').should('not.be.checked');
    cy.contains('Notification Method').should('not.exist');
    cy.contains('Notification Timing').should('not.exist');

    cy.get('#btnNotified input .p-toggleswitch-input').should('exist').click();
    cy.get('#btnNotified input .p-toggleswitch-input').should('be.checked');

    // Ensure notification settings appear
    cy.contains('Notification Method').should('be.visible');
    cy.contains('Notification Timing').should('be.visible');

    // Select and verify notification timing options
    cy.get('#day0 .p-checkbox-input').should('not.be.checked').click();
    cy.get('#day0 .p-checkbox-input').should('be.checked');
    cy.get('#day1 .p-checkbox-input').should('not.be.checked').click();
    cy.get('#day1 .p-checkbox-input').should('be.checked');
    cy.get('#day7 .p-checkbox-input').should('not.be.checked').click();
    cy.get('#day7 .p-checkbox-input').should('be.checked');

    // Deselect and verify notification timing options
    cy.get('#day0 .p-checkbox-input').click();
    cy.get('#day0 .p-checkbox-input').should('not.be.checked');
    cy.get('#day1 .p-checkbox-input').click();
    cy.get('#day1 .p-checkbox-input').should('not.be.checked');
    cy.get('#day7 .p-checkbox-input').click();
    cy.get('#day7 .p-checkbox-input').should('not.be.checked');

    cy.get('#btnNotified .p-toggleswitch-input').should('exist').click();
    cy.get('#btnNotified .p-toggleswitch-input').should('not.be.checked');
    cy.contains('Notification Method').should('not.exist');
    cy.contains('Notification Timing').should('not.exist');
  });

  it('User can successfully set up their notification preferences with 1 method', () => {
    // Spy on window.alert
    cy.on('window:alert', (text) => {
      expect(text).to.equal('Your notification setting has been successfully updated.');
    });

    // Verify UI Elements
    cy.get('#btnNotified .p-toggleswitch-input').should('not.be.checked').click().should('be.checked');

    cy.wait(2000)
    // Select notification timing
    cy.get('#day1 .p-checkbox-input').should('not.be.checked').click().should('be.checked');

    // Save preferences
    cy.get('#btnSavePref').click();

    // Confirm success message by listening for the alert, spy on window:alert

    // Ensure changes persist after reload
    cy.reload();
    // waiting for the info load before moving on
    cy.get('#infoCard').should('be.visible');

    cy.get('#btnNotified .p-toggleswitch-input').should('be.checked');
    cy.get('label').contains('Email').should('exist');
    cy.get('#day1 .p-checkbox-input').should('be.checked');

    // Ensure other options are unselected
    cy.get('#day0 .p-checkbox-input').should('not.be.checked');
    cy.get('#day7 .p-checkbox-input').should('not.be.checked');
  });

  it('User can successfully set up their notification preferences with 3 methods', () => {
    // Spy on window.alert
    cy.on('window:alert', (text) => {
      expect(text).to.equal('Your notification setting has been successfully updated.');
    });

    // Verify UI Elements
    cy.get('#btnNotified .p-checkbox-input').should('not.be.checked');
    cy.get('#btnNotified .p-checkbox-input').should('exist').click();

    // Select notification timing
    cy.get('#day0 .p-checkbox-input').should('not.be.checked').click().should('be.checked');
    cy.get('#day1 .p-checkbox-input').should('not.be.checked').click().should('be.checked');
    cy.get('#day7 .p-checkbox-input').should('not.be.checked').click().should('be.checked');

    // Save preferences
    cy.get('#btnSavePref').click();

    // Confirm success message by listening for the alert, spy on window:alert

    // Ensure changes persist after reload
    cy.reload();
    // waiting for the info load before moving on
    cy.get('#infoCard').should('be.visible');

    cy.get('#btnNotified .p-toggleswitch-input').should('be.checked');
    cy.get('label').contains('Email').should('exist');
    cy.get('#day1 .p-checkbox-input').should('be.checked');
    cy.get('#day0 .p-checkbox-input').should('be.checked');
    cy.get('#day7 .p-checkbox-input').should('be.checked');
  });


  it('User can successfully set up their notification preferences with 0 methods', () => {
    // Spy on window.alert
    cy.on('window:alert', (text) => {
      expect(text).to.equal('Your notification setting has been successfully updated.');
    });

    // Verify UI Elements
    cy.get('#btnNotified .p-toggleswitch-input').should('exist').and('not.be.checked').click().should('be.checked');
    // cy.get('#btnNotified input').should('exist').click();
    // cy.get('#btnNotified input').should('be.checked');
    // Do not select any notification timing

    // Save preferences
    cy.get('#btnSavePref').click();


    // Ensure changes persist after reload
    cy.reload();
    // waiting for the info load before moving on
    cy.get('#infoCard').should('be.visible');

    cy.get('#btnNotified .p-toggleswitch-input').should('be.checked');
    cy.get('label').contains('Email').should('exist');
    cy.get('#day1 .p-checkbox-input').should('not.be.checked');
    cy.get('#day0 .p-checkbox-input').should('not.be.checked');
    cy.get('#day7 .p-checkbox-input').should('not.be.checked');
  });

  it('User cancels their notification setup and settings revert to previous state', () => {
    // Ensure initial state
    cy.get('#btnNotified .p-toggleswitch-input').should('not.be.checked');

    // Change notification settings
    cy.get('#btnNotified .p-toggleswitch-input').should('exist').click();
    cy.contains('Notification Method').should('be.exist');
    cy.contains('Notification Timing').should('be.visible');

    cy.wait(2000)
    // Select notification timing
    cy.get('#day0 .p-checkbox-input').should('exist').and('not.be.checked').click().should('be.checked');
    cy.get('#day7 .p-checkbox-input').should('exist').and('not.be.checked').click().should('be.checked');

    // Click Cancel button
    cy.get('#btnCancelPref').click();

    // Ensure settings revert to original state
    cy.get('#btnNotified .p-toggleswitch-input').should('not.be.checked');
    cy.contains('Notification Method').should('not.exist');
    cy.contains('Notification Timing').should('not.exist');
  });
});
//endregion