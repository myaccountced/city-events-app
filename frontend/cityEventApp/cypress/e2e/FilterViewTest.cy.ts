// 'http://127.0.0.1:8001/events?offset=0&limit=20'
import {getAllCategoryNames, getAllCategories} from "../../src/components/interfaces/Category";
import {getAudience} from "../../src/components/interfaces/Audience";


// Story34 - Chau
/*
describe('Event Category Filter Test', () => {
    beforeEach(() => {
        cy.visit('/'); // Visit the event page
        cy.get('.eventContainer .event', { timeout: 10000 })
          .should('have.length.at.least', 20);
        cy.get('#filterButton').click(); // Open the filter modal
        cy.contains('.p-tree-node', "Categories").as("CategoryMenu");
        cy.get('@CategoryMenu').within((el) => {
            //cy.wrap(el).get('button').click();
            cy.wrap(el).get('.p-tree-node-children').should('not.exist');
            cy.wrap(el).get('button').first().click();
            cy.wrap(el).get('.p-tree-node-children').should('exist');
        });
    });

    const categories = getAllCategories();
    const category1 = categories[Math.floor(Math.random() * categories.length)];
    const category3 = categories.sort(() => Math.random() - 0.5).slice(0, 3);

    it('should display all color coded categories', () => {
        cy.get("#cancelFilters", { timeout: 10000 }).click();
        cy.get('.sortTitle', { timeout: 10000 }).click();
        categories.forEach(category => {
            cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);
            cy.get(`[data-cy=category-tag-${category.name.toLowerCase().replace(/\s/g, '-')}]`).should('be.visible');
        });
    });

    it('should filter events by 1 selected category', () => {
        const categoryTag = `category-tag-${category1.name.toLowerCase().replace(/\s/g, '-')}`;
        // const categoryQueryParam = category1.name.replace(/\s/g, '+'); // Convert spaces to "+"

        // Select "Education" category inside checkbox-wrapper
        cy.contains(".p-tree-node-content", "" + category1.name).within((el) => {
            cy.wrap(el).get('input').should('not.be.checked').check();
            cy.wrap(el).get('input').should('be.checked');
        })

        // Apply filter
        cy.get('#applyFilters').click();

        // Wait dynamically until at least 20 events load OR timeout after 1000ms
        cy.get('.eventContainer .event', { timeout: 20000 })
            .should('have.length.at.least', 20);
        //cy.scrollTo('bottom'); // Scroll to the bottom

        //cy.wait(5000);


        // Assert all displayed events belong to "Education" category
        cy.get('.eventContainer .event').each(($event) => {
            cy.wrap($event).within(() => {
                //Verify the label "Education"
                cy.get('[data-cy^=category-tag]')
                    .should('have.attr', 'data-cy', categoryTag);
                // Verify the icon for "Education" category
                cy.get('[data-cy^=event-category-icon]')
                    .should('have.class', category1.icon); // PrimeVue icon class
                // Verify the background color for "Education" category
                cy.get('[data-cy^=category-tag-]')
                    .should('have.css', 'background-color', category1.bgColorTesting); // Verify the background has the comput4ed value of /var\(--p-blue-400\)/
            });
        });
    });

    it('should filter events by multiple selected categories', () => {

        category3.forEach(category => {
            //const catTag = `category-tag-${category.name.toLowerCase().replace(/\s/g, '-')}`;
            //cy.get(`.checkbox-wrapper [data-cy=${catTag}]`).click();
            cy.contains(".p-tree-node-content", "" + category.name).within((el) => {
                cy.wrap(el).get('input').should('not.be.checked').check();
                cy.wrap(el).get('input').should('be.checked');
            })
        });

        // Apply filter
        cy.get('#applyFilters').click();

        // There should only be 2 events because there are only 2 events with 3 or more categories
        cy.get('.eventContainer .event', { timeout: 20000 })
            .should('have.length.least', 2);
        cy.scrollTo('bottom'); // Scroll to the bottom

        // Assert all displayed events belong to one of the selected categories
        cy.get('.eventContainer .event').each(($event) => {
            cy.wrap($event).within(() => {
                // Get category data attributes
                for (let i = 0; i < category3.length; i++) {
                    cy.contains('[data-cy^=category-tag]', "" + category3.at(i).name).invoke('attr', 'data-cy').then((categoryTag) => {
                        expect(category3.map(cat => `category-tag-${cat.name.toLowerCase().replace(/\s/g, '-')}`)).to.include(categoryTag);
                    });

                    // Verify the corresponding icon and background color dynamically
                    cy.contains(' .eventCategory > [data-cy^=category-tag]', "" + category3.at(i).name).then(($categoryTag) => {
                        const category = $categoryTag.attr('data-cy');
                        const categoryData = category3.find(cat => `category-tag-${cat.name.toLowerCase().replace(/\s+/g, '-')}` === category);

                        if (categoryData) {
                            cy.get('[data-cy^=event-category-icon]').should('have.class', categoryData.icon);
                            cy.wrap($categoryTag).should('have.css', 'background-color', categoryData.bgColorTesting);
                        }
                    });
                }
            });
        });
    });

    it('should filter events by all selected categories', () => {

        // Select all available categories
        categories.forEach((category) => {
            cy.contains(".p-tree-node-content", "" + category.name).within((el) => {
                cy.wrap(el).get('input').should('not.be.checked').check();
                cy.wrap(el).get('input').should('be.checked');
            })
        });

        // Apply filter
        cy.get('#applyFilters').click();

        // There are only 2 events with ALL categories!
        cy.get('.eventContainer .event', { timeout: 10000 })
            .should('have.length', 2);

        // Assert all displayed events belong to one of the selected categories
        cy.get('.eventContainer .event').each(($event) => {
            cy.wrap($event).within(() => {
                cy.get('[data-cy^=category-tag]').invoke('attr', 'data-cy').then((categoryTag) => {
                    const categoryFound = categories.find((cat) => categoryTag === `category-tag-${cat.name.toLowerCase().replace(/\s/g, '-')}`);
                    expect(categoryFound).to.not.be.undefined;
                });

                cy.get('[data-cy^=category-tag]').then(($categoryTag) => {
                    const categoryTag = $categoryTag.attr('data-cy');
                    const categoryName = categoryTag.replace('category-tag-', '').replace(/-/g, ' ');
                    const category = categories.find((cat) => cat.name.toLowerCase() === categoryName.toLowerCase());

                    if (category) {
                        // Verify the label
                        cy.wrap($categoryTag).contains(category.name);

                        // Verify the icon
                        cy.get('[data-cy^=event-category-icon]').should('have.class', category.icon);

                        // Verify the background color
                        cy.wrap($categoryTag)
                            .should('have.css', 'background-color', category.bgColorTesting);
                    }
                });
            });
        });
    });
});
*/

// Story 47 - Refactored Filtering (Morgan)
describe("Advanced Filtering by Multiple Filtering Properties", () => {
    before(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-multiple-test-fixtures' +
            ' EventFixtureTwoEvent EventFixtures lotsOfEvents');
        cy.visit('/');
    })

    it('Single-click filters and Advanced filters', () => {
// REGION START single-click filters work for categories
        cy.get('.sortTitle').click();
        cy.get('.sortTitle').click();

        cy.get(".eventCategory .p-tag", { timeout: 10000 }).contains("Health and Wellness", { timeout: 10000 }).should('exist');
        cy.get('#filterDisplayText').should('have.text', 'No filters applied');

        cy.get('.eventCategory .p-tag').contains("Health and Wellness").click();
        cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Health and Wellness');

        cy.get('.event-item')
            .should('have.length', 20)
            .each(($el) => {
                cy.wrap($el, { timeout: 10000 }).get('.eventCategory').contains('Health and Wellness', { timeout: 10000 });
            });

// REGION END



// REGION START single-click filters work for locations

        cy.get('.eventLocation .p-tag').contains("Warman").click();

        cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Warman');

        cy.get('.event-item')
            .should('have.length', 20)
            .each(($el) => {
                cy.wrap($el, { timeout: 10000 }).get('.eventLocation').contains('Warman', { timeout: 10000 });
            });

// REGION END


// REGION START single-click filters work for dates

        cy.get('.eventStartDate .p-tag').contains("2026-01-01").click();

        cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', '2026-01-01');

        cy.get('.event-item')
            .should('have.length', 20)
            .each(($el) => {
                cy.wrap($el).contains('.eventStartDate', '2026-01-01');
            });

// REGION END


// REGION START single-click filters work for audiences

        cy.get('.eventAudience .p-tag').contains("Teens and Up").click();

        cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'Teens and Up');
        cy.wait(3000);
        // cy.get('.eventAudience .p-tag')
        //     .should('have.length.at.least', 20)
        //     .each(($el) => {
        //     cy.wrap($el).should('contain.text', 'Teens and Up');
        // });

        cy.get('.eventAudience .p-tag').as('audienceTags');
        cy.get('@audienceTags').should('have.length.at.least', 20);

        // Check each element individually
        cy.get('@audienceTags').each(($el) => {
            cy.wrap($el).should('contain.text', 'Teens and Up');
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
        cy.contains(".p-tree-node-content", "Teens and Up").within((el) => {
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
        cy.contains(".p-tree-node-content", "General").within((el) => {
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
            .should('contain.text', 'Teens and Up')
            .and('contain.text', 'Arts and Culture');

        // There SHOULD be 22 events in the database that are both Arts and Culture and Teens and Up
        cy.get('.event-item')
            .should('have.length', 20)
            .each(($el) => {
                cy.wrap($el).contains('.eventCategory', 'Arts and Culture');
                cy.wrap($el).contains('.eventAudience', 'Teens and Up');
            });

// REGION END



// REGION START sorting also works when filtering
        cy.get('.sortTitle').click();

        cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);

        // Event Title 999 SHOULD be the first event to show up?
        cy.get('.event-item').first()
            .should('contain.text', 'Event Title 999')
            .and('contain.text', 'Arts and Culture')
            .and('contain.text', 'Teens and Up');

        cy.get('.sortTitle').click();

        cy.get('.event-item', { timeout: 10000 }).should('have.length', 20);

        // Event Title 144 SHOULD be the first event to show up?
        cy.get('.event-item').first()
            .should('contain.text', 'Event Title 144')
            .and('contain.text', 'Arts and Culture')
            .and('contain.text', 'Teens and Up');
// REGION END



// REGION START scrolling also works when filtering
        cy.scrollTo('bottom');

        cy.get('.event-item', { timeout: 10000 }).should('have.length', 23);

        // This should be the last item loaded?
        cy.get('.event-item').last()
            .should('contain.text', 'Event Title 999')
            .and('contain.text', 'Arts and Culture')
            .and('contain.text', 'Teens and Up');
// REGION END



// REGION START clearing filters

        cy.get('#clearFilters').click();
        cy.get('#filterDisplayText').should('have.text', 'No filters applied');
        // These two options that were not filtered on should appear again
        cy.contains(".eventCategory", "Sports", { timeout: 10000 }).should('exist');
        cy.contains(".eventAudience", "Family Friendly", { timeout: 10000 }).should('exist');

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

// REGION START filtering by multiple categories

        cy.get('#clearFilters').click();
        cy.get('#filterDisplayText', { timeout: 10000 }).should('contain.text', 'No filters applied')
        cy.get('#filterButton').click();

        cy.get('@CategoryMenu').within((el) => {
            cy.wrap(el).get('.p-tree-node-children').should('not.exist');
            cy.wrap(el).get('button').first().click();
            cy.wrap(el).get('.p-tree-node-children').should('exist');
        })

        // Selecting 2 category filters with some results
        cy.contains(".p-tree-node-content", "Arts and Culture").within((el) => {
            cy.wrap(el).get('input').should('not.be.checked').check();
            cy.wrap(el).get('input').should('be.checked');
        })

        cy.contains(".p-tree-node-content", "Sports").within((el) => {
            cy.wrap(el).get('input').should('not.be.checked').check();
            cy.wrap(el).get('input').should('be.checked');
        })

        cy.get('#applyFilters').click();

        cy.get('#filterDisplayText', { timeout: 10000 })
            .should('contain.text', 'Arts and Culture')
            .and('contain.text', 'Sports');

        // There should only be two events with ALL categories
        cy.get('.event-item')
            .should('have.length', 2)
            .each(($el) => {
                cy.wrap($el).contains('.eventCategory', 'Arts and Culture');
                cy.wrap($el).contains('.eventCategory', 'Sports');
            });

// REGION END


//REGION START advanced filter by location
        cy.get('#filterButton').click();

        cy.get('@CategoryMenu').within((el) => {
            cy.wrap(el).get('.p-tree-node-children').should('exist');
            cy.wrap(el).get('button').first().click();
            cy.wrap(el).get('.p-tree-node-children').should('not.exist');
        })

        cy.get('#locationFilterInput').type('Yorkton')

        cy.get('#applyFilters').click();

        cy.get('#filterDisplayText', { timeout: 10000 })
            .should('contain.text', 'Arts and Culture')
            .and('contain.text', 'Sports')
            .and('contain.text', 'Yorkton');

        cy.get('.event-item')
            .should('have.length', 1)
            .each(($el) => {
                cy.wrap($el).contains('.eventCategory', 'Arts and Culture');
                cy.wrap($el).contains('.eventCategory', 'Sports');
                cy.wrap($el).contains('.location', 'Yorkton');
            });

// REGION END


//REGION START advanced filter by date
        cy.get('#filterButton').click();

        cy.get('@CategoryMenu').within((el) => {
            cy.wrap(el).get('.p-tree-node-children').should('exist');
        })

        cy.contains(".p-tree-node-content", "Arts and Culture").within((el) => {
            cy.wrap(el).get('input').should('be.checked').check();
            cy.wrap(el).get('input').uncheck();
            cy.wrap(el).get('input').should('not.be.checked');
        })

        cy.contains(".p-tree-node-content", "Sports").within((el) => {
            cy.wrap(el).get('input').should('be.checked').check();
            cy.wrap(el).get('input').uncheck();
            cy.wrap(el).get('input').should('not.be.checked');
        })

        cy.contains(".p-tree-node-content", "Music").within((el) => {
            cy.wrap(el).get('input').should('not.be.checked').check();
            cy.wrap(el).get('input').check();
            cy.wrap(el).get('input').should('be.checked');
        })

        cy.get('#locationFilterInput').clear();
        cy.get('#locationFilterInput').type('Moose Jaw');

        cy.get('#startDateInput').type('2026-03-01');
        cy.get('#endDateInput').type('2026-03-01');

        cy.get('#applyFilters').click();

        cy.get('#filterDisplayText', { timeout: 10000 })
            .should('contain.text', 'Moose Jaw')
            .and('contain.text', 'Starting on 2026-3-1')
            .and('contain.text', 'Ending on 2026-3-1');

        cy.get('.event-item')
            .should('have.length', 1)
            .each(($el) => {
                cy.wrap($el).contains('.eventCategory', 'Music');
                cy.wrap($el).contains('.location', 'Moose Jaw');
                cy.wrap($el).contains('.startDate', '2026-03-01');
                cy.wrap($el).contains('.endDate', '2026-03-01');
            });

// REGION END
    })

    after(() => {
        cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures');
    })
})
// */