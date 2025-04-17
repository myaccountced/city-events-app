
/**
 * This helper function attempts to sign a user in with a username and password.
 * @param username string username (or email) being used to log in
 * @param password string password belonging to the user logging in
 */
export function loginHelper(username, password) {
    cy.visit('/signin');
    cy.get('#signInOut').click();
    cy.get('#identifier').type(username);
    cy.get('#password').type(password);
    cy.get('button[type="submit"]').click();

    cy.get('#signInOut', { timeout: 10000 }).should('contain.text', 'Sign Out');
}
