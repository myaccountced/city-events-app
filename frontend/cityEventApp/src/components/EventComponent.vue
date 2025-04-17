<script setup lang="ts">
import Card from 'primevue/card'
import Galleria from 'primevue/galleria'
import Image from 'primevue/image'
import Dialog from 'primevue/dialog'
//import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import 'primeicons/primeicons.css'

import { reportReasons } from '@/components/interfaces/reportReason'
import {computed, nextTick, onMounted, ref, watch} from 'vue'
import ReportView from '@/components/ReportView.vue'
import { useAuth } from '@/useAuth'
import Button from 'primevue/button'
import ButtonGroup from 'primevue/buttongroup'
import { useRoute, useRouter } from 'vue-router'
import CategoryComponent from '@/components/CategoryComponent.vue'
import FilterItemComponent from '@/components/FilterItemComponent.vue'
import {useConfirm} from "primevue/useconfirm"

const router = useRouter()
const route = useRoute()
const { token, user, userId } = useAuth();
const confirm = useConfirm()
const toast = useToast();
const backendUrl = import.meta.env.VITE_BACKEND_URL
const frontendUrl = import.meta.env.VITE_FRONTEND_URL

const frontendURLFull = import.meta.env.VITE_FULL_FRONTEND_URL
const HTTPURL = 'http://127.0.0.1:8001/uploads/'
const props = defineProps<{
  event: object
  index?: number
  currpath?: string
}>()
//const emit = defineEmits(['filter-category']);
//const handleCategoryClick = (selectedCategory: string) => {

let visibleSharingModal = ref<boolean>(false);
let facebookLink = ref<string | null>(null);
let xLink = ref<string | null>(null);
let genericLink = ref<string | null>(null);
let isCopied = ref<boolean>(false);
let mailToLink = ref<string | null>(null)
let smsLink = ref<string | null>(null)

// Interest and attendance counters
const interestedCount = ref(0);
const attendingCount = ref(0);

// Define interaction status enum
const InteractionStatus = {
  NO_INTERACTION: 'no_interaction',
  INTERESTED: 'interested',
  ATTENDING: 'attending'
};

// Track user's interaction status using the enum
const interactionStatus = ref(InteractionStatus.NO_INTERACTION);

// Button classes as computed properties
const interestedButtonClass = computed(() => {
  return interactionStatus.value === InteractionStatus.INTERESTED ? 'active-button' : 'standard-button';
});

const attendingButtonClass = computed(() => {
  return interactionStatus.value === InteractionStatus.ATTENDING ? 'active-button' : 'standard-button';
});

// Format the interest and attendance count text, handling zero case
const formatInterestText = computed(() => {
  if (interestedCount.value === 0) {
    return 'No one interested yet';
  } else if (interestedCount.value === 1) {
    return '1 person interested';
  } else {
    return `${interestedCount.value} people interested`;
  }
});

const formatAttendanceText = computed(() => {
  if (attendingCount.value === 0) {
    return 'No one attending yet';
  } else if (attendingCount.value === 1) {
    return '1 person will attend';
  } else {
    return `${attendingCount.value} people will attend`;
  }
});

const checkUserInteraction = () => {
  const currentUserId = userId.value;

  // Default to NO_INTERACTION
  interactionStatus.value = InteractionStatus.NO_INTERACTION;

  // Check if this user has an interaction with the event
  if (props.event.interactions && props.event.interactions.userInteractions) {
    for (const interaction of props.event.interactions.userInteractions) {
      if (interaction.userId === currentUserId) {
        // Convert the status string to the corresponding enum value
        const normalizedStatus = interaction.status.toLowerCase();
        if (normalizedStatus === InteractionStatus.ATTENDING) {
          interactionStatus.value = InteractionStatus.ATTENDING;
        } else if (normalizedStatus === InteractionStatus.INTERESTED) {
          interactionStatus.value = InteractionStatus.INTERESTED;
        }
        break;
      }
    }
  }
  // Set the counts
  interestedCount.value = props.event.interactions?.interestedCount || 0;
  attendingCount.value = props.event.interactions?.attendingCount || 0;
}

// Toggle interest status with mutually exclusive behavior
const toggleInterest = async () => {
  if (!useAuth().user.value) {
    // Redirect to login if user is not authenticated
    router.push('/signin');
    return;
  }

  try {
    // If already interested, remove interest (DELETE)
    // Otherwise, set as interested (POST)
    const method = interactionStatus.value === InteractionStatus.INTERESTED ? 'DELETE' : 'POST';
    const url = `${backendUrl}/events/interactions/interest?eventID=${props.event.id}&userID=${useAuth().user.value}`;

    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token.value}`
      },
      body: JSON.stringify({
        eventID: props.event.id,
        userID: useAuth().user.value
      })
    });

    if (response.ok) {
      // Update the user's interaction status
      if (interactionStatus.value === InteractionStatus.INTERESTED) {
        interactionStatus.value = InteractionStatus.NO_INTERACTION;
        interestedCount.value--; // Decrease count
      } else if (interactionStatus.value === InteractionStatus.ATTENDING) {
        interactionStatus.value = InteractionStatus.INTERESTED;
        attendingCount.value--; // Decrease attending count
        interestedCount.value++; // Increase interested count
      } else {
        interactionStatus.value = InteractionStatus.INTERESTED;
        interestedCount.value++; // Increase count
      }
    }
  } catch (error) {
    console.error('Error updating interest status:', error);
  }
};

// Toggle attendance status with mutually exclusive behavior
const toggleAttendance = async () => {
  if (!useAuth().user.value) {
    // Redirect to login if user is not authenticated
    router.push('/signin');
    return;
  }

  try {
    // If already attending, remove attendance (DELETE)
    // Otherwise, set as attending (POST)
    const method = interactionStatus.value === InteractionStatus.ATTENDING ? 'DELETE' : 'POST';
    const url = `${backendUrl}/events/interactions/attendance?eventID=${props.event.id}&userID=${useAuth().user.value}`;

    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token.value}`
      },
      body: JSON.stringify({
        eventID: props.event.id,
        userID: useAuth().user.value
      })
    });

    if (response.ok) {
      // Update the user's interaction status
      if (interactionStatus.value === InteractionStatus.ATTENDING) {
        interactionStatus.value = InteractionStatus.NO_INTERACTION;
        attendingCount.value--; // Decrease count
      } else if (interactionStatus.value === InteractionStatus.INTERESTED) {
        interactionStatus.value = InteractionStatus.ATTENDING;
        interestedCount.value--; // Decrease interested count
        attendingCount.value++; // Increase attending count
      } else {
        interactionStatus.value = InteractionStatus.ATTENDING;
        attendingCount.value++; // Increase count
      }
    }
  } catch (error) {
    console.error('Error updating attendance status:', error);
  }
};


const handleFilterClick = (emitObject: object) => {
  let queryObject = {};
  queryObject[emitObject.selectedProperty] = emitObject.selectedValue;

  // Update the URL and navigate to the new route with the selected category as a query parameter
  router.push({ path: `${props.currpath}`, query: queryObject });
};

const showExpanded = ref('expandView')
const expandEvent = ref(false)

const images = ref(props.event.imagePaths)
const imageCount = ref(props.event.imagePaths ? props.event.imagePaths.length : 0)

const showReportModal = ref(false)
const currentEventId = ref<number | null>(null)
// Functions to open and close the modal
const openReportModal = (eventId: number) => {
  currentEventId.value = eventId
  showReportModal.value = true
}
const closeReportModal = () => {
  showReportModal.value = false
  currentEventId.value = null
}

const formatLink = (eventTitle, eventLocation) => {
  return eventTitle + ' at ' + eventLocation
}

const isEventBookmarked = ref(false)
const bookmarkState = ref('pi pi-bookmark')

/**
 * checkForBookmark will be called when the page is loaded and will determine the status of
 * each bookmark initially, onMount
 */
const checkForBookmark = async () => {
      // get the current user's id
      const currentUserId = userId.value;

      //check if the bookmark array for the event contains the same user id
      for (const bookmark of props.event.bookmarks){
        if(bookmark.userId === currentUserId)
        {
          isEventBookmarked.value = true;
          break;
        }
      } 
      bookmarkState.value = isEventBookmarked.value ? 'pi pi-bookmark-fill' : 'pi pi-bookmark';
    }

async function toggleBookmark() {
  await nextTick()

  //change it visually while it checks it in the backend
  if (isEventBookmarked.value) {
    // if it is already bookmarked, unbookmark it (VISUALLY)
    bookmarkState.value = 'pi pi-bookmark'
  } else {
    // if it not already bookmarked, then bookmark it (VISUALLY)
    bookmarkState.value = 'pi pi-bookmark-fill'
  }

  // check with backend to see if the event is bookmarked
  // if event is already bookmarked, now unbookmark it
  // DELETE request
  if (isEventBookmarked.value == true) {
    try {
      const response = await fetch(backendUrl + '/events/bookmarks', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token.value}`
        },
        body: JSON.stringify({
          eventID: props.event.id, // Pass username as 'identifier' to match backend logic
          currentUser: useAuth().user.value
        })
      })

      if (response.status != 201) {
        const errorData = await response.json() // Parse the error response
        if (errorData.errors) {
          // Map backend errors to the `errors` ref
          Object.keys(errorData.errors).forEach((field) => {
            if (field in errors.value) {
              errors.value[field as keyof typeof errors.value] = errorData.errors[field]
            }
          })
        }
        throw new Error('Failed to check event bookmark status')
      }
      isEventBookmarked.value = false
    } catch (error) {
      console.error('Error fetching event:', error.message)
    }
  } else if (isEventBookmarked.value == false) {
    // if it is not bookmarked, bookmark it in backend
    // POST request
    try {
      const response = await fetch(backendUrl + '/events/bookmarks', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token.value}`
        },
        body: JSON.stringify({
          eventID: props.event.id, // Pass username as 'identifier' to match backend logic
          currentUser: useAuth().user.value
        })
      })

      if (response.status != 201) {
        const errorData = await response.json() // Parse the error response
        if (errorData.errors) {
          // Map backend errors to the `errors` ref
          Object.keys(errorData.errors).forEach((field) => {
            if (field in errors.value) {
              errors.value[field as keyof typeof errors.value] = errorData.errors[field]
            }
          })
        }
        throw new Error('Failed to bookmark event')
      } else {
        isEventBookmarked.value = true
      }
    } catch (error) {
      console.error('Error bookmarking event:', error.message)
    }
  }
}

/**
 * Send a fetch request to the backend MediaController. Get back a JsonResponse
 * with image : [list of paths]. Put these paths into the images ref object
 */
const getEventImages = async () => {
  const url = backendUrl + `/events/media?eventID=${props.event.id}`
  // get the image paths associated with the event
  await fetch(url)
    .then((response) => response.json())
    .then((pathsObj) => {
      pathsObj.images = pathsObj.images.map((path) => HTTPURL + `${path}`)
      images.value = pathsObj
    })

  imageCount.value = images.value.images.length
}

//sharing methods
function openSharingModal() {
  // and then opens the sharingModal
  visibleSharingModal.value = true
  //triggers when the sharing button is pressed for the given event
  // runs the functions generateFacebookLink, generateXLink, generateGenericCopyLink()
  generateMailToLink()
  generateSmsLink()
  generateFacebookLink()
  generateXLink()
  generateGenericCopyLink()

}

function generateFacebookLink()
{
  // triggers when the modal is opened
  // will build the facebook link to be used in the associated href
  // changes the facebookLink ref to the generated link
  let facebookStringStart = 'https://www.facebook.com/sharer/sharer.php'
  let localLink = encodeURI(`http://${frontendUrl}/event/${props.event.id}`)
  facebookLink.value = facebookStringStart + '?u=' + localLink;
}

function generateXLink()
{
  console.log("Generate x Link")
  // triggers when the modal is opened
  // will build the X link to be used in the associated href
  // changes the xLink ref to the generated link
  let xStringStart = 'https://twitter.com/intent/tweet'
  let eventLink = `http://${frontendUrl}/event/${props.event.id}`
  //let eventLink = "https://example.com/"
  let tweetPost = encodeURIComponent(`Check out this event!`)
  let localLink = encodeURIComponent(eventLink)
  let generatedLink = `${xStringStart}?text=${tweetPost}&url=${localLink}`
  xLink.value = generatedLink;

  const xLinkElement = document.getElementById('x')
  xLinkElement.href = xLink.value;
}

function generateGenericCopyLink()
{
  // triggers when the modal is opened
  // builds the link that can be pasted to anywhere
  // and leads to the associated event
  // changes the genericLink ref to the generated link
  genericLink.value = `http://${frontendUrl}/event/${props.event.id}`
}

function copyGenericLink() {
  generateGenericCopyLink();
  navigator.clipboard.writeText(genericLink.value)
    .then(() => {
      isCopied.value = true;
      setTimeout(() => {
        isCopied.value = false; // Reset after 2 seconds
      }, 2000);
    })
}

// Generate the mailto link. This link navigates to the mailing app of the user's device
function generateMailToLink() {
  console.log("Mail to link")
  const subject = `Checkout this City Event🎉: ${props.event.title}`
  const body = `Hi there!\n\nLocation: ${props.event.location}\nDate: ${props.event.startDate} - ${props.event.endDate}\nDescription: ${props.event.description}\n\nFor more details and to get all the information you need, click here: ${frontendURLFull}/event/${props.event.id}\n\nBest regards,`
  mailToLink.value = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
}

// Generate the sms link. This link navigates to the sms messaging app of the user's device
function generateSmsLink() {
  const phone = '+ '
  const body = `Check out this awesome event happening in the city🎉: ${props.event.title}. You can find more details here: ${frontendURLFull}/event/${props.event.id}`
  smsLink.value = `SMS:${encodeURIComponent(phone)}?body=${encodeURIComponent(body)}`
}
//end of sharing methods

// For exporting events to a Google Calendar

const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID
const API_KEY = import.meta.env.GOOGLE_CALENDAR_API_KEY

const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest'
const SCOPES = 'https://www.googleapis.com/auth/calendar'

let tokenClient

function loadGoogleAPI() {
  window.gapi.load('client', initializeGoogleAPIClient)
}

async function initializeGoogleAPIClient() {
  window.gapi.client.init({
    apiKey: API_KEY,
    discoveryDocs: [DISCOVERY_DOC]
  })
}

function loadGoogleIdentityServices() {
  tokenClient = window.google.accounts.oauth2.initTokenClient({
    client_id: CLIENT_ID,
    scope: SCOPES,
    callback: ''
  })
}

function handleExportClicked() {
  tokenClient.callback = async (resp) => {
    if (resp.error != undefined) {
      throw resp
    }
    await createCalendarEvent()
  }
  if (tokenClient.access_token === null) {
    tokenClient.requestAccessToken({ prompt: 'consent' })
  } else {
    tokenClient.requestAccessToken({ prompt: '' })
  }
}

async function createCalendarEvent() {
  //format the event object that will get sent to the Google Calendar API
  let event = {
    summary: props.event.title,
    location: props.event.location,
    description: props.event.description,
    start: {
      dateTime: formatDateTimeForExport(props.event.startDate, props.event.startTime).toISOString()
    },
    end: {
      dateTime: formatDateTimeForExport(props.event.endDate, props.event.endTime).toISOString()
    },
    source: {
      title: 'Motodo',
      url: frontendURLFull + `/event/${props.event.id}`
    },
    eventType: 'default'
  }
  console.log(event);
  window.gapi.client
    .request({
      path: '/calendar/v3/calendars/primary/events',
      method: 'POST',
      body: event
    })
    .then(function (resp) {
      //const toSend = resp.result.status.toString()
      console.log("After the export")
      prepareExportResultMessage(resp.result.status)
    })
}

function formatDateTimeForExport(eventDate, eventTime) {
  const tempDate = eventDate.toString().split('-')
  const date = tempDate.at(2)
  const year = tempDate.at(0)
  const month = tempDate.at(1) - 1

  const hours = eventTime.toString().split(':').at(0)
  const minutes = eventTime.toString().split(':').at(1)
  return new Date(year, month, date, hours, minutes)
}

const exportMessage = ref("")
const exportSuccess = ref(false)
function prepareExportResultMessage(response) {
  if (response == "confirmed")
  {
    exportMessage.value = "The event was successfully exported to your Google Calendar."
    exportSuccess.value = true
    console.log("we get into the response to exporting an event")
    toast.add({severity: 'success', successIcon: "pi pi-face-smile", summary: "Success",
      detail: exportMessage, group: 'ce', life: 4000})
  } else {
    exportMessage.value = "Unable to export the event to your Google Calendar at this time. Please try again later."
    exportSuccess.value = false
    toast.add({severity: 'error', successIcon: "pi pi-exclamation-circle", summary: "Error",
      detail: exportMessage, group: 'ce', life: 4000})
  }
}

// Watch for changes to expanded state
watch(expandEvent, (newValue) => {
  if (newValue && props.user) {
    checkUserInteraction();
  }
});

watch(() => props.event, (newEvent) => {
  if (newEvent && newEvent.interactions) {
    checkUserInteraction();
  }
});

// Deleting an event/series variables and methods
let isDeleteSeries = ref<boolean>(false)
let visibleDeleteEventModal = ref<boolean>(false)
let visibleConfirmationModal = ref<boolean>(false)

/**
 * Close the Delete Event modal and open the Confirmation modal
 */
const deleteInstance = () => {
  visibleDeleteEventModal.value = false
  visibleConfirmationModal.value = true
}

/**
 * Close the Delete Event modal and open the Confirmation modal
 * Set the isDeleteSeries to true
 */
const deleteSeries = () => {
  isDeleteSeries.value = true;
  visibleDeleteEventModal.value = false
  visibleConfirmationModal.value = true
}

/**
 * Sends a request to the route in the backend that handles deleting event/series.
 */
const deleteEvents = async () => {
  visibleConfirmationModal.value = false // Close the Confirmation modal

  try {
    const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/events/delete', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: JSON.stringify({
        eventData: {
          eventID: props.event.id,
          deleteSeries: isDeleteSeries.value
        }
      }),
    });

    // Verify the response
    if (!response.ok) {
      throw new Error(`Error deleting event/series: ${ response.statusText }`);
    }
    else {
      // Show success toast and reload the page after 5 seconds
      toast.add({ severity: 'success', summary: 'Event Cleared', detail: 'The event or series has been successfully deleted permanently.', life: 5000 })
      setTimeout(() => {
        window.location.reload()
      }, 5000);
    }

    isDeleteSeries.value = false // Reset the isDeleteSeries
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Server Error', detail: 'Try deleting again later', life: 10000 });
  }
}
// End of deleting an event/series

// get event images when the event component is loaded in
onMounted(async () => {
  await checkUserInteraction()
  if(props.event.images > 0)
  {
    getEventImages()
  }
  if (useAuth().user.value)
  {
    checkForBookmark()
  }

  // for google calendar API initialization
  // reference for converting Google Calendar API init to Vue 3
  // https://stackoverflow.com/questions/76337091/how-to-use-google-calendar-api-in-vue3-application
  let gapiScript = document.createElement('script')
  gapiScript.defer = true
  gapiScript.async = true
  gapiScript.onreadystatechange = gapiScript.onload = function () {
    const interval = setInterval(function () {
      if (!gapiScript.readyState || /loaded|complete/.test(gapiScript.readyState)) {
        clearInterval(interval)
        if (window.gapi) {
          loadGoogleAPI()
        } else {
          console.log('Failed to load gapi')
        }
      }
    }, 100)
  }

  gapiScript.src = 'https://apis.google.com/js/api.js'
  document.head.appendChild(gapiScript)

  let gisScript = document.createElement('script')
  gisScript.defer = true
  gisScript.async = true
  gisScript.onreadystatechange = gisScript.onload = function () {
    const interval = setInterval(function () {
      if (!gisScript.readyState || /loaded|complete/.test(gisScript.readyState)) {
        clearInterval(interval)
        if (window.google && window.google.accounts) {
          loadGoogleIdentityServices()
        } else {
          console.log('Failed to load gis')
        }
      }
    }, 100)
  }
  gisScript.src = 'https://accounts.google.com/gsi/client'
  document.head.appendChild(gisScript)
})
</script>

<template>

  <div class="event" v-bind:id="'event' + event.id">
    <Card class="collapseView event-item" style="width: 100%">
      <template #title
        ><h1 class="eventTitle collapseView">{{ event.title }}</h1></template
      >

      <template #subtitle>
        Posted by <RouterLink :to="'/profile?username=' + event.creator" class="eventCreator" >{{ event.creator }}</RouterLink>
      </template>

      <template #content>
        <Image
          v-if="imageCount != 0"
          :src="images[0]"
          alt="Image"
          width="200rem"
          class="imageThumbnail"
          style="
            box-shadow:
              0 4px 8px 0 rgba(0, 0, 0, 0.2),
              0 6px 20px 0 rgba(0, 0, 0, 0.19);
          "

        />
        <i
            v-else
            class="imageThumbnail pi pi-calendar"
            style="font-size: 10rem;"
        />
        <div>
          <ButtonGroup raised rounded style="margin-bottom: 1rem" id="buttonGroup">
            <Button
                icon="pi pi-ellipsis-h"
                @click="expandEvent = !expandEvent"
                class="eventButton"
                as="a"
                style="text-decoration: none"
                v-tooltip.left="{ value:'Expand for more info', showDelay: 500}"
            />

            <Button
                as="a"
                @click="openReportModal($props.event.id)"
                class="report-button"
                icon="pi pi-flag"
                style="text-decoration: none"
                v-tooltip.bottom="{ value:'Report event', showDelay: 500}"
            />

            <Button
                as="a"
                v-if="event.links"
                icon="pi pi-external-link"
                :href="event.links"
                target="_blank"
                class="eventLink"
                style="text-decoration: none"
                v-tooltip.bottom="{ value:'Visit external site', showDelay: 500}"
            />

            <Button
                v-if="useAuth().user.value"
                :icon="bookmarkState"
                @click="toggleBookmark()"
                class="bookmarkButton"
                style="text-decoration: none"
                as="a"
                v-tooltip.bottom="{ value:'Bookmark event', showDelay: 500}"
            />

            <Button
                icon="pi pi-share-alt"
                style="text-decoration: none"
                @click="openSharingModal"
                as="a"
                class="shareButton"
                v-tooltip="{ value:'Share event', showDelay: 500}"
            />

            <!-- Trash icon, opens the Delete Event modal -->
            <Button
                icon="pi pi-trash"
                style="text-decoration: none"
                @click="visibleDeleteEventModal = true"
                as="a"
                class="deleteButton"
                v-tooltip="{ value:'Share event', showDelay: 500}"
                v-if="props.currpath === '/myevents'"
            />
          </ButtonGroup>

          <ReportView
              v-if="showReportModal && currentEventId === event.id"
              :eventID="event.id"
              :eventTitle="event.title"
              :isReportVisible="showReportModal"
              :reportReasons="reportReasons.map((rpReason) => rpReason.reason)"
              @cancel-report="closeReportModal"
          />
        </div>

        <strong>Location: </strong
        ><span class="eventLocation collapseView">
        <FilterItemComponent :filter-property="'location'" :filter-value="event.location" @click-filter="handleFilterClick" />
      </span>
        <br />
        <strong>Date: </strong
        ><span class="eventStartDate collapseView"
      ><FilterItemComponent :filter-property="'startDate'" :filter-value="event.startDate"
                            @click-filter="handleFilterClick" />
          <span class="eventEndDate" v-if="event.endDate"> - <FilterItemComponent :filter-property="'endDate'"
                                                                                  :filter-value="event.endDate" @click-filter="handleFilterClick" /></span></span
      >
        <br />
        <strong>Time: </strong
        ><span class="eventStartTime collapseView"
      >{{ event.startTime }}
          <span class="eventEndTime" v-if="event.endTime"> - {{ event.endTime }}</span></span
      >
        <br />
        <strong>Audience: </strong
        ><span class="eventAudience collapseView>">
        <FilterItemComponent :filter-value="event.audience" :filter-property="'audience'" @click-filter="handleFilterClick" />
      </span>
        <br />
        <strong>Category: </strong
        ><span class="eventCategory collapseView >">
          <CategoryComponent v-for="(category, index) in event.category" v-bind:key="index" :cateName="category" @click-category="handleFilterClick" />
        </span>

        <!-- Interest and Attendance counters (visible to all users) -->
        <div class="event-interaction-counters">
          <div class="interest-counter">
            <i class="pi pi-star"></i>
            <span>{{ formatInterestText }}</span>
          </div>
          <div class="attendance-counter">
            <i class="pi pi-check-circle"></i>
            <span>{{ formatAttendanceText }}</span>
          </div>
        </div>

        <!-- Expanded view content -->
        <div v-bind:class="showExpanded" v-if="expandEvent">
          <strong>Description: </strong>
          <span class="eventDescription expandView">{{ event.description }}</span>
          <br />
          <span v-if="event.links">
            <a v-bind:href="event.links" target="_blank" class="eventLink">{{ formatLink(event.title, event.location) }}</a>
            <br />
          </span>
          <span v-if="event.recurringType">
            <strong>Recurring: </strong>{{ event.recurringType }}
          </span>

          <Button
            v-if="useAuth().premiumState.value.status && formatDateTimeForExport(props.event.startDate, props.event.startTime) > Date.now()"
            id="exportEvent"
            @click="handleExportClicked"
            label="Export Event to Google Calendar"
            icon="pi pi-google"
            iconPos="right"
            style="margin-bottom: 1rem; margin-top: 1rem"
          />


          <Galleria
              class="imageGallery"
              :value="images"
              :circular="true"
              containerStyle="max-width: 500px;
                            max-height: 300px;
                             background-position: 50% 50%;
                             background-repeat: no-repeat;
                             background-size: cover;"
              :showItemNavigators="true"
              :showThumbnails="false"
          >
            <template #item="slotProps">
              <img
                  :src="slotProps.item"
                  :alt="slotProps.item.alt"
                  style="width: 100%; display: block"
              />
            </template>
          </Galleria>

          <!-- Interest and Attendance buttons (only for logged-in users) -->
          <div v-if="user" class="event-interaction-buttons">
            <Button
              :class="interestedButtonClass"
              class="hover-light-red"
              @click="toggleInterest"
              icon="pi pi-star"
              label="I am interested in this event"
            />
            <Button
              :class="attendingButtonClass"
              class="hover-light-green"
              @click="toggleAttendance"
              icon="pi pi-check-circle"
              label="I want to attend this event"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>

  <Dialog id="shareModal" class="shareModal" v-model:visible="visibleSharingModal" modal header="Sharing Options">
    <p><img src="/facebookLogo.jfif" alt="facebook" style="width: 50px; height: 50px"/>
      <span>
        <a id="facebook" :href="facebookLink" target="_blank" ><Button label="Facebook"  style="width: 100px; margin-left: 5px"/></a>
      </span>
    </p>
    <p><img src="/xLogo.jfif" alt="x" style="width: 50px; height: 50px"/>
      <span>
        <a id="x" :href="xLink" target="_blank"><Button label="X"  style="width: 100px; margin-left: 5px"/></a>
      </span>
    </p>
    <Button id="copyLink" @click="copyGenericLink">Copy link to clipboard</Button>
    <p v-if="isCopied" style="color: green; background-color: lightgray; margin-top: 5px; text-align: center;">Copied to clipboard</p>
    <p><span class="pi pi-phone" style="font-size: 2rem" />
      <a id="smsLink" :href="smsLink" @click="visibleSharingModal = false">
        <Button label="Text Message" style="width: 150px; margin-left: 5px" v-tooltip="{ value:'Share via sms message', showDelay: 500}" />
      </a>
    </p>
    <p><span class="pi pi-envelope" style="font-size: 2rem" />
      <a id="emailLink" :href="mailToLink" @click="visibleSharingModal = false">
        <Button label="Email" style="width: 150px; margin-left: 5px" v-tooltip="{ value:'Share via email', showDelay: 500}" />
      </a>
    </p>
  </Dialog>

  <!-- Delete Event modal  -->
  <Dialog id="deleteEventModal" class="deleteEventModal" v-model:visible="visibleDeleteEventModal" modal header="Delete Event" >
    <template #default>
      <div class="flex flex-col items-center text-center">
        <i class="pi pi-info-circle" style="font-size: 2.5rem"></i><br />
        <strong>Read Carefully before proceeding!!</strong><br />
        <div v-if="event.recurringType">
          Choose whether to just delete this specific event or delete the whole series.<br />
          Deleting the whole series will delete other upcoming events related to this event.<br />
          Past events in the same series will <strong>not</strong> be affected.<br />
          Unless you are directly deleting the series through the past event.<br />
          <strong>WARNING!!</strong> Event/series deletion is permanent.<br />
        </div>
        <div v-else>
          <strong>WARNING!!</strong> Event deletion is permanent.
        </div>
      </div>
    </template>

    <template #footer>
        <div style="display: flex; justify-content: flex-start; gap: 8px; width: 100%;">
          <Button id="deleteSeriesDeleteEventModal" label="Delete Series" icon="pi pi-calendar" class="p-button-warning" @click="deleteSeries()" v-if="event.recurringType" />
          <Button id="deleteEventDeleteEventModal" label="Delete Event" icon="pi pi-trash" class="p-button-danger" @click="deleteInstance()" />
        </div>
        <Button label="Cancel" icon="pi pi-times" class="p-button-secondary" @click="visibleDeleteEventModal = false" />
    </template>
  </Dialog>

  <!-- Confirmation modal  -->
  <Dialog id="confirmationDeleteEventModal" class="deleteEventModal" v-model:visible="visibleConfirmationModal" modal header="Confirmation Delete Event" >
    <template #default>
      <div class="flex flex-col items-center text-center">
        <i class="pi pi-exclamation-triangle" style="font-size: 2.5rem"></i><br />
        <div v-if="isDeleteSeries">
          Proceeding will permanently delete the whole series titled, "{{ event.title }}".<br />
          All events related to the series will be deleted. <strong>Proceed with caution!!</strong>
        </div>
        <div v-else>
          Proceeding will permanently delete the event titled, "{{ event.title }}".<br />
          If this event is part of a series, the other events in the same series will <strong>not</strong> be affected.
        </div>
        <br /><strong>When the deletion is successful, the page will automatically refresh after 5 seconds</strong>
      </div>
    </template>

    <template #footer>
      <div style="display: flex; justify-content: flex-start; gap: 8px; width: 100%;">
        <Button id="proceedConfirmationModal" label="Proceed" icon="pi pi-play" class="p-button-warning" @click="deleteEvents()" />
      </div>
      <Button label="Cancel" icon="pi pi-times" class="p-button-secondary" @click="visibleConfirmationModal = false; isDeleteSeries = false" />
    </template>
  </Dialog>
</template>

<style scoped>

@media (max-width: 400px) {
  .imageThumbnail {
    width: 30px;
    display: flex;

  }
  #buttonGroup {
    margin-top: 1rem;
  }


}

@media (min-width: 1024px) {
  .imageThumbnail {
/*    width: 250px;*/
    float: right;
    margin-right: 3rem;

  }


}


#exportEvent {
  display: flex;
  margin-top: 5px;
}
.event-interaction-counters {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.interest-counter, .attendance-counter {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.event-interaction-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.standard-button {
  background-color: #f0f0f0;
  color: #333;
}

.active-button {
  background-color: #3B82F6;
  color: white;
}

.hover-light-red:hover {
  background-color: lightcoral !important;
}

.hover-light-green:hover {
  background-color: lightseagreen !important;
}

</style>