<script setup lang="ts">
import ImageUpload from './ImageUpload.vue'
import { nextTick, ref, onMounted } from 'vue'
import {getAllCategories, Category, getAllCategoryNames} from "@/components/interfaces/Category";
import { getAllAudiences, Audience } from '@/components/interfaces/Audience'
import { useAuth } from '@/useAuth'
import { useRouter } from 'vue-router'
import ProgressSpinner from 'primevue/progressspinner'
import CategoryComponent from '@/components/CategoryComponent.vue';
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber';
import SelectButton from 'primevue/selectbutton';

const {user, userId, loadUserFromLocalStorage, token} = useAuth();
const backendUrl = import.meta.env.VITE_BACKEND_URL;
const router = useRouter();

const categories = getAllCategories();
const audiences = getAllAudiences();
const imageUploadRef = ref<InstanceType<typeof ImageUpload> | null>(null);
const currentUserId =ref<number | null>(null)
const isBanned = ref(false)
const banReason = ref('')
const loading = ref(true)

const eventTitle = ref<string>('');
const eventDescription = ref<string>('');
const eventLocation = ref<string>('');
const eventCategories = ref<string[]>([]);
const eventAudiences = ref<string>([]);
const eventStartDate = ref<string | null>(null);
const eventEndDate = ref<string | null>(null);
const eventStartTime = ref<string | null>(null);
const eventEndTime = ref<string | null>(null);
const eventLinks = ref<string>('');
const eventCreator = ref<string | null>(null);
const isRecurringEvent = ref<boolean>(false);
const eventInstanceNumber = ref<number | null>(2);
const eventRecurringType = ref<string | null>(null);

const submissionSuccessful = ref<boolean>(false);

const errors = ref({
  eventTitle: '',
  eventDescription: '',
  eventLocation: '',
  eventCategories: '',
  eventAudiences: '',
  eventStartDate: '',
  eventEndDate: '',
  eventStartTime: '',
  eventEndTime: '',
  eventLink: '',
  eventInstanceNumber: '',
  eventRecurringType: '',
})

// Options for the recurring type
const recurringTypeOptions = ref([
  { name: '📅WEEKLY', type: 'WEEKLY'},
  { name: '📅2️⃣BI-WEEKLY', type: 'BI-WEEKLY'},
  { name: '📅🌙MONTHLY', type: 'MONTHLY'},
])

// Fetch banned status
const fetchBannedStatus = async () => {
  try {
    const response = await fetch(`${backendUrl}/checkbanned/${currentUserId.value}`)
    if (!response.ok) {
      throw new Error('Failed to fetch banned status')
    }
    const data = await response.json()
    isBanned.value = data.banned
    banReason.value = data.reason || ''

    loading.value = false;

  } catch (error) {
    console.error('Error fetching banned status:', error)
  }
}

// Check banned status when the component mounts
onMounted(async () => {
  await loadUserFromLocalStorage() // Ensure user data is loaded
  if (userId.value) {
    //console.log('userId.value:', userId.value)
    currentUserId.value = userId.value
  }
 await fetchBannedStatus();
})

const toggleCategory = (category:string)=> {
  // Add or remove the category from selectedCategories
  //console.log('before category selected:', eventCategories.value)
  if (eventCategories.value.includes(category.selectedValue)) {
    eventCategories.value = eventCategories.value.filter(
        (item) => item !== category.selectedValue
    );
  } else {
    eventCategories.value.push(category.selectedValue);
  }
  //console.log('after category selected:', eventCategories.value)
}

onMounted( async () => {
  await loadUserFromLocalStorage() // Ensure user data is loaded
  if (userId.value) {
    //console.log('userId.value:', userId.value);
    currentUserId.value = userId.value;
  }
});

const submitEvent = async () => {
  // wait for Vue to update refs
  await nextTick()

  if (isBanned.value) {
    return // Do nothing if the user is banned
  }

  if (!validateEventInfo()) {
    return;
  }

  // Default values for instance number and recurring type if Recurring Event checkbox is NOT checked
  if (!isRecurringEvent.value) {
    eventInstanceNumber.value = 1
    eventRecurringType.value = null
  }

  if (eventEndDate.value === null) {
    eventEndDate.value = eventStartDate.value;
  }
  if (eventEndTime.value === null) {
    eventEndTime.value = eventStartTime.value;
  }
  eventCreator.value = user.value

  //prepare the event data
  const eventData = {
    eventTitle: eventTitle.value,
    eventDescription: eventDescription.value,
    eventLocation: eventLocation.value,
    eventCategory: eventCategories.value.join(', '),
    eventAudience: eventAudiences.value.join(', '),
    eventStartDate: eventStartDate.value,
    eventEndDate: eventEndDate.value,
    eventStartTime: eventStartTime.value,
    eventEndTime: eventEndTime.value,
    eventLink: eventLinks.value,
    creator: user.value,
    userId: currentUserId.value,
    recurring: isRecurringEvent.value,
    instanceNumber: eventInstanceNumber.value,
    eventRecurringType: eventRecurringType.value?.type || null,
  };
  const formData = new FormData();

  // Append eventData even if no images are uploaded
  formData.append('eventData', JSON.stringify(eventData));

  try {
    // Check if imageUploadRef exists and has files
    if (imageUploadRef.value) {
      const imageUpload = imageUploadRef.value;

      if (imageUpload.files && imageUpload.files.length > 0) {
        if (!imageUpload.validateFiles()) {
          console.error('File validation failed.');
          return; // Stop submission if image validation fails
        }

        // Append image files to FormData
        imageUpload.files.forEach((file, index) => {
          const photoKey = `photo${['One', 'Two', 'Three'][index]}`; // photoOne, photoTwo, photoThree
          formData.append(photoKey, file);
        });
      } else {
        //console.log('No files selected for upload.');
      }
    } else {
      console.log('Image upload ref is not available.');
    }

    console.log(eventData)

    // console.log('FormData before sending:');
    // formData.forEach((value, key) => {
    //   console.log(key, value);
    // });
    // TODO: uncomment here
    const response = await fetch(backendUrl + '/events', {
      method: 'POST', // HTTP method
      headers: {
        'Authorization': `Bearer ${token.value}`
      },
      body: formData,
    });

    // Check if the request was successful
    if (!response.ok) {
      const errorData = await response.json(); // Parse the error response
      if (errorData.errors) {
        // Map backend errors to the `errors` ref
        Object.keys(errorData.errors).forEach((field) => {
          if (field in errors.value) {
            errors.value[field as keyof typeof errors.value] = errorData.errors[field];
          }
        });
      }
      throw new Error('Event creation failed');
    }

    const responseData = await response.json();

    //console.log('Event created successfully:', responseData);

    submissionSuccessful.value = true;

    setTimeout(() => {
      router.push('/'); // Redirect to the root page
    }, 5000);


  } catch (error) {
    console.error('Error creating event:', error.message);
    // Optionally, display an error message to the user
  }
}

function validateEventInfo() {
  //Get today's date for comparison purposes
  const today = new Date().toISOString().split('T')[0]; //in YYYY-MM-DD form
  let isValid = true;

  //reset the errors object
  Object.keys(errors.value).forEach((key) => {
    errors.value[key as keyof typeof errors.value] = '';
  });

  //validate Event Title
  if (!eventTitle.value.trim()) {
    errors.value.eventTitle = "You must enter in a title for the event";
    isValid = false;
  } else if (!/^[a-zA-Z\s]+$/.test(eventTitle.value)) {
    errors.value.eventTitle = "The event title cannot contain numbers or special characters";
    isValid = false;
  }

  //validate event description
  if (eventDescription.value.trim().length < 10 || eventDescription.value.trim().length > 250) {
    errors.value.eventDescription = "The description must be between 10 and 250 characters long.";
    isValid = false;
  }

  //validate event location
  if (!eventLocation.value.trim()) {
    errors.value.eventLocation = 'You must enter in a city for the event.'
    isValid = false
  } else if (!/^[a-zA-Z\s]+$/.test(eventLocation.value)) {
    errors.value.eventLocation = "You must enter in a valid city name for the event.";
    isValid = false;
  }

  //validate start date
  if (!eventStartDate.value) {
    errors.value.eventStartDate = "You must enter a start date.";
    isValid = false;
  } else if (eventStartDate.value <= today) {
    errors.value.eventStartDate = "You must enter a start date that is after today.";
    isValid = false;
  }

  //validate end date
  if (eventEndDate.value && eventEndDate.value < eventStartDate.value) {
    errors.value.eventEndDate = "The end date must come after the start date.";
    isValid = false;
  } else if (eventEndDate.value && eventEndDate.value <= today) {
    errors.value.eventEndDate = "You must enter an end date that is after today."
    isValid = false;
  }

  //validate start time
  if (!eventStartTime.value) {
    errors.value.eventStartTime = "You must enter in the starting time of the event.";
    isValid = false;
  }

  //validate event categories
  if (!eventCategories.value.length) {
    errors.value.eventCategories = "At least one category must be selected.";
    isValid = false;
  }

  // Validate Event Audiences
  if (!eventAudiences.value.length) {
    errors.value.eventAudiences = "At least one audience must be selected.";
    isValid = false;
  }

  if (isRecurringEvent.value) {
    // Validate Instance Number
    if (eventInstanceNumber.value === null || eventInstanceNumber.value < 2 || eventInstanceNumber.value > 12) {
      errors.value.eventInstanceNumber = " Invalid number. Enter numbers between 2-12"
      isValid = false
    }
    // Validate Recurring Type
    if (eventRecurringType.value === null) {
      errors.value.eventRecurringType = "Pick whether the event is a weekly, bi-weekly, or a monthly event."
      isValid = false
    }
  }

  return isValid;
}

</script>

<template>
  <div>
    <!-- Spinner displayed while waiting for banned status -->
    <div v-if="loading" id="loadingSpinner">
      <ProgressSpinner />
    </div>
    <div v-else>
      <div v-if="isBanned" class="banned-message">
        <h1>You are banned, cannot post at the moment.</h1>
      </div>
      <div v-else>
        <div v-if="!submissionSuccessful" class="form-container">
          <p id="requiredFieldWarning">(Fields marked with an <span class="red-asterisk">*</span> are required)</p>
          <form @submit.prevent="submitEvent">
            <div class="form-group">
              <label for="eventTitle">Event Title: <span class="red-asterisk">*</span></label>
              <input type="text" id="eventTitle" name="eventTitle" v-model="eventTitle"
                     placeholder="Enter the title of the event you would like to post">
              <div id="errorTitle" v-if="errors.eventTitle" class="error-message">{{ errors.eventTitle }}</div>
            </div>

      <div class="form-group">
        <label for="eventDescription">Event Description: <span class="red-asterisk">*</span></label>
        <textarea id="eventDescription" name="eventDescription" v-model="eventDescription"
                  placeholder="Add a description that is between 10 and 250 characters"></textarea>
        <div id="errorDescription" v-if="errors.eventDescription" class="error-message">{{ errors.eventDescription }}
        </div>
      </div>

      <div class="form-group">
        <label for="eventLocation">Location: <span class="red-asterisk">*</span></label>
        <input type="text" id="eventLocation" name="eventLocation" v-model="eventLocation"
               placeholder="Enter the city of where the event is located">
        <div id="errorLocation" v-if="errors.eventLocation" class="error-message">{{ errors.eventLocation }}</div>
      </div>

<!--      <div class="form-group">
        <label for="eventCategory">Category: *</label>
        <select id="eventCategory" name="eventCategory" v-model="eventCategories" multiple>
          <option value="" disabled selected>Please select applicable categories</option>
          <option v-for="category in categories" :key="category.name" :value="category.name">
            {{ category.name }}
          </option>
        </select>
        <div id="errorCategory" v-if="errors.eventCategories" class="error-message">{{ errors.eventCategories }}</div>
      </div>-->

      <!-- Category Selection Field -->
      <div class="form-group">
        <label for="eventCategory">Categories: <span class="red-asterisk">*</span></label>
        <div id="eventCategory" class="category-options">
          <div
              v-for="category in categories"
              :key="category.id"
              :class="{ selected: eventCategories.includes(category.name) }"
              class="category-wrapper"
          >
            <CategoryComponent
                :cateName="category.name"
                @click-category="toggleCategory"
            />
          </div>
        </div>
        <div id="errorCategory" v-if="errors.eventCategories" class="error-message">{{ errors.eventCategories }}</div>
      </div>


      <div class="form-group">
        <label for="eventAudience">Audience: <span class="red-asterisk">*</span></label>
        <select id="eventAudience" name="eventAudience" v-model="eventAudiences" multiple>
          <option value="" disabled selected>Please select the target audience</option>
          <option v-for="audience in audiences" :key="audience.name" :value="audience.name">
            {{ audience.name }}
          </option>
        </select>
        <div id="errorAudience" v-if="errors.eventAudiences" class="error-message">{{ errors.eventAudiences }}</div>
      </div>

      <div class="form-group">
        <label for="eventStartDate">Start Date: <span class="red-asterisk">*</span></label>
        <input type="date" id="eventStartDate" name="eventStartDate" v-model="eventStartDate">
        <div id="errorStartDate" v-if="errors.eventStartDate" class="error-message">{{ errors.eventStartDate }}</div>
      </div>

      <div class="form-group">
        <label for="eventEndDate">End Date:</label>
        <input type="date" id="eventEndDate" name="eventEndDate" v-model="eventEndDate">
        <div id="errorEndDate" v-if="errors.eventEndDate" class="error-message">{{ errors.eventEndDate }}</div>
      </div>

      <div class="form-group">
        <label for="eventStartTime">Start Time: <span class="red-asterisk">*</span></label>
        <input type="time" id="eventStartTime" name="eventStartTime" v-model="eventStartTime">
        <div id="errorStartTime" v-if="errors.eventStartTime" class="error-message">{{ errors.eventStartTime }}</div>
      </div>

      <div class="form-group">
        <label for="eventEndTime">End Time:</label>
        <input type="time" id="eventEndTime" name="eventEndTime" v-model="eventEndTime">
        <div id="errorEndTime" v-if="errors.eventEndTime" class="error-message">{{ errors.eventEndTime }}</div>
      </div>

            <!-- Recurring Event checkbox -->
            <div class="flex items-center gap-2" style="margin-bottom: 2%">
              <Checkbox v-model="isRecurringEvent" id="recurringEventCheckbox" binary /> <strong>Recurring Event</strong>
            </div>

            <div v-if="isRecurringEvent" style="margin-bottom: 1%">
              <!-- Instance number input box -->
              <div class="form-group">
                <label for="eventInstanceNumberCB">Instance:</label>
                <div>
                  <i class="pi pi-info-circle" style="font-size: 1rem"></i>
                  This is the number of event instances in the series. Choose between 2-12.<br />
                  Each event will have different start and end dates based on the recurrence type.
                </div>
                <InputNumber v-model="eventInstanceNumber" v-if="isRecurringEvent" inputId="eventInstanceNumberCB"
                             mode="decimal" showButtons :min="2" :max="12" fluid style="width: 15%" />
                <div id="errorInstanceNumber" v-if="errors.eventInstanceNumber" class="error-message">{{ errors.eventInstanceNumber }}</div>
              </div>

              <!-- Recurring type options -->
              <div class="form-group">
                <label for="eventRecurringTypeSB">Recurrence Type:</label>
                <div>
                  <i class="pi pi-info-circle" style="font-size: 1rem"></i>
                  For monthly, overflow issues are handled automatically. For example, if the start date is January 31st, the next event will start on February 28th.<br />
                  Start and end dates can be edited using the Edit Post feature after creation.
                </div>
                <SelectButton v-model="eventRecurringType" v-if="isRecurringEvent" aria-labelledby="eventRecurringTypeSB"
                              :options="recurringTypeOptions" optionLabel="name" />
                <div id="errorRecurringType" v-if="errors.eventRecurringType" class="error-message">{{ errors.eventRecurringType }}</div>
              </div>
            </div>

      <div class="form-group">
        <ImageUpload ref="imageUploadRef"/>
      </div>

      <div class="form-group">
        <label for="eventLink">External Links:</label>
        <input type="text" id="eventLink" name="eventLink" v-model="eventLinks"
               placeholder="Enter link to an external website here">
        <div id="errorLink" v-if="errors.eventLink" class="error-message">{{ errors.eventLink }}</div>
      </div>

      <div class="form-group">
        <Button type="submit" data-cy="submit-button">Post Event</Button>
      </div>

    </form>
  </div>


        <div v-else>
          <h1>Your event has been submitted for moderator review.</h1>
          <h2>Thank you for submitting your event titled "{{ eventTitle }}" {{ eventCreator }}</h2>
          <h3>You will be redirected to the event list in 5 seconds</h3>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Center the form and limit its width */
.form-container {
  max-width: 600px; /* Set maximum width of the form */
  /* Center form horizontally */
  padding: 1rem; /* Add some padding around the form */
  border: 1px solid #ccc; /* Optional border for better visibility */
  border-radius: 8px; /* Rounded corners */
  margin: 3rem auto 0;
}

/* Style for the form container */
.form-group {
  display: flex;
  flex-direction: column; /* Arrange label and input/textarea vertically */
  margin-bottom: 1rem; /* Add spacing between fields */
}

label {
  margin-bottom: 0.5rem; /* Add space below the label */
  font-weight: bold; /* Make label text bold for better visibility */
}

input,
textarea {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  width: 100%; /* Ensure inputs take up available space within the form */
  box-sizing: border-box; /* Include padding and border in width calculation */
}

input:focus,
textarea:focus {
  outline: none;
  border-color: #007bff; /* Add focus border color */
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Optional focus effect */
}

.error-message {
  color: red;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-weight: normal;
  line-height: 1.5;
  padding-left: 0.5rem;
}

/*Chau add new style for the modified Category field*/
.category-wrapper {
  padding: 5px;
  border: 2px solid transparent;
  border-radius: 8px;
  margin: 5px;
  display: inline-block; /* Keeps the categories inline */
  transition: all 0.3s ease; /* Smooth effect for border and background changes */
}

.category-wrapper:hover {
  border-color: #ccc; /* Add a light border when hovering */
  background-color: #f9f9f9;
}

.category-wrapper.selected {
  border-color: #007bff; /* Blue border for selected categories */
  background-color: #03fade; /* Light blue background for selected */
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2); /* Slight shadow for a selected look */
}

#loadingSpinner {
  margin-top: 4rem;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}

.red-asterisk {
  color: red;
}

#requiredFieldWarning {
  color: gray;
  font-size: 10pt;
}
</style>
