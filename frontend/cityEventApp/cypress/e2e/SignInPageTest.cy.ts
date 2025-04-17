// chao's tests for sign up button
describe('Sign-up Button Test', () => {
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
// end of chao's tests

// cedric's tests for signing into an account
describe('Sign In Button', () => {
  it('visits the homepage and check the Sign In button', () => {
    cy.visit('http://localhost:5173/')
    cy.get('#signInOut').should('contain.text', 'Sign In')

    cy.get('#signInOut').click(); // Click the 'Sign In' button

    // Clicking the button takes you to the sign-in page
    cy.url().should('include', '/signin');
  })
})

/**
 *
 * Test sign-in using username
 *
 */
describe('Successful Username Sign In', () => {
  it('should redirect to homepage and show Sign Out button', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('button[type="submit"]').click();

    cy.url( { timeout: 10000 }).should('eq', 'http://localhost:5173/'); // the current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
  });
});

describe('Incorrect Username', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('notmyusername'); // Type invalid username
    cy.get('#password').type('@Password1');     // Type valid password
    cy.get('button[type="submit"]').click();

    cy.url({ timeout: 10000 }).should('include', '/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});

describe('Correct Username and Wrong Password', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1');  // Type valid username
    cy.get('#password').type('password1');   // Type invalid password
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});

/**
 *
 * Test sign-in using Email
 *
 */
describe('Successful Email Sign In', () => {
  it('should redirect to homepage and show Sign Out button', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1@example.com'); // Type valid email
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:5173/'); // the current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
  });
});

describe('Incorrect Email', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('notmyemail@example.com'); // Type invalid email
    cy.get('#password').type('@Password1');     // Type valid password
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});

describe('Correct Email and Wrong Password', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1@example.com');  // Type valid email
    cy.get('#password').type('a');   // Type invalid password
    cy.get('button[type="submit"]').click();

    cy.url( { timeout: 10000 }).should('include', '/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});


// cedric's tests for being signed in after signing up automatically
/**
 *
 * No Sign-Up Option When Signed In
 *
 */
describe('No Sign-Up Option When Signed In', () => {
  it('should redirect to homepage and show Sign Out button', () => {
    // Sign-in
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:5173/'); // the current page should be the homepage

    // Check if we have Sign-up option
    cy.get('.signup-button').should('not.exist'); // Ensure that Sign-Up button does not exist
  });
});

/**
 * Test automatic sign-in after registration
 */
describe('Automatic Sign in After Registration', () => {
  it('should register a new user and redirect to the homepage', () => {
    cy.visit('http://localhost:5173/'); // Visit the site

    cy.get('.signup-button').click(); // Navigate to the registration page

    // Fill in the registration form
    cy.get('input[type="text"]').type('username3'); // Enter username
    cy.get('input[type="email"]').type('username3@example.com'); // Enter email
    cy.get('input[type="password"]').first().type('@Password3');
    cy.get('input[type="password"]').eq(1).type('@Password3')
    // Submit the form
    cy.get('button[type="submit"]').click();

    // After registration, check if we are signed in
    cy.get('.signup-button').should('not.exist'); // Ensure that Sign-Up button does not exist
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
  });
});

// /**
//  * Testing the 'Create Account' button in sign-in page
//  */
describe('Navigation to Registration Page from Sign In Page', () => {
  it('should navigate to registration page', () => {
    cy.visit('http://localhost:5173/'); // Visit the site
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('.register-button').click(); // Click the 'Create Account' button

    cy.url().should('eq', 'http://localhost:5173/registration'); // the current page should be the registration page
  });
});

// cedric's tests for remember me functionality
describe('Remember Me Enabled Test', () => {

  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures AppUserFixture')
  });

  it('should store the token in localStorage and user is remembered and continue button signs user in', () => {
    cy.visit('http://localhost:5173/'); // Visit the website
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('#rememberMe').check(); // Check the 'Remember Me' checkbox
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:5173/'); // The current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button should be replaced by 'Sign Out'
    cy.get('#signInOut').click(); // Sign-out

    // Check that the token is stored in localStorage
    cy.window().then((window) => {
      const token = window.localStorage.getItem('tokenusername1');
      expect(token).to.exist;
    });

    // Ensure user is remembered
    cy.url().should('eq', 'http://localhost:5173/signin'); // should navigate to sign in page
    // Test the continue button
    cy.get('.continue-button').click() // Click continue
    cy.url().should('eq', 'http://localhost:5173/'); // the current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
  });
});

describe('Multiple Remember Me Enabled Test', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures AppUserFixture')
  });

  it('should store the token in localStorage and users are remembered and continue buttons signs users in', () => {
    cy.visit('http://localhost:5173/'); // Visit the website

    // Sign in as username1
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('#rememberMe').check(); // Check the 'Remember Me' checkbox
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:5173/'); // should navigate to sign in page
    cy.get('#signInOut').click(); // Sign-out

    // Sign in as username2
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username2'); // Type valid username
    cy.get('#password').type('@Password2'); // Type valid password
    cy.get('#rememberMe').check(); // Check the 'Remember Me' checkbox
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:5173/'); // the current page should be the sign in page
    cy.get('#signInOut').click(); // Sign-out



    // Check that the tokens are stored in localStorage
    cy.window().then((window) => {
      const token1 = window.localStorage.getItem('tokenusername1');
      expect(token1).to.exist;

      const token2 = window.localStorage.getItem('tokenusername2');
      expect(token2).to.exist;
    });

    // Ensure user is remembered and press the continue button
    cy.get('.continue-button').eq(1).click();
    cy.url().should('eq', 'http://localhost:5173/'); // the current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
    cy.window().then((window) => {
      const parsedUsername = JSON.parse(window.localStorage.getItem('username'));
      // Check if the parsed 'value' property equals 'username2'
      expect(parsedUsername.value).to.equal('username2');
    });
  });
});

describe('Remember Me Disabled Test', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures AppUserFixture')
  });

  it('should store the token in localStorage and user is not remembered', () => {
    cy.visit('http://localhost:5173/'); // Visit the website
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('#rememberMe').uncheck(); // 'Remember Me' checkbox is not checked
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:5173/'); // The current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button should be replaced by 'Sign Out'
    cy.get('#signInOut').click(); // Sign-out

    cy.url().should('eq', 'http://localhost:5173/signin'); // The current page should be the sign in page
    // Check that the token is stored in localStorage
    cy.window().then((window) => {
      const token = window.localStorage.getItem('tokenusername1');
      expect(token).to.exist;
    });

    // Ensure user is not remembered
    cy.url().should('eq', 'http://localhost:5173/signin'); // should navigate to sign in page
    cy.get('.continue-button').should('not.exist'); // continue button shouldn't exist
  });
});

describe('Remember Me Forget Button Test', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures AppUserFixture')
  });

  it('should store the token in localStorage and user is remembered and continue button signs user in', () => {
    cy.visit('http://localhost:5173/'); // Visit the website
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('#rememberMe').check(); // Check the 'Remember Me' checkbox
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:5173/'); // The current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button should be replaced by 'Sign Out'
    cy.get('#signInOut').click(); // Sign-out

    // Check that the token is stored in localStorage
    cy.window().then((window) => {
      const token = window.localStorage.getItem('tokenusername1');
      expect(token).to.exist;
    });

    // Ensure user is remembered
    cy.url().should('eq', 'http://localhost:5173/signin'); // should navigate to sign in page
    // Test the forget button
    cy.get('.forget-button').click() // Click continue

    // Check that the token is removed from the localStorage
    cy.window().then((window) => {
      const token = window.localStorage.getItem('tokenusername1');
      expect(token).to.not.exist;
    });
  });
});

// more sign in tests?
// https://on.cypress.io/api
// npx cypress open

describe('Sign In Button', () => {
  it('visits the homepage and check the Sign In button', () => {
    cy.visit('http://localhost:5173/')
    cy.get('#signInOut').should('contain.text', 'Sign In')

    cy.get('#signInOut').click(); // Click the 'Sign In' button

    // Clicking the button takes you to the sign-in page
    cy.url().should('include', '/signin');
  })
})

describe('Successful Sign In', () => {
  it('should redirect to homepage and show Sign Out button', () => {
    cy.visit('http://localhost:5173/'); // Visit the home page
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1'); // Type valid username
    cy.get('#password').type('@Password1'); // Type valid password
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/'); // the current page should be the homepage
    cy.get('#signInOut').should('have.text', 'Sign Out'); // 'Sign In' button would be replaced by 'Sign Out'
  });
});

describe('Incorrect Username and Password', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the home page
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('notmyusername'); // Type invalid username
    cy.get('#password').type('password1');     // Type invalid password
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'http://localhost:5173/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});

describe('Correct Username and Wrong Password', () => {
  it('should stay on sign in page and show error message', () => {
    cy.visit('http://localhost:5173/'); // Visit the home page
    cy.get('#signInOut').click(); // Click the 'Sign In' button
    cy.get('#identifier').type('username1');  // Type valid username
    cy.get('#password').type('password1');   // Type invalid password
    cy.get('button[type="submit"]').click();
    cy.url().should('include', 'http://localhost:5173/signin'); // Should remain on the sign-in page
    cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check the error message
  });
});

// Test for multiple users
const users = [
  { username: 'username1', password: '@Password1', shouldSucceed: true },
  { username: 'username2', password: '@Password2', shouldSucceed: true },
  { username: 'notmyusername', password: 'password1', shouldSucceed: false } // This should fail
];

describe('User Login Tests', () => {
  users.forEach(user => {
    const { username, password, shouldSucceed } = user;

    it(`should ${shouldSucceed ? 'succeed' : 'fail'} for user ${username}`, () => {
      cy.visit('http://localhost:5173/'); // Visit the home page
      cy.get('#signInOut').click(); // Click the 'Sign In' button
      cy.get('#identifier').type(username);
      cy.get('#password').type(password);
      cy.get('button[type="submit"]').click();

      if (shouldSucceed) {
        cy.url().should('include', '/'); // Check redirect to homepage
        cy.get('#signInOut').should('have.text', 'Sign Out'); // Check for Sign Out button
      } else {
        cy.url().should('include', 'http://localhost:5173/signin'); // Should stay on sign-in page
        cy.get('.error').should('contain', 'Invalid identifier (username or email) or password'); // Check error message
      }
    });
  });
  after(()=>{
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  })
});


// end of cedric's tests