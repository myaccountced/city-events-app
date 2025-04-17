
// region
describe('Sign-up Button Test', () => {
  before(() => {
    // These tests need all of the user fixtures
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
  });
  beforeEach(() => {
    // Visit the home page of your web application
    cy.visit('/'); // Replace with your actual home page URL
  });

  it('Should have a Sign-up button that leads to the registration page', () => {
    // Ensure the sign-up button exists on the page
    cy.get('button').contains('Sign Up').should('be.visible');

    // Simulate a click on the sign-up button
    cy.get('button').contains('Sign Up').click();

    // Verify that the user is redirected to the registration page
    cy.url().should('include', '/registration'); // Adjust the URL as needed for your registration route

    // Optionally, verify that the registration page contains a form or some specific content
    cy.get('form#registration-form').should('be.visible'); // Adjust the selector based on your actual registration form
  });
});

describe('Registration Page Test', () => {
  beforeEach(() => {
    // Visit the page where the form is located
    cy.visit('/registration');
  });

  it('displays an error message for empty username', () => {
    // Fill in registration data except the username
    cy.get('input[type="email"]').type('test11@example.com');
    cy.get('input[type="password"]').eq(0).type('@Password123');
    cy.get('input[type="password"]').eq(1).type('@Password123')

    // Submit the form
    cy.get('button[type="submit"]').click()

    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'username is required'); // Check the message content


    // Add the username
    cy.get('input[type="text"]').type('username1')

    // Submit again
    cy.get('button[type="submit"]').click()

    // Test error for short username
    cy.get('input[type="text"]').clear().type('1234');
    cy.get('button[type="submit"]').click();
    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'username must be at least 5 characters'); // Check the message content


    // Test error for long username
    cy.get('input[type="text"]').clear().type('asdasdasdasdasdasdasdasdas');
    cy.get('button[type="submit"]').click();
    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'username cannot be longer than 25 characters'); // Check the message content

    // Test displays an error message for empty email
    cy.get('input[type="text"]').clear().type('cosa280');
    cy.get('input[type="email"]').clear();
    cy.get('button[type="submit"]').click();
    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'email is required'); // Check the message content

    // Test displays an error message for invalid email
    cy.get('input[type="email"]').clear().type('invalid@email');
    cy.get('button[type="submit"]').click();

    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'invalid email address'); // Check the message content

  // Test displays an error message for empty password
    cy.get('input[type="password"]').eq(0).clear()
    // Submit the form
    cy.get('button[type="submit"]').click();

    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'password is required'); // Check the message content

  // Test displays an error message for short password
    cy.get('input[type="password"]').eq(0).clear().type('short');
    // Submit the form
    cy.get('button[type="submit"]').click();

    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'password must be at least 8 characters'); // Check the message content

    // Test displays an error message for long password
    cy.get('input[type="password"]').eq(0).clear().type('Password10Password201');

    // Submit the form
    cy.get('button[type="submit"]').click();

    // Check that the error message is displayed
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'password cannot be longer than 20 characters'); // Check the message content

    // Test password not match
    cy.get('input[type="password"]').eq(0).clear().type('@Password123');
    cy.get('input[type="password"]').eq(1).type('@Password12')

    //cy.get('button[type="submit"]').click();


    // Verify error message is displayed
    cy.get('.error', {timeout:4000}).should('contain', 'Passwords do not match')

    // Verify we're still on the registration page
    cy.url().should('include', '/registration')

  });



  it('does not show an error message for valid data lowest boundary', () => {
    // Mock the API response for successful registration
    cy.intercept('POST', '/api/registration', {
      statusCode: 200,
      body: {
        message: 'Registration successful!' // success message from backend
      }
    }).as('registerUser');
    // Input a valid email, 5-character username, 8-character password
    cy.get('input[type="text"]').type('12345');
    cy.get('input[type="email"]').type('test@example.com');
    cy.get('input[type="password"]').eq(0).type('Password');
    cy.get('input[type="password"]').eq(1).type('Password')

    // Submit the form
    cy.get('button[type="submit"]').click();

    // Check that the error message is not displayed
    cy.get('.error').should('not.exist');
  });

  it('does not show an error message for valid data highest boundary', () => {
    // Mock the API response for successful registration
    cy.intercept('POST', '/api/registration', {
      statusCode: 200,
      body: {
        message: 'Registration successful!' // success message from backend
      }
    }).as('registerUser');
    // Input a valid email, 25-character username, 20-character password
    cy.get('input[type="text"]').type('1234512345123451234512345');
    cy.get('input[type="email"]').type('test@example.com');
    cy.get('input[type="password"]').eq(0).type('Password10Password10');
    cy.get('input[type="password"]').eq(1).type('Password10Password10')

    // Submit the form
    cy.get('button[type="submit"]').click();

    // Check that the error message is not displayed
    cy.get('.error').should('not.exist');
  });


  it('displays error message upon unsuccessful registration', () => {
    // Mock the API response for unsuccessful registration
    cy.intercept('POST', '/api/registration', {
      statusCode: 409, // Simulate a bad request
      body: {
          status: 'error',
          message: 'Duplicated data',
          errors: {
            email: 'Email already exists' // Custom error message from backend
          },
      }
    }).as('registerUser');

    // Fill in the registration form
    cy.get('input[type="text"]').type('testuser');
    cy.get('input[type="email"]').type('test@example.com');
    cy.get('input[type="password"]').eq(0).type('Password');
    cy.get('input[type="password"]').eq(1).type('Password')

    // Submit the form
    cy.get('button[type="submit"]').click();

    // Wait for the mocked API call to complete
    cy.wait('@registerUser');

    // Check for the success message not display
    cy.get('.successMS').should('not.exist'); // Check if the success message not exists

    // check for duplicate email address error
    cy.get('.error').should('exist'); // Check if the error message element exists
    cy.get('.error').should('contain', 'Email already exists');
  });
});
// endregion



// region Story15: User upgrade account to premium

describe('Subscription Component in Registration Page', () => {
  beforeEach(() => {
    Cypress.on('uncaught:exception', (err, runnable) => {
      // returning false here prevents Cypress from
      // failing the test
      return false
    })
    cy.visit('http://localhost:5173/registration')

    const timestamp = new Date().getTime() // Generate a unique timestamp

    // Input mock data for registration
    cy.get('input[type="text"]').type(`user${timestamp}`)
    cy.get('input[type="email"]').type(`user${timestamp}@example.com`)
    cy.get('input[type="password"]').eq(0).type('@Password1');
    cy.get('input[type="password"]').eq(1).type('@Password1')
  })

  it('should display the SubscriptionPlan component', () => {
    // Verify the subscription plan component is present
    cy.get('div.subscription-container').should('exist')
  })


  it('should highlight the "1 MONTH" option when selected and unselect it when clicked again', () => {
    // Select the "1 MONTH" option and verify its background color changes
    cy.get('#card-1')
      .click()
      .should('have.class', 'selected') // Assuming 'selected' is the class applied when highlighted

    // Click again to unselect and verify the background color reverts
    cy.get('#card-1')
      .click()
      .should('not.have.class', 'selected')
  })

  it('should highlight the "1 YEAR" option when selected and unselect it when clicked again', () => {
    // Select the "1 MONTH" option and verify its background color changes
    cy.get('#card-2')
      .click()
      .should('have.class', 'selected') // Assuming 'selected' is the class applied when highlighted

    // Click again to unselect and verify the background color reverts
    cy.get('#card-2')
      .click()
      .should('not.have.class', 'selected')
  })


  it('should create a premium account with 30 days for 1-Month selection', () => {
    // Select the 1-Month option
    cy.get('#card-1').click()

    // Submit the registration form
    cy.get('button[type="submit"]').click()
    cy.wait(3000) // wait for browser
    // Verify that the browser redirects to the event page ('/')
    cy.url().should('eq', 'http://localhost:5173/') // Adjust URL if different for your setup

    // Navigate to the profile page
    cy.visit('http://localhost:5173/profile')
    cy.wait(5000) // wait for browser

    // Verify the profile page displays the premium symbol and premium account message
    cy.get('img[alt="Premium_Icon"]').should('be.visible')
    cy.get('#premium-message').should('be.visible')
      .and('contain', '30 days remaining')
  })

  it('should create a premium account with 365 days for 1-Year selection', () => {
    // Select the 1-Month option
    cy.get('#card-2').click()

    // Submit the registration form
    cy.get('button[type="submit"]').click()
    cy.wait(3000) // wait for browser
    // Verify that the browser redirects to the event page ('/')
    cy.url().should('eq', 'http://localhost:5173/') // Adjust URL if different for your setup

    // Navigate to the profile page
    cy.visit('http://localhost:5173/profile')
    cy.wait(3000) // wait for browser
    // Verify the profile page displays the premium symbol and premium account message
    cy.get('img[alt="Premium_Icon"]').should('be.visible')
    cy.get('#premium-message').should('be.visible')
      .and('contain', '365 days remaining')
  })
})


// endregion

// region story 51 guest user sign with social media credential
describe('Social Media Signup Tests', () => {
  beforeEach(() => {
    // Visit the signup page of the application
    cy.visit('http://localhost:5173/registration')
    // cy.get('button').contains('Continue with Google').should('be.visible')
    // Check if the Google login button exists inside the form
    cy.get('form#registration-form')  // Find the registration form by ID
        .find('.googlelogin .login')  // Find the Google login button by its class
        .should('exist'); // Assert that the button exists
  })

  // Test 1: Guest user successfully creates an account with Google (user never in local database) and sign in automatically
  it('should successfully create an account with Google and redirect to homepage', () => {

    // Intercept the API request to the backend
    cy.intercept('POST', '/auth/google-login', {
      statusCode: 200,
      body: {
        token: 'fake-jwt-token',
        userId: '123',
        username: 'Zuevents',
        scope: ''
      }
    }).as('googleLogin')

    // Intercept the API request to the backend
    cy.intercept('GET', '/user/123', {
      statusCode: 200,
      body: [{
        id: 123,
        username: 'Zuevents',
        email: 'zuevents@gmail.com',
        mod: 0
      }]
    }).as('getProfile')

    // Click the login button
    cy.get('.googlelogin').click();

    // Assert the response
    cy.wait('@googleLogin').its('response.statusCode').should('equal', 200)

    // Assert that the user is redirected to the home page
    cy.url().should('eq', `${Cypress.config().baseUrl}/`)

    // Have the sign-out button
    cy.get('#signInOut').should('have.text', 'Sign Out')

    // Navigate to profile page will get user's infomation
    cy.visit('http://localhost:5173/profile')
    cy.wait('@getProfile').its('response.statusCode').should('equal', 200)

    cy.get('#username').should('contain', 'Zuevents')
    cy.get('#userEmail').should('contain', 'zuevents@gmail.com')
  })

  // Test 2: Guest user successfully signs in with Google account, account already linked with Google
  it('should successfully sign in with Google and redirect to homepage', () => {
    // Visit the signup page of the application
    cy.visit('http://localhost:5173/signin')
    // cy.get('button').contains('Continue with Google').should('be.visible')
    // Check if the Google login button exists inside the form
    cy.get('form')  // Find the registration form by ID
        .find('.googlelogin .login')  // Find the Google login button by its class
        .should('exist'); // Assert that the button exists
    // Intercept the API request to the backend
    cy.intercept('POST', '/auth/google-login', {
      statusCode: 200,
      body: {
        token: 'fake-jwt-token',
        userId: '123',
        username: 'Zuevents',
        scope: ''
      }
    }).as('googleLogin')

    // Intercept the API request to the backend
    cy.intercept('GET', '/user/123', {
      statusCode: 200,
      body: [{
        id: 123,
        username: 'Zuevents',
        email: 'zuevents@gmail.com',
        mod: 0
      }]
    }).as('getProfile')

    // Click the login button
    cy.get('.googlelogin').click();

    cy.wait(4000)
    // Assert the response
    cy.wait('@googleLogin').its('response.statusCode').should('equal', 200)

    // Assert that the user is redirected to the home page
    cy.url().should('eq', `${Cypress.config().baseUrl}/`)

    // Have the sign-out button
    cy.get('#signInOut').should('have.text', 'Sign Out')

    // Navigate to profile page will get user's infomation
    cy.visit('http://localhost:5173/profile')
    cy.wait('@getProfile').its('response.statusCode').should('equal', 200)

    cy.get('#username').should('contain', 'Zuevents')
    cy.get('#userEmail').should('contain', 'zuevents@gmail.com')
  })

})

// endregion