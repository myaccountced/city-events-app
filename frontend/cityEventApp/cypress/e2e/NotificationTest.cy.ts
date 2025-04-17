import {loginHelper} from "./LoginHelper";

const frontendURL = Cypress.env('FRONTEND_URL');
const backendURL = Cypress.env('API_BASE_URL');


// Story 52 - Registered User Receives Notifications for Followed Events and Users
describe("Email notifications", () => {
    before(() => {
        cy.mhDeleteAll();
        cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force ' +
            '&& php bin/console doctrine:database:create && php bin/console doctrine:schema:create ' +
            '&& php bin/console app:load-multiple-test-fixtures BookmarkFixtures ModeratorFixture');
    })

    it("Email notifications for followers and bookmarks", () => {
        // zuUser in UserFixtures.php, eventCreator in BookmarkFixtures, Moderator in ModeratorFixture

        // So we know which event has been approved!
        let stringFromHell = "";
        let eventID;
        cy.intercept(backendURL + '/events/mod', (request) => {
            eventID = request.body.id;
            request.continue();
        }).as("approval");


//REGION START Users can follow each other

        // Go to main page
        cy.visit("/");
        cy.get(".event-item", { timeout: 10000 } ).first().get('.eventButton').click();
        cy.get('.eventCreator').first().click();

        // Going to eventCreator's profile page as a guest
        cy.url().should('contain', '/profile?username=eventCreator');
        cy.get('#username', { timeout: 10000 }).should('contain.text', 'eventCreator');

        // Guests cannot see the email of others
        cy.get('#userEmail').should('not.exist');

        // Guests cannot follow people
        cy.get('#followUnfollowButton').should('not.exist');
        cy.get('#followStatus').should('not.exist')

        // Logging in as zuUser
        loginHelper('zueventsproject@gmail.com', '@Password1');

        // zuUser cannot follow themselves
        cy.contains('.p-menubar-item-link', 'Profile').click();
        cy.get('#username', { timeout: 10000 }).should('contain.text', 'zuUser');
        cy.get('#userEmail').should('contain.text', 'zueventsproject@gmail.com');
        cy.get('#followUnfollowButton').should('not.exist');
        cy.get('#followStatus').should('not.exist')

        // Going back to main page
        cy.contains('.p-menubar-item-link', 'Event').click();
        cy.get(".event-item").first().get('.eventButton').click();
        cy.get('.eventCreator').click();

        // zuUser can visit eventCreator's profile page
        cy.url().should('contain', '/profile?username=eventCreator');
        cy.get('#username', { timeout: 10000 }).should('contain.text', 'eventCreator');

        // Users cannot see the email of other users
        cy.get('#userEmail').should('not.exist');

        // zuUser CAN follow username1
        cy.get('#followUnfollowButton').should('contain', 'Follow');
        cy.get('#followStatus').should('not.exist')
        cy.get('#followUnfollowButton').click();
        cy.get('#followUnfollowButton').should('contain.text', 'Unfollow');
        cy.get('#followStatus').should('exist').and('contain.text', 'Following');

// REGION END



// REGION START followers receive emails on new posts being approved

        // Check how many emails have been sent BEFORE any notifications:
        let numOfEmails = 0;
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    // This is how many emails we have BEFORE sending anything
                    numOfEmails = emails.length ? emails.length: 0;
                    return true;
                });
        }, { timeout: 20000, interval: 2000 });

        // logging in as a user with a follower
        loginHelper('eventCreator', '@Password1');

        // creating new event:
        cy.contains(".p-menubar-item-link", "Post New Event", { timeout: 10000 }).click();

        cy.get('#eventTitle', { timeout: 10000 }).type('Folk Fest Saskatoon');
        cy.get('#eventDescription', { timeout: 10000 }).type('It\'s Folk Festival! Come listen to some music!');
        cy.get('#eventLocation').type('Saskatoon');
        cy.get('#eventStartDate').type('2025-07-10');
        cy.get('#eventEndDate').type('2026-07-13');
        cy.get('#eventStartTime').type('13:00');
        cy.get('#eventEndTime').type('17:00');
        cy.get(`[data-cy=category-tag-music]`).first().click();
        cy.get('#eventAudience').select('General');
        cy.get('#eventLink').type('https://saskatoonfolkfest.com/');

        cy.contains('button', 'Post Event').click();
        cy.contains('h1', 'Your event has been submitted for moderator review.');

        // logging in as a moderator to approve the event
        loginHelper('Moderator', 'ABC123def');
        cy.contains(".p-menubar-item-link", "Moderator Tools").click();

        // Approving:
        cy.contains('[data-pc-section=bodyrow]', 'Folk Fest Saskatoon').within((event) => {
            cy.wrap(event).get('button.p-datatable-row-toggle-button').click();
        });

        cy.get('button.approveBtn', { timeout: 10000 }).click();

        cy.wait("@approval");

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(2000);


        // Checking the email is sent and good:
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    // Check if an email was received
                    if (!emails.length) return false;

                    // TWO emails should have been sent, because this user has TWO followers!
                    if (numOfEmails + 2 != emails.length) return false;
                    numOfEmails = emails.length;

                    const goodEmails = emails.filter(e => {
                        return e.Content.Headers.Subject.some(subj => subj.includes('eventCreator posted "Folk Fest Saskatoon"'));
                    });


                    // Check if there is an email with the correct header
                    if (!goodEmails) return false;

                    cy.wrap(goodEmails).should('have.length', 2).each(email => {
                        const bodyType = 'text/html; charset=utf-8';

                        const emailContent = email.MIME.Parts.filter(mime => {
                            const contentType = mime.Headers['Content-Type'] || null;

                            if (contentType) {
                                return contentType.includes(bodyType);
                            }
                        });

                        // NOTE: If you EVER edit follower_update_email.html.twig, use this value to get the new string from hell:
                        //console.log(emailContent[0].Body.toString());

                        stringFromHell = "<!DOCTYPE html>\r\n" +
                            "<html>\r\n" +
                            "    <head>\r\n" +
                            "        <meta charset=3D\"UTF-8\">\r\n" +
                            "=\r\n" +
                            "        <title>Update Email</title>\r\n" +
                            "        <link rel=3D\"icon\" href=3D\"da=\r\n" +
                            "ta:image/svg+xml,<svg xmlns=3D%22http://www.w3.org/2000/svg%22 viewBox=3D%2=\r\n" +
                            "20 0 128 128%22><text y=3D%221.2em%22 font-size=3D%2296%22>=E2=9A=AB=\r\n" +
                            "=EF=B8=8F</text><text y=3D%221.3em%22 x=3D%220.2em%22 font-size=3D%2276%22 =\r\n" +
                            "fill=3D%22%23fff%22>sf</text></svg>\">\r\n" +
                            "            <style>\r\n" +
                            "        .emai=\r\n" +
                            "l-container {\r\n" +
                            "            max-width: 600px;\r\n" +
                            "            margin: 0 auto;=\r\n" +
                            "\r\n" +
                            "            padding: 20px;\r\n" +
                            "            background-color: #f4f4f9;\r\n" +
                            "  =\r\n" +
                            "          border-radius: 8px;\r\n" +
                            "            box-shadow: 0 4px 12px rgba(0, =\r\n" +
                            "0, 0, 0.1);\r\n" +
                            "            font-family: 'Arial', sans-serif;\r\n" +
                            "            c=\r\n" +
                            "olor: #333;\r\n" +
                            "            font-size: 18px;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        a {\r\n" +
                            " =\r\n" +
                            "           color: #007BFF;\r\n" +
                            "            text-decoration: none;\r\n" +
                            "        }=\r\n" +
                            "\r\n" +
                            "\r\n" +
                            "        a:hover {\r\n" +
                            "            text-decoration: underline;\r\n" +
                            "       =\r\n" +
                            " }\r\n" +
                            "\r\n" +
                            "        .description {\r\n" +
                            "            padding-top: 1em;\r\n" +
                            "          =\r\n" +
                            "  padding-bottom: 1em;\r\n" +
                            "            border-radius: 8px;\r\n" +
                            "            box-=\r\n" +
                            "shadow: 0 4px 12px rgba(0, 0, 0, 0.1);\r\n" +
                            "            width: 100%;\r\n" +
                            "       =\r\n" +
                            "     background-color: #fafaff;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        .description > p {=\r\n" +
                            "\r\n" +
                            "            padding: 1em;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        .link {\r\n" +
                            "           =\r\n" +
                            " padding-top: 1em;\r\n" +
                            "            font-size: 12px;\r\n" +
                            "        }\r\n" +
                            "    </style=\r\n" +
                            ">\r\n" +
                            "\r\n" +
                            "                    </head>\r\n" +
                            "    <body>\r\n" +
                            "            <div class=3D=\r\n" +
                            "\"email-container\">\r\n" +
                            "        <p class=3D\"title\">\r\n" +
                            "            <a href=3D\"h=\r\n" +
                            "ttp://localhost:5173/profile?username=3DeventCreator\">eventCreator</a> post=\r\n" +
                            "ed \"<a\r\n" +
                            `                    href=3D"http://localhost:5173/event/${eventID}">Folk Fe=\r\n` +
                            "st Saskatoon</a>\"\r\n" +
                            "        </p>\r\n" +
                            "\r\n" +
                            "        <div class=3D\"description\">=\r\n" +
                            "\r\n" +
                            "            <p>It&#039;s Folk Festival! Come listen to some music!</p>=\r\n" +
                            "\r\n" +
                            "        </div>\r\n" +
                            "\r\n" +
                            "\r\n" +
                            "        <p class=3D\"location\"><strong>Location:</=\r\n" +
                            "strong> Saskatoon</p>\r\n" +
                            "        <p class=3D\"date\"><strong>Date:</strong> 20=\r\n" +
                            "25-07-10 to 2026-07-13</p>\r\n" +
                            "        <p class=3D\"time\"><strong>Time:</stron=\r\n" +
                            "g> 13:00 to 17:00</p>\r\n" +
                            "        <p class=3D\"categories\"><strong>Categories:=\r\n" +
                            "</strong> Music</p>\r\n" +
                            "        <p class=3D\"audience\"><strong>Audience:</stro=\r\n" +
                            "ng> General</p>\r\n" +
                            "\r\n" +
                            "        <p class=3D\"link\"><a href=3D\"http://localhost:=\r\n" +
                            `5173/event/${eventID}">Go to Event</a></p>\r\n` +
                            "\r\n" +
                            "    </div>\r\n" +
                            "    </body>\r\n" +
                            "</html>=";

                        emailContent.some(thing => {
                            cy.wrap(thing.Body.toString()).should('contain', stringFromHell);
                        })

                        cy.visit("/event/" + eventID);

                        // rough draft body content:
                        /*
                            <p><a>eventCreator</a> posted <a>"Folk Fest Saskatoon"</a></p>
                            <p>It's Folk Festival! Come listen to some music!</p>

                            <p><strong>Location:</strong> Saskatoon</p>
                            <p><strong>Date:</strong> 2025-07-10 to 2025-07-13</p>
                            <p><strong>Time:</strong> 1:00pm to 5:00pm</p>
                            <p><strong>Categories:</strong> Music</p>
                            <p><strong>Audience:</strong> General</p>

                            <a>Go to Event</a>
                         */

                    });
                });
        }, { timeout: 20000 });

        // We should be at the event now
        cy.url().should('contain', frontendURL + '/event/');

        cy.get(".eventTitle").should('contain.text', 'Folk Fest Saskatoon');

// REGION END



// REGION START followers receive emails on bookmarked posts being edited

        // Moderator must approve the edited event (already exists in a fixture)
        //loginHelper('Moderator', 'ABC123def');
        cy.contains(".p-menubar-item-link", "Moderator Tools").click();

        // Approving:
        cy.contains('[data-pc-section=bodyrow]', 'Bookmark this event').within((event) => {
            cy.wrap(event).get('button.p-datatable-row-toggle-button').click();
        });
        cy.get('button.approveBtn', { timeout: 10000 }).click();

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(2000);

        cy.wait("@approval");

        // Checking the email is sent and good:
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    // Check if an email was received
                    if (!emails.length) return false;

                    // only ONE email should have been sent since the last email!!!
                    if (numOfEmails + 2 != emails.length) return false;
                    numOfEmails = emails.length;

                    const goodEmails = emails.filter(e => {
                        return e.Content.Headers.Subject.some(subj => subj.includes('eventCreator updated "Bookmark this event"'));
                    });


                    // Check if there is an email with the correct header
                    if (!goodEmails) return false;

                    cy.wrap(goodEmails).should('have.length', 2).each(email => {
                        const bodyType = 'text/html; charset=utf-8';

                        const emailContent = email.MIME.Parts.filter(mime => {
                            const contentType = mime.Headers['Content-Type'] || null;

                            if (contentType) {
                                return contentType.includes(bodyType);
                            }
                        });

                        // NOTE: If you EVER edit follower_update_email.html.twig, use this value to get the new string from hell:
                        //console.log(emailContent[0].Body.toString());

                        stringFromHell = "<!DOCTYPE html>\r\n" +
                            "<html>\r\n" +
                            "    <head>\r\n" +
                            "        <meta charset=3D\"UTF-8\">\r\n" +
                            "=\r\n" +
                            "        <title>Update Email</title>\r\n" +
                            "        <link rel=3D\"icon\" href=3D\"da=\r\n" +
                            "ta:image/svg+xml,<svg xmlns=3D%22http://www.w3.org/2000/svg%22 viewBox=3D%2=\r\n" +
                            "20 0 128 128%22><text y=3D%221.2em%22 font-size=3D%2296%22>=E2=9A=AB=\r\n" +
                            "=EF=B8=8F</text><text y=3D%221.3em%22 x=3D%220.2em%22 font-size=3D%2276%22 =\r\n" +
                            "fill=3D%22%23fff%22>sf</text></svg>\">\r\n" +
                            "            <style>\r\n" +
                            "        .emai=\r\n" +
                            "l-container {\r\n" +
                            "            max-width: 600px;\r\n" +
                            "            margin: 0 auto;=\r\n" +
                            "\r\n" +
                            "            padding: 20px;\r\n" +
                            "            background-color: #f4f4f9;\r\n" +
                            "  =\r\n" +
                            "          border-radius: 8px;\r\n" +
                            "            box-shadow: 0 4px 12px rgba(0, =\r\n" +
                            "0, 0, 0.1);\r\n" +
                            "            font-family: 'Arial', sans-serif;\r\n" +
                            "            c=\r\n" +
                            "olor: #333;\r\n" +
                            "            font-size: 18px;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        a {\r\n" +
                            " =\r\n" +
                            "           color: #007BFF;\r\n" +
                            "            text-decoration: none;\r\n" +
                            "        }=\r\n" +
                            "\r\n" +
                            "\r\n" +
                            "        a:hover {\r\n" +
                            "            text-decoration: underline;\r\n" +
                            "       =\r\n" +
                            " }\r\n" +
                            "\r\n" +
                            "        .description {\r\n" +
                            "            padding-top: 1em;\r\n" +
                            "          =\r\n" +
                            "  padding-bottom: 1em;\r\n" +
                            "            border-radius: 8px;\r\n" +
                            "            box-=\r\n" +
                            "shadow: 0 4px 12px rgba(0, 0, 0, 0.1);\r\n" +
                            "            width: 100%;\r\n" +
                            "       =\r\n" +
                            "     background-color: #fafaff;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        .description > p {=\r\n" +
                            "\r\n" +
                            "            padding: 1em;\r\n" +
                            "        }\r\n" +
                            "\r\n" +
                            "        .link {\r\n" +
                            "           =\r\n" +
                            " padding-top: 1em;\r\n" +
                            "            font-size: 12px;\r\n" +
                            "        }\r\n" +
                            "    </style=\r\n" +
                            ">\r\n" +
                            "\r\n" +
                            "                    </head>\r\n" +
                            "    <body>\r\n" +
                            "            <div class=3D=\r\n" +
                            "\"email-container\">\r\n" +
                            "        <p class=3D\"title\">\r\n" +
                            "            <a href=3D\"h=\r\n" +
                            "ttp://localhost:5173/profile?username=3DeventCreator\">eventCreator</a> upda=\r\n" +
                            "ted \"<a\r\n" +
                            `                    href=3D"http://localhost:5173/event/${eventID}">Bookma=\r\n` +
                            "rk this event</a>\"\r\n" +
                            "        </p>\r\n" +
                            "\r\n" +
                            "        <div class=3D\"description\">=\r\n" +
                            "\r\n" +
                            "            <p>This is the description of the event</p>\r\n" +
                            "        </div>=\r\n" +
                            "\r\n" +
                            "\r\n" +
                            "\r\n" +
                            "        <p class=3D\"location\"><strong>Location:</strong> Martensvi=\r\n" +
                            "lle</p>\r\n" +
                            "        <p class=3D\"date\"><strong>Date:</strong> 2025-02-02 to 20=\r\n" +
                            "25-12-12</p>\r\n" +
                            "        <p class=3D\"time\"><strong>Time:</strong> 00:00</p>=\r\n" +
                            "\r\n" +
                            "        <p class=3D\"categories\"><strong>Categories:</strong> Music, Spor=\r\n" +
                            "ts</p>\r\n" +
                            "        <p class=3D\"audience\"><strong>Audience:</strong> General</=\r\n" +
                            "p>\r\n" +
                            "\r\n" +
                            `        <p class=3D"link"><a href=3D"http://localhost:5173/event/${eventID}"=\r\n` +
                            ">Go to Event</a></p>\r\n" +
                            "\r\n" +
                            "    </div>\r\n" +
                            "    </body>\r\n" +
                            "</html>";

                        emailContent.some(thing => {
                            cy.wrap(thing.Body.toString()).should('contain', stringFromHell);
                        })

                        cy.visit("/event/" + eventID);

                        // rough draft body content:
                        /*
                            <p><a>eventCreator</a> updated <a>"Bookmark this event"</a></p>
                            <p>This is the description of the event</p>

                            <p><strong>Location:</strong> Martensville</p>
                            <p><strong>Date:</strong> 2025-02-02 to 2025-12-12</p>
                            <p><strong>Time:</strong> 12:00am</p>
                            <p><strong>Categories:</strong> Music, Sports</p>
                            <p><strong>Audience:</strong> General</p>

                            <a>Go to Event</a>
                         */

                    });
                });
        }, { timeout: 20000 });

        // We should be at the event now
        cy.url().should('contain', frontendURL + '/event/');

        cy.get(".eventTitle").should('contain.text', 'Bookmark this event');

// REGION END



// REGION START unfollowing a user will stop the notifications

        // Logging in as zuUser who is following eventCreator to unfollow
        loginHelper('zueventsproject@gmail.com', '@Password1');
        cy.contains('.event-item', 'Bookmark this event').within((event) => {
            // Un-bookmark that event too!
            cy.wrap(event).get('.pi-bookmark-fill').should('exist');
            cy.wrap(event).get('.bookmarkButton').click();
            cy.wrap(event).get('.pi-bookmark-fill').should('not.exist');

            // Go to the profile page of eventCreator
            cy.wrap(event).get('.eventCreator').click();
        })

        cy.url().should('contain', '/profile?username=eventCreator');
        cy.get('#username', { timeout: 10000 }).should('contain.text', 'eventCreator');

        // unfollowing:
        cy.get('#followStatus').should('exist').and('contain.text', 'Following');
        cy.get('#followUnfollowButton').should('contain.text', 'Unfollow');
        cy.get('#followUnfollowButton').click();
        cy.get('#followUnfollowButton').should('contain.text', 'Follow');
        cy.get('#followStatus').should('not.exist')

        // eventCreator will now create another event
        loginHelper('eventCreator', '@Password1');

        // creating new event:
        cy.contains(".p-menubar-item-link", "Post New Event", { timeout: 10000 }).click();

        cy.get('#eventTitle').type('Another Event with NO Notification');
        cy.get('#eventDescription', { timeout: 10000 }).type('Hello again! Welcome to the description');
        cy.get('#eventLocation').type('Saskatoon');
        cy.get('#eventStartDate').type('2025-07-11');
        cy.get('#eventEndDate').type('2026-07-12');
        cy.get('#eventStartTime').type('12:00');
        cy.get('#eventEndTime').type('13:00');
        cy.get(`[data-cy=category-tag-music]`).first().click();
        cy.get('#eventAudience').select('Family Friendly');
        cy.get('#eventLink').type('https://www.google.com/');

        cy.contains('button', 'Post Event').click();
        cy.contains('h1', 'Your event has been submitted for moderator review.');

        // logging in as a moderator to approve the event
        loginHelper('Moderator', 'ABC123def');
        cy.contains(".p-menubar-item-link", "Moderator Tools").click();

        // Approving:
        cy.contains('[data-pc-section=bodyrow]', 'Another Event with NO Notification').within((event) => {
            cy.wrap(event).get('button.p-datatable-row-toggle-button').click();
        });
        cy.get('button.approveBtn', { timeout: 10000 }).click();

        cy.wait("@approval");

        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(2000);

        // Checking that no email was sent:
        cy.waitUntil(() => {
            return cy.request('GET', 'http://localhost:8025/api/v2/messages')
                .its('body.items')
                .then((emails) => {
                    // Check if an email was received
                    if (!emails.length) return false;

                    // only ONE email should have been sent since the last email!!! Since only ONE person is following!! ('followerUser' => 'follow@example.com')
                    if (numOfEmails + 1 != emails.length) return false;
                    numOfEmails = emails.length;

                    const goodEmails = emails.filter(e => {
                        return e.Content.Headers.Subject.some(subj => subj.includes('eventCreator posted "Another Event with NO Notification"'));
                    });

                    // Check if there is an email with the correct header
                    if (!goodEmails) return false;

                    cy.wrap(goodEmails).should('have.length', 1);

                    cy.wrap(goodEmails[0].Content.Headers.To[0]).should('contain', 'follow@example.com');
                    //return goodEmails[0].Content.Headers.To.includes("follow@example.com");
                });
        }, { timeout: 20000, interval: 2000 });

// REGION END



// REGION START un-bookmarking an event means no notifications

        // TODO: idk if this is important, but it's currently impossible to test

// REGION END

    });

    after(() => {
        cy.mhDeleteAll();
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures');
    });
});