describe('Event Reporting', () => {
  beforeEach(() => {
    //cy.intercept('GET', '/events?limit=20&offset=0', {
    cy.intercept('GET', '/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval][]=1&sortField=eventStartDate&sortOrder=ASC', {
      statusCode: 200,
      body: Array.from({length: 20}, (e, index) => ({
        id: 1,
        title: `Event ${index + 1}`,
        description: `Description ${index +1}`,
        location: 'Location',
        startDate: "January 1, 2025",
        endDate: "January 1, 2025",
        audience: 'Audience',
        category: 'Category',
        images: 'images',
        startTime: '12:00:00',
        endTime: '17:00:00',
        links: 'links',
      }))
    }).as('initialEvents')

    // Stimulate Successful response from backend
    cy.intercept('POST', '/api/reports', {
      statusCode: 201,
      body : {
        message: 'Report created successfully', // success message from backend
      }
    }).as('SuccessfulSubmit');

    // Visit the event page where the report button is located
    cy.visit('/'); // route to the event page, that displays a list of events
    // stimulate response from backend
    cy.wait('@initialEvents');
  });


  it('displays report button for each event', () => {
    // Check that each event has a report button
    cy.get('div.eventContainer div.event .event-item').first().find('.report-button').should('exist'); // Adjust selector if needed
    cy.get('.report-button').should('have.length.greaterThan', 0); // Ensure there are multiple report buttons
  });

  it('opens the report form when clicking "Report" button', () => {
    // Click on the report button for the first event
    cy.get('div.eventContainer div.event .event-item').first().find('.report-button').click();

    // Verify the report form appears
    cy.get('.report-form').should('be.visible');
    cy.get('.report-reason').should('exist'); // Check for reason options
    cy.get('#submitButton').should('exist'); // Check for submit button
    cy.get('#cancelButton').should('exist'); // Check for cancel button
  });

  it('closes the report form when clicking "Cancel" button', () => {
    // Open the report form
    cy.get('div.eventContainer div.event .event-item').first().find('.report-button').click();

    // Click the "Cancel" button
    cy.get('#cancelButton').click();

    // Verify the report form is closed
    cy.get('.report-form').should('not.exist');
  });

  it('shows an error message when submitting without selecting a reason', () => {
    // Open the report form
    cy.get('.report-button').first().click();

    // Try submitting without selecting a reason
    cy.get('#submitButton').click();

    // Verify an error message is shown
    cy.get('.error-message').should('exist');
    cy.get('.error-message').should('contain', 'Please enter a reason to proceed!'); // Adjust message based on actual error text
  });

  it('shows an error message when selecting "Other" without providing details', () => {
    // Open the report form
    cy.get('.report-button').first().click();

    // Select "Other" as the reason without entering details
    cy.get('#reason-Other').click();

    cy.get('#submitButton').click();

    // Verify an error message is shown
    cy.get('.error-message').should('exist');
    cy.get('.error-message').should('contain', 'Please provide details'); // Adjust message based on actual error text
  });

  it('shows an error when "Other" reason details exceed 255 characters', () => {
    // Open the report form
    cy.get('.report-button').first().click();

    // Select "Other" as the reason and enter details with 256 characters
    cy.get('#reason-Other').click();
    cy.get('#otherReason').type('a'.repeat(256));
    cy.get('#submitButton').click();

    // Verify an error message is shown
    cy.get('.error-message').should('exist');
    cy.get('.error-message').should('contain', 'Reason cannot exceed 255 characters'); // Adjust message based on actual error text
  });

  it('successfully submits a report with Other reason of 1-character length', () => {
    // Open the report form by clicking on the report button for the first event
    cy.get('.report-button').first().click();

    // Select "Other" as the reason for reporting
    cy.get('#reason-Other').click();
    // Enter a 1-character reason in the text area for "Other" reason
    cy.get('#otherReason').type('a'); // Assuming "a" as the 1-character input
    // Submit the report form
    cy.get('#submitButton').click();

    // Wait for the mocked API call to complete
    cy.wait('@SuccessfulSubmit');

    // Verify that the success message is displayed
    cy.get('.success-message').should('exist');
    cy.get('.success-message').should('contain', 'Your report has been successfully submitted!'); // Adjust text as frontend

    // Verify that no error message is displayed
    cy.get('.error-message').should('not.exist');
  });

  it('successfully submits a report with Other reason of 255-character length', () => {
    // Open the report form by clicking on the report button for the first event
    cy.get('.report-button').first().click();

    // Select "Other" as the reason for reporting
    cy.get('#reason-Other').click();
    // Enter a 255-character reason in the text area for "Other" reason
    const longReason = 'a'.repeat(255); // Generate a string with 255 'a' characters
    cy.get('#otherReason').type(longReason);
    // Submit the report form
    cy.get('#submitButton').click();

    // Wait for the mocked API call to complete
    cy.wait('@SuccessfulSubmit');

    // Verify that the success message is displayed
    cy.get('.success-message').should('exist');
    cy.get('.success-message').should('contain', 'Your report has been successfully submitted!'); // Adjust text as frontend

    // Verify that no error message is displayed
    cy.get('.error-message').should('not.exist');
  });

  it('submits report successfully with a valid reason (non-"Other" option)', () => {
    // Open the report form
    cy.get('.report-button').first().click();

    // Select a valid reason (not "Other")
    cy.get('#reason-Spam').click();// Adjust option based on actual reason options
    cy.get('#submitButton').click();

    // Wait for the mocked API call to complete
    cy.wait('@SuccessfulSubmit');

    // Verify a success message is displayed
    cy.get('.success-message').should('exist');
    cy.get('.success-message').should('contain', 'Your report has been successfully submitted!');

    // Verify that no error message is displayed
    cy.get('.error-message').should('not.exist');
  });

  it('submits report successfully with "Other" reason and valid details then click Ok button', () => {
    // Open the report form
    cy.get('.report-button').first().click();

    // Select "Other" as the reason and enter valid details
    cy.get('#reason-Other').click();
    cy.get('#otherReason').type('This event is misleading');
    // Submit the report form
    cy.get('#submitButton').click();

    // Wait for the mocked API call to complete
    cy.wait('@SuccessfulSubmit');

    // Verify a success message is displayed
    cy.get('.success-message').should('exist');
    cy.get('.success-message').should('contain', 'Your report has been successfully submitted!');
    // Verify that no error message is displayed
    cy.get('.error-message').should('not.exist');
    // Verify OK button appear, Submit and Cancel buttons disappear
    cy.get('#submitButton').should('not.exist');
    cy.get('#cancelButton').should('not.exist');
    cy.get('#okButton').should('exist');

    // Click Ok button to close the form
    cy.get('#okButton').click();
    // Verify the report form is closed
    cy.get('.report-form').should('not.exist');
  });

  it('displays an error message when the backend server is not running', () => {
    // Mock the POST request to /api/reports/ and force the connection to fail
    cy.intercept('POST', '/api/reports', {
      statusCode:0,
      forceNetworkError: true, // Simulate a network error
    }).as('FailedSubmit');

    // Open the report form by clicking on the report button for the first event
    cy.get('.report-button').first().click();

    // Select "Other" as the reason for reporting and enter a valid reason
    cy.get('#reason-Spam').click();

    // Submit the report form
    cy.get('#submitButton').click();

    // Verify that the error message is displayed in the UI
    cy.get('.error-message').should('exist'); // Check if the error message element exists
    cy.get('.error-message').should('contain', 'Server is not available at the moment'); // display error message
  });
});