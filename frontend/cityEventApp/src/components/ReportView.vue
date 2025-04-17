<script setup lang="ts">
import { ref, watch } from 'vue';
import useEventFetch from '../scripts/EventFetch';

// Define the props for this component
const props = defineProps<{
  eventID: number; // to track selected event
  eventTitle: string; // to display title on report
  isReportVisible: boolean; // Controls modal visibility
  reportReasons: string[]; // Array of reporting reasons
}>();

// Define emit to notify parent component
const emit = defineEmits<{
  (event: 'cancel-report') : void;
}>();

// Track the selected report reason
const selectedReason = ref<string | null>(null);
const otherReasonText = ref('');
const showErrorMessage = ref(false);
const errMessageText = ref('');
const isReportSubmitted = ref(false);  // Track submission status

// Watch for prop changes to reset the report form
watch(props, (newProps) => {
  if (!newProps.isReportVisible) {
    selectedReason.value = null; // Unselect active reason
    showErrorMessage.value = false; // Hide error message if visible
    errMessageText.value = ''; // reset error message text
  }
});
// Watch for changes in the selected reason or otherReasonText to hide error message
watch([selectedReason, otherReasonText], () => {
  showErrorMessage.value = false; // Hide the error message whenever the user updates the reason
  errMessageText.value = '';
});

// Handle the submit action
async function submitReport() {
  // If "Other" is selected, check if the textarea has a value
  if (selectedReason.value === 'Other' && (!otherReasonText.value || otherReasonText.value.trim() === '')) {
    //console.log('otherReasonText: ', otherReasonText.value.trim())
    showErrorMessage.value = true; // Show error if textarea is empty
    errMessageText.value = 'Please provide details';
    otherReasonText.value = '';
  }
  else if (selectedReason.value) {
    const reasonToSubmit = selectedReason.value === 'Other' ? otherReasonText.value.trim() : selectedReason.value;
    if (reasonToSubmit.length > 255)
    {
      showErrorMessage.value = true; // Show error if no reason is selected
      errMessageText.value = 'Reason cannot exceed 255 characters';
      return;
    }
    if (props.eventID && reasonToSubmit) {
      const payload = {
        eventID: props.eventID,
        reason: reasonToSubmit,
      };

      try {
        // Send POST request to backend
        const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/api/reports', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        if (response.ok) {
          const result = await response;
          console.log('Report successfully submitted:', result);
          isReportSubmitted.value = true; // successful status
          showErrorMessage.value = false;
        } else {
          const error = await response.json();
          console.error('Error:', error.message);
          // Handle the error here, show feedback from server to the user
          isReportSubmitted.value = false;
          showErrorMessage.value = true; // Show error if when getting error response from server
          errMessageText.value = 'Fail to submit report';
        }
      } catch (error) {
        console.error('Error:', error);
        // Handle network errors here, e.g., show a message to the user
        showErrorMessage.value = true; // Show error when there is no reply from backend
        isReportSubmitted.value = false;
        errMessageText.value = 'Server is not available at the moment';
      }
    } else {
      showErrorMessage.value = true; // Show error when payload is not available
      errMessageText.value = 'Can not submit in-sufficient data';
      // Show an error message or alert for missing reason
    }
  } else {
    showErrorMessage.value = true; // Show error if no reason is selected
    errMessageText.value = 'Please enter a reason to proceed!';
  }
}

// Handle the close action
function closeReport() {
  emit('cancel-report'); // revoke the closeReportModal() in parent
}
</script>

<template>
  <div v-if="isReportVisible" class="modal-overlay">
    <div class="modal">
      <h4>Report Event</h4>
      <h4>{{eventTitle}}</h4>

      <!-- Report Reasons -->
      <div class="report-form mb-3">
        <h5>Select a reason:</h5>
        <div class="row">
          <div class="col-12" v-for="reason in reportReasons" :key="reason">
            <div class="form-check report-reason">
              <input
                  class="form-check-input"
                  type="radio"
                  :value="reason"
                  v-model="selectedReason"
                  :id="('reason-' + reason).replace(' ', '')"
                  :disabled="isReportSubmitted"
              />
              <label class="form-check-label" :for="'reason-' + reason">
                {{ reason }}
              </label>
            </div>
          </div>
        </div>

        <!-- Additional input for the "Other" option -->
        <div v-if="selectedReason === 'Other'" class="mt-3">
          <label for="otherReason" class="form-label">Please specify:</label>
          <textarea
              id="otherReason"
              v-model="otherReasonText"
              class="form-control"
              rows="3"
              placeholder="Enter your reason"
              :disabled="isReportSubmitted"
          ></textarea>
        </div>

        <!-- Error message for invalid input -->
        <div v-if="showErrorMessage && errMessageText " class="alert alert-danger mt-2 error-message" role="alert">
          {{ errMessageText }}
        </div>
        <!-- Success Message after successful submission -->
        <div v-if="isReportSubmitted" class="alert alert-success mt-3 success-message" role="alert">
          Your report has been successfully submitted!
        </div>
      </div>

      <!-- Buttons -->
      <button v-if="!isReportSubmitted" @click="submitReport" id="submitButton">Submit</button>
      <button v-if="!isReportSubmitted" @click="closeReport" id="cancelButton">Cancel</button>
      <!-- OK Button after successful submission -->
      <button v-if="isReportSubmitted" @click="closeReport" id="okButton">OK</button>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgb(255, 255, 255,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  display: block;
  background-color: var(--p-red-500);
  border-radius: 10px;
  box-shadow: 0 6px 30px rgba(93, 142, 41, 0.4);
  width: 90%;
  max-width: 400px;
  text-align: left;
  padding-left: 20px;
  padding-top: 10px;
}

h4, h5 {
/*  color: #1a40da;*/
  margin-bottom: 20px;
}

button {
  background-color: #aa330e;
  color: #ffffff;
  border: none;
  padding: 12px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.3s;
}

button:hover {
  background-color: #e65c00;
}

button + button {
  margin-left: 10px;
}
</style>