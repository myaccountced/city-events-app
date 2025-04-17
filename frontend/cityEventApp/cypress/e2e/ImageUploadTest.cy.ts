// Kim's tests for uploading images
import 'cypress-file-upload';

describe('Post Event Image Upload - E2E Test', () => {
  before(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create &&  php bin/console doctrine:schema:create && php bin/console app:load-test-fixtures')
  })

  beforeEach(() => {
    cy.visit('/')
    cy.contains('button', 'Sign In').click()

    cy.get('#identifier').type('username1');
    cy.get('#password').type('@Password1');
    cy.intercept('/auth/signin').as('signIn');
    cy.get('button[type="submit"]').click();

    cy.wait('@signIn')

    cy.url({ timeout: 10000 }).should('include', '/');
    //cy.intercept('/events/media?*').as('getMedia');
    //cy.wait('@getMedia');

    cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click()
  });


  it('should successfully upload a single valid image', () => {
    // Navigate to the upload page
    cy.get('.p-menubar-item-link').eq(5).should('have.text', 'Post New Event').click();

    // Set up test data for the event
    cy.get('input[name="eventTitle"]', { timeout: 10000 }).type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2025-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2025-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Attach a valid image
    cy.get('[data-cy="photo-files"]').attachFile({
      filePath: './p1.jpg',
      fileName: 'p1.jpg',
      mimeType: 'image/jpeg',
    });

    // Submit the form
    cy.get('[data-cy="submit-button"]').click();

  });

  it('should successfully upload up to 3 valid images', () => {
    // Navigate to the upload page
    //cy.visit('/postevent');



    // Set up test data for the event
    cy.get('input[name="eventTitle"]').type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2025-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2025-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Attach multiple valid images
    cy.get('[data-cy="photo-files"]').attachFile([
      './p1.jpg',
      './p2.jpg',
      './p3.jpg'
    ]);

    // Submit the form
    cy.get('[data-cy="submit-button"]').click();

  });

  it('should prevent uploading an invalid file type', () => {
    // Navigate to the upload page
    //cy.visit('/postevent');

    // Set up test data for the event (to ensure all required fields are filled)
    cy.get('input[name="eventTitle"]').type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2025-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2025-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Attach an invalid file type
    cy.get('[data-cy="photo-files"]').attachFile('./pdf1.pdf');

    // Submit the form
    cy.get('[data-cy="submit-button"]').click();

    // Assert error message is displayed
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'Invalid file type. Only image files are allowed.');
  });

  it('should prevent uploading more than 3 images', () => {
    // Navigate to the upload page
    //cy.visit('/postevent');

    // Set up test data for the event (to ensure all required fields are filled)
    cy.get('input[name="eventTitle"]').type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2025-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2025-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Attach more than 3 images
    cy.get('[data-cy="photo-files"]').attachFile([
      './p1.jpg',
      './p2.jpg',
      './p3.jpg',
      './p4.jpg'
    ]);

    // Submit the form
    cy.get('[data-cy="submit-button"]').click();

    // Assert error message is displayed
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'You can upload up to 3 images only.');
  });

  it('should prevent uploading an image larger than 5MB', () => {
    // Navigate to the upload page
    //cy.visit('/postevent');

    // Set up test data for the event (to ensure all required fields are filled)
    cy.get('input[name="eventTitle"]').type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2024-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2024-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Attach a large image file
    cy.get('[data-cy="photo-files"]').attachFile('./p1Large.jpg');

    // Submit the form
    cy.get('[data-cy="submit-button"]').click();

    // Assert error message is displayed
    cy.get('[data-cy="error-message"]')
      .should('be.visible')
      .and('contain', 'File size must not exceed 5 MB');
  });

  it('should allow event creation without images', () => {
    // Navigate to the upload page
    //cy.visit('/postevent');


    // Set up all event details
    cy.get('input[name="eventTitle"]').type('Test Event');
    cy.get('textarea[name="eventDescription"]').type('Test Description');
    cy.get('input[name="eventLocation"]').type('Test Location');

    // Select category and audience
    cy.get(`[data-cy=category-tag-education]`).first().click();
    //cy.get('select[name="eventCategory"]').select(['Education']);
    cy.get('select[name="eventAudience"]').select(['Youth']);

    // Set dates and times
    cy.get('input[name="eventStartDate"]').type('2025-12-01');
    cy.get('input[name="eventStartTime"]').type('10:00');
    cy.get('input[name="eventEndDate"]').type('2025-12-02');
    cy.get('input[name="eventEndTime"]').type('18:00');

    // Submit the form without images
    cy.get('[data-cy="submit-button"]').click();


});

  after(() => {
    cy.exec('cd ../../backend/cityEventApp && php bin/console app:load-test-fixtures');
  })
});
// end of kim's tests