//uploads for pending events starts here
describe("Non signed in user cannot access moderation page.", () =>{
    it("The moderate button should not appear in the the nav bar and if typed in the url, should navigate back to the " +
        "main page", () =>{
        cy.visit("/")

        //when not signed in there should not be a moderate button in the nav bar
        cy.contains(".nav-item", "Moderate").should('not.exist');

        //if a non signed in user tries to access the moderation by navigatin through the url
        // then they should be navigated back to the main page.
        cy.visit("/moderator/pending")
        cy.url().should('include', '/');
    })
})

describe("Non moderator user cannot access moderation page.", () =>{
    it("The moderate button should not appear in the the nav bar and if typed in the url, should navigate back to the " +
        "main page", () =>{
        cy.visit("/signin")
        cy.get('#identifier').type('username1');
        cy.get('#password').type('@Password1');
        cy.get('button[type="submit"]').click();

        //when not signed in there should not be a moderate button in the nav bar
        cy.get('.p-menubar-item-link').eq(6).should('not.exist');

        //if a non signed in user tries to access the moderation by navigatin through the url
        // then they should be navigated back to the main page.
        cy.visit("/moderator/pending")
        cy.url().should("not.include", "/moderator/pending");
        cy.url().should('include', '/');
    })
})

describe("Moderator accesses the moderation page with 0 pending events.", () =>{
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures EventFixtureNoEvent')
    });

    it("The moderate button should appear in the the nav bar and if typed in the url, should navigate back to the " +
        "moderation page. The Moderation page has no pending events", () =>{
        // need to load the "EventFixtureNoEvent.php" fixture
        cy.visit("/signin")
        cy.get('#identifier').type('moderator2');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();

        //when signed in with a moderator account, there should nbe a moderate button in the nav bar
        cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools').click();

        //should navigate to the moderation page
        cy.url().should('include', '/moderator/pending');
        cy.contains('#no-pending', 'There are currently no pending events.')

        //Verify that there are the two tabs
        cy.get('.p-buttongroup').within(() =>{
            cy.contains('Pending');
            cy.contains('Reported');
        })

    })
})

describe("Moderator accesses the moderation page with 21 pending events.", () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures TwentyOnePendingEvents')
        // set up intercepts to track if any unexpected calls for media are made
        cy.intercept('GET', '/events/media?eventID=*', (req) => {
            assert.fail("Unexpected media fetch request");
        }).as('unexpectedMediaRequest')

        // set up intercepts to track if any unexpected calls for bookmarks are made
        cy.intercept('GET', '/events/bookmarks?eventID=*', (req) => {
            assert.fail("Unexpected Bookmark fetch request");
        }).as('unexpectedBookmarkRequest')
    });
    it("The moderate button should appear in the the nav bar and if typed in the url, should navigate back to the " +
        "moderation page", () => {


        cy.visit("/signin")
        cy.get('#identifier').type('moderator3');
        cy.get('#password').type('ABC123def');
        cy.get('button[type="submit"]').click();

        cy.get('.p-menubar-item-link').eq(6).should('have.text', 'Moderator Tools');

        cy.visit("/moderator/pending")
        cy.url().should('include', '/moderator/pending');

        //A table should be visible
        cy.get('#pendingTable').should('be.visible');

        // Verify initial rows
        cy.get(".p-datatable-striped tbody tr").should("have.length", 20);

        //get all the row data
        const eventTitles : String[] = [];
        cy.get('#pendingTable tbody tr').then(rows => {

            rows.each((index, row) => {
                const eventTitle = Cypress.$(row).find('.event-title').text();
                eventTitles.push(eventTitle);
            });

            const expectedOrder = [];
            for (let i = 1; i <= eventTitles.length; i++) {
                if(i < 10)
                {
                    expectedOrder.push(`Pending Event 0${i}`);
                }
                else {
                    expectedOrder.push(`Pending Event ${i}`);
                }

            }

            // Assert that the event titles are in the correct order
            expect(eventTitles).to.deep.equal(expectedOrder);

            // Assert that the approved events do not appear
            cy.contains('.event-title', 'Approved Event').should('not.exist')

            //At this point, only the first 20 events should be loaded
            //test to see that the 21st doesn't exist yet
            cy.contains('.event-title', 'Pending Event 21').should('not.exist')

            //after scrolling
            cy.scrollTo('bottom')
            //should now exist
            cy.contains('.event-title', 'Pending Event 21')

            //check the show more button actually shows more
            cy.get('#pendingTable tbody tr').first().within(() => {
                  cy.get('.rejectBtn').should('not.exist'); // Ensure hidden initially
                  cy.get('.approveBtn').should('not.exist');
                  cy.get('.eventDescription').should('not.exist');

                  // Click the expander button
                cy.get('.p-datatable-row-toggle-button').should('exist').click();

            });
            cy.get('.p-3').should('exist');

            // Verify that the elements are now visible
            cy.get('.rejectBtn').should('be.visible');
            cy.get('.approveBtn').should('be.visible');
            cy.get('.eventDescription').should('be.visible');
        })

        // Check the images
        cy.get('.p-datatable-row-toggle-button').eq(0).click(); // Close the first row

        const events = [
            { row: 0,
                images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                ]
            },
            { row: 1,
                images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                    'http://127.0.0.1:8001/uploads/chili1.jpg',
                ]
            },
            { row: 2,
                images: [
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                    'http://127.0.0.1:8001/uploads/chili1.jpg',
                    'http://127.0.0.1:8001/uploads/chilidisaster.jpg',
                ]
            },
            { row: 3,
                images: []
            }
        ];

        events.forEach((event, index) => {
            const rowIndex = event.row;
            cy.get('.p-datatable-row-toggle-button').eq(rowIndex).click(); // Expand the current row

            cy.get('table').find('tbody').find('tr').eq(rowIndex + 1).within(() => {
                if (rowIndex == 3) { // Test if there is no image
                    cy.get('p').eq(3).should('have.text', 'Images: No images included');
                }
                else {              // Test if there are images
                    event.images.forEach((imgSrc, i) => {
                        cy.get('span').eq(i).find('img').should('have.attr', 'src', imgSrc);
                    });
                }
            });

            cy.get('.p-datatable-row-toggle-button').eq(rowIndex).click(); // Close the current row
        });
    })
    after(()=>{
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
    })
})

//end of pending events uploads