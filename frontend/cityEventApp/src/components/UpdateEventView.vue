<script setup lang="ts">
import ImageUpload from './ImageUpload.vue'
import { nextTick, ref, onMounted, watch } from 'vue'
import { getAllCategories, Category, getAllCategoryNames } from '@/components/interfaces/Category'
import { getAllAudiences } from '@/components/interfaces/Audience'
import { useAuth } from '@/useAuth'
import { useRouter, useRoute } from 'vue-router'
import ProgressSpinner from 'primevue/progressspinner'
import CategoryComponent from '@/components/CategoryComponent.vue'

const { user, userId, loadUserFromLocalStorage, token } = useAuth()
const backendUrl = import.meta.env.VITE_BACKEND_URL
const router = useRouter()
const route = useRoute()

// Get event ID from route params
const eventId = ref<string>(route.params.id as string)

const categories = getAllCategories()
const audiences = getAllAudiences()
const imageUploadRef = ref<InstanceType<typeof ImageUpload> | null>(null)
const currentUserId = ref<number | null>(null)
const isBanned = ref(false)
const banReason = ref('')
const loading = ref(true)
const loadingEvent = ref(true)

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


const submissionSuccessful = ref<boolean>(false)
const eventNotFound = ref<boolean>(false)
const notAuthorized = ref<boolean>(false)

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
  eventLink: ''
})

// Watch for changes in the route params (in case user navigates between different events to edit)
watch(
  () => route.params.id,
  (newId) => {
    if (newId && newId !== eventId.value) {
      eventId.value = newId as string
      loadEventData()
    }
  }
)

// Function to load all necessary data
const loadEventData = async () => {
  loading.value = true
  loadingEvent.value = true

  try {
    await loadUserFromLocalStorage() // Ensure user data is loaded

    if (userId.value) {
      currentUserId.value = userId.value
      await fetchBannedStatus()
      await fetchEventData()
    } else {
      router.push('/') // Redirect to login page if user is not authenticated
    }
  } catch (error) {
    console.error('Error loading data:', error)
    loading.value = false
    loadingEvent.value = false
  }
}

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
    loading.value = false
  } catch (error) {
    console.error('Error fetching banned status:', error)
    loading.value = false
  }
}

// Fetch event data
const fetchEventData = async () => {
  try {
    loadingEvent.value = true

    if (!eventId.value) {
      throw new Error('No event ID provided')
    }

    await new Promise(resolve => setTimeout(resolve, 100));

    const response = await fetch(`${backendUrl}/event/${eventId.value}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (!response.ok) {
      if (response.status === 404) {
        eventNotFound.value = true
      } else if (response.status === 403) {
        notAuthorized.value = true
      }
      throw new Error(`Failed to fetch event: ${response.statusText}`)
    }

    const data = await response.json()
    console.log("Fetched event data:", data)

    await nextTick(() => {
      // Correctly map the properties
      eventTitle.value = data.title || ''
      eventDescription.value = data.description || ''
      eventLocation.value = data.location || ''

      // Handle categories
      if (data.category) {
        // If it's an array, use it directly, otherwise split if it's a string
        eventCategories.value = Array.isArray(data.category)
            ? data.category
            : (data.category as string).split(',').map((cat: string) => cat.trim())
      }

      // Handle audiences
      if (data.audience) {
        // If it's an array, use it directly, otherwise split if it's a string
        eventAudiences.value = Array.isArray(data.audience)
            ? data.audience
            : (data.audience as string).split(',').map((aud: string) => aud.trim())
      }

      // Handle dates and times
      if (data.startDate) {
        try {
          const startDate = new Date(data.startDate)
          if (!isNaN(startDate.getTime())) {
            eventStartDate.value = startDate.toISOString().split('T')[0]

            // Use startTime from the data or default to '00:00'
            eventStartTime.value = data.startTime || '00:00'
          }
        } catch (e) {
          console.error('Error parsing start date:', e)
        }
      }

      if (data.endDate) {
        try {
          const endDate = new Date(data.endDate)
          if (!isNaN(endDate.getTime())) {
            eventEndDate.value = endDate.toISOString().split('T')[0]

            // Use endTime from the data or default to '00:00'
            eventEndTime.value = data.endTime || '00:00'
          }
        } catch (e) {
          console.error('Error parsing end date:', e)
        }
      }

      // Handle links
      eventLinks.value = data.links || ''
    })

    loadingEvent.value = false
  } catch (error) {
    console.error('Error fetching event data:', error)
    loadingEvent.value = false
  }
}

// Load event data when the component mounts
onMounted(() => {
  loadEventData()
})

const toggleCategory = (category: any) => {
  const categoryName = typeof category === 'string'
      ? category
      : category.selectedValue || category.name;

  if (eventCategories.value.includes(categoryName)) {
    eventCategories.value = eventCategories.value.filter(
        (item) => item !== categoryName
    );
  } else {
    eventCategories.value.push(categoryName);
  }
  console.log('Updated categories:', eventCategories.value)
}

const updateEvent = async () => {
  // wait for Vue to update refs
  await nextTick()

  if (isBanned.value) {
    return // Do nothing if the user is banned
  }

  if (!validateEventInfo()) {
    return
  }

  if (eventEndDate.value === null) {
    eventEndDate.value = eventStartDate.value
  }
  if (eventEndTime.value === null) {
    eventEndTime.value = eventStartTime.value
  }

  // Prepare the event data directly
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
    eventLink: eventLinks.value
  };

  const formData = new FormData();

  // Append each key-value pair directly to FormData
  formData.append('eventData', JSON.stringify(eventData));

  try {
    if (imageUploadRef.value) {
      const imageUpload = imageUploadRef.value

      if (imageUpload.files && imageUpload.files.length > 0) {
        if (!imageUpload.validateFiles()) {
          console.error('File validation failed.')
          return // Stop submission if image validation fails
        }

        // Append image files to FormData
        imageUpload.files.forEach((file, index) => {
          const photoKey = `photo${['One', 'Two', 'Three'][index]}` // photoOne, photoTwo, photoThree
          formData.append(photoKey, file)
        })
      }
    }

    // console.log('FormData before sending:');
    // formData.forEach((value, key) => {
    //   console.log(key, value);
    // });

    const response = await fetch(`${backendUrl}/myevents/${eventId.value}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token.value}`,
      },
      body: formData,
    })

    // Check if the request was successful
    if (!response.ok) {
      const errorData = await response.json() // Parse the error response
      if (errorData.errors) {
        // Map backend errors to the `errors` ref
        Object.keys(errorData.errors).forEach((field) => {
          if (field in errors.value) {
            errors.value[field as keyof typeof errors.value] = errorData.errors[field]
          }
        })
      }
      throw new Error('Event update failed')
    }

    const responseData = await response.json()
    console.log('Event updated successfully:', responseData)
    submissionSuccessful.value = true

    // Redirect to myevent page after successful update
    setTimeout(() => {
      router.push(`/myevents`)
    }, 3000)
  } catch (error) {
    console.error('Error updating event:', error.message)
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

  return isValid;
}
</script>

<template>
  <div>
    <!-- Spinner displayed while loading -->
    <div v-if="loading || loadingEvent" class="loading-container">
      <div class="card flex justify-center">
        <ProgressSpinner
          style="width: 50px; height: 50px"
          strokeWidth="8"
          fill="transparent"
          animationDuration=".5s"
          aria-label="Custom ProgressSpinner"
        />
        <p class="loading-text">Loading event data...</p>
      </div>
    </div>

    <!-- Error messages for special cases -->
    <div v-else-if="eventNotFound" class="error-container">
      <h1>Event Not Found</h1>
      <p>Sorry, the event you're trying to update doesn't exist.</p>
      <button @click="router.push('/myevents')">Return to My Events</button>
    </div>

    <div v-else-if="notAuthorized" class="error-container">
      <h1>Not Authorized</h1>
      <p>You don't have permission to update this event.</p>
      <button @click="router.push('/myevents')">Return to My Events</button>
    </div>

    <div v-else-if="isBanned" class="banned-message">
      <h1>You are banned, cannot update events at the moment.</h1>
      <p v-if="banReason">Reason: {{ banReason }}</p>
      <button @click="router.push('/')">Return to Events</button>
    </div>

    <!-- Main form content -->
    <div v-else>
      <div v-if="!submissionSuccessful" class="form-container">
        <h1>Update Event</h1>
        <p>Fields marked with an * are required</p>
        <form @submit.prevent="updateEvent">
          <div class="form-group">
            <label for="eventTitle">Event Title: *</label>
            <input
              type="text"
              id="eventTitle"
              name="eventTitle"
              v-model="eventTitle"
              placeholder="Enter the title of the event you would like to post"
            />
            <div id="errorTitle" v-if="errors.eventTitle" class="error-message">
              {{ errors.eventTitle }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventDescription">Event Description: *</label>
            <textarea
              id="eventDescription"
              name="eventDescription"
              v-model="eventDescription"
              placeholder="Add a description that is between 10 and 250 characters"
            ></textarea>
            <div id="errorDescription" v-if="errors.eventDescription" class="error-message">
              {{ errors.eventDescription }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventLocation">Location: *</label>
            <input
              type="text"
              id="eventLocation"
              name="eventLocation"
              v-model="eventLocation"
              placeholder="Enter the city of where the event is located"
            />
            <div id="errorLocation" v-if="errors.eventLocation" class="error-message">
              {{ errors.eventLocation }}
            </div>
          </div>

          <!-- Category Selection Field -->
          <div class="form-group">
            <label for="eventCategory">Categories: *</label>
            <div id="eventCategory" class="category-options">
              <div
                v-for="category in categories"
                :key="category.id"
                :class="{ selected: eventCategories.includes(category.name) }"
                class="category-wrapper"
              >
                <CategoryComponent
                    :cateName="category.name" @click-category="toggleCategory"
                />
              </div>
            </div>
            <div id="errorCategory" v-if="errors.eventCategories" class="error-message">
              {{ errors.eventCategories }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventAudience">Audience: *</label>
            <select id="eventAudience" name="eventAudience" v-model="eventAudiences" multiple>
              <option value="" disabled>Please select the target audience</option>
              <option v-for="audience in audiences" :key="audience.name" :value="audience.name">
                {{ audience.name }}
              </option>
            </select>
            <div id="errorAudience" v-if="errors.eventAudiences" class="error-message">
              {{ errors.eventAudiences }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventStartDate">Start Date: *</label>
            <input type="date" id="eventStartDate" name="eventStartDate" v-model="eventStartDate" />
            <div id="errorStartDate" v-if="errors.eventStartDate" class="error-message">
              {{ errors.eventStartDate }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventEndDate">End Date:</label>
            <input type="date" id="eventEndDate" name="eventEndDate" v-model="eventEndDate" />
            <div id="errorEndDate" v-if="errors.eventEndDate" class="error-message">
              {{ errors.eventEndDate }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventStartTime">Start Time: *</label>
            <input type="time" id="eventStartTime" name="eventStartTime" v-model="eventStartTime" />
            <div id="errorStartTime" v-if="errors.eventStartTime" class="error-message">
              {{ errors.eventStartTime }}
            </div>
          </div>

          <div class="form-group">
            <label for="eventEndTime">End Time:</label>
            <input type="time" id="eventEndTime" name="eventEndTime" v-model="eventEndTime" />
            <div id="errorEndTime" v-if="errors.eventEndTime" class="error-message">
              {{ errors.eventEndTime }}
            </div>
          </div>

          <div class="form-group">
            <label>Event Images:</label>
            <p class="image-note">Upload new images or leave empty to keep existing ones <br/><strong>If you are uploading new images you MUST re-upload all images you wish to be apart of the event posting.</strong></p>
            <ImageUpload ref="imageUploadRef" />
          </div>

          <div class="form-group">
            <label for="eventLink">External Links:</label>
            <input
              type="text"
              id="eventLink"
              name="eventLink"
              v-model="eventLinks"
              placeholder="Enter link to an external website here"
            />
            <div id="errorLink" v-if="errors.eventLink" class="error-message">
              {{ errors.eventLink }}
            </div>
          </div>

          <div class="form-group buttons">
            <button id="submit" type="submit" data-cy="update-button">Update Event</button>
            <button type="button" class="cancel-button" @click="router.push('/myevents')">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <div v-else class="success-message">
        <h1>Your event has been updated successfully</h1>
        <h2>You will be redirected to your events page in 3 seconds</h2>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Center the form and limit its width */
.form-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 1rem;
  border: 1px solid #ccc;
  border-radius: 8px;
}

/* Style for the form container */
.form-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 1rem;
}

label {
  margin-bottom: 0.5rem;
  font-weight: bold;
}

input,
textarea {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  width: 100%;
  box-sizing: border-box;
}

input:focus,
textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

.error-message {
  color: red;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-weight: normal;
  line-height: 1.5;
  padding-left: 0.5rem;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
}

.loading-text {
  margin-top: 10px;
  color: #666;
}

.success-message,
.error-container,
.banned-message {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
  text-align: center;
  border-radius: 8px;
}

.success-message {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.error-container {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.banned-message {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.buttons {
  flex-direction: row;
  justify-content: space-between;
  gap: 1rem;
}

button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  background-color: #007bff;
  color: white;
  transition: background-color 0.3s;
}

button:hover {
  background-color: #0056b3;
}

.cancel-button {
  background-color: #6c757d;
}

.cancel-button:hover {
  background-color: #5a6268;
}

.image-note {
  font-size: 0.875rem;
  color: #6c757d;
  margin-top: -0.5rem;
  margin-bottom: 0.5rem;
}

/*Chau add new style for the modified Category field*/
.category-options {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

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
</style>
