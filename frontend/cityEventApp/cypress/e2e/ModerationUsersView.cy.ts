import { loginHelper } from './LoginHelper.ts';

const AMOUNT_OF_USERS = 20;
const LOCAL_URL = 'http://localhost:5173'

describe("Moderator accesses the Users tab of the moderation page", () =>{
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures ModeratorFixture')
    });

    it('Moderator can reach the user\'s tab', () => {
        loginHelper('Moderator', 'ABC123def');

        cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').click();
        cy.get('#usersTab').click();
        cy.url().should('eq', LOCAL_URL + '/moderator/users');

        // Since only one fixture was loaded (containing only one user), there should be only one user loaded

        // this SHOULD be the rows in the datatable added by PrimeVue
        cy.get(".p-datatable-striped tbody tr").should("have.length", 1);
    })

    after(()=>{
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    })
})


describe('Moderator loads users', () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')

        loginHelper('Moderator', 'ABC123def');
        cy.visit(LOCAL_URL + "/moderator/users");

        // Sort headers:
        cy.get('.p-datatable-column-title').contains('Username').parent().as('sortUser');
        cy.get('.p-datatable-column-title').contains('Email').parent().as('sortEmail');
        cy.get('.p-datatable-column-title').contains('Creation').parent().as('sortDate');
    })

    it('Different types of user accounts are indicated', () => {
//region Different types of user accounts are indicated

        // Regular users should have the regularAccount class, and bo2 is a regular user
        cy.get('.p-datatable-striped tbody tr').contains('bo2').parent().within(() => {
            cy.get('.user-type > *').should('have.class', 'regularAccount')
                .and('not.have.class', 'premiumAccount').and('not.have.class', 'moderatorAccount');
        });

        // ok straight up we have too many users to reach our premium user
        cy.scrollTo('bottom');

        // Premium users should only have the premiumAccount class, and premium is a premium user
        cy.get('.p-datatable-striped tbody tr').contains('premium').parent().within(() => {
            cy.get('.user-type > *').should('have.class', 'premiumAccount')
                .and('not.have.class', 'regularAccount').and('not.have.class', 'moderatorAccount');
        });

        // Mods should only have the moderatorAccount class, and Moderator is a mod
        cy.get('.p-datatable-striped tbody tr').contains('Moderator').parent().within(() => {
            cy.get('.user-type > *').should('have.class', 'moderatorAccount')
                .and('not.have.class', 'premiumAccount').and('not.have.class', 'regularAccount');
        });

//endregion


//region Moderator scrolls through several users

        cy.get(".p-datatable-striped tbody tr").should("have.length", AMOUNT_OF_USERS * 2);
        cy.scrollTo('bottom');

        // There are only 40 users in the fixtures being loaded
        cy.get(".p-datatable-striped tbody tr").should("have.length", 40);

//endregion


//region Sort through users by Username

        // By default, it should be sorted by username A-Z
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '1');
        });

        // So these are not being sorted by
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // First username by username
        cy.get(".user-username").first().should('contain.text', 'bo0');


        // Sorting descending now
        cy.get('@sortUser').click();

        // After click, it should be sorted by username Z-A
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '-1');
        });

        // These are still the same
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // should be last user by username
        cy.get(".user-username").first().should('contain.text', 'zuUser');


        // After this click, everything should be back to default
        cy.get('@sortUser').click();
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '1');
        });
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // First username by username
        cy.get(".user-username").first().should('contain.text', 'bo0');

//endregion


//region Sort through users by email

        // Sorting by email ascending now
        cy.get('@sortEmail').click();

        // After click, it should be sorted by email A-Z
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '1');
        });
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // should be first user by email
        cy.get(".user-email").first().should('contain.text', 'bo1@example.com');

        // Now sorting in descending:
        cy.get('@sortEmail').click();
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '-1');
        });
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // should be last user by email
        cy.get(".user-email").first().should('contain.text', 'zueventsproject@gmail.com');

//endregion


//region Change the sort column correctly

        // After clicking sort by date ONCE
        cy.get('@sortDate').click();
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '1');
        });
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // Now descending
        cy.get('@sortDate').click();
        cy.get('@sortDate').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '-1');
        });
        cy.get('@sortEmail').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });
        cy.get('@sortUser').within(() => {
            cy.get('.p-datatable-sort-icon').should('have.attr', 'sortOrder').and('eq', '0');
        });

        // Back to default
        cy.get('@sortUser').click();

//endregion


//region Searching through users by username AND email

        // Nothing is input into the search bar, so no autocomplete options should be suggested
        cy.get('.p-autocomplete-option').should('not.exist');

        cy.get('#searchUsers').type('bob');
        cy.get('.p-autocomplete-option').should('exist');

        // This should be the first option
        cy.get('.p-autocomplete-option').first().should('contain.text', 'bob0');


        // first user in the table before search:
        cy.get(".user-username").first().should('contain.text', 'bo0');
        cy.get(".user-email").first().should('contain.text', 'bo5@example.com');

        // actually searching
        cy.get('#searchUsers').type('{enter}');

        // first user in the table after search:
        cy.get(".user-username").first().should('contain.text', 'bob0');
        cy.get(".user-email").first().should('contain.text', 'bob5@example.com');

        cy.get('#searchUsers').type('{selectAll}{del}');
        cy.get('#searchUsers').type('bobert');
        cy.get('.p-autocomplete-option').should('exist');
        cy.get('.p-autocomplete-option').first().should('contain.text', 'robert0');

        cy.get(".user-username").first().should('contain.text', 'bob0');
        cy.get(".user-email").first().should('contain.text', 'bob5@example.com');

        // Searching
        cy.get('#searchUsers').type('{enter}');

        cy.get(".user-username").first().should('contain.text', 'robert0');
        cy.get(".user-email").first().should('contain.text', 'bobert5@example.com');
    })
//endregion

    after(()=>{
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures')
    })

    // */
})

