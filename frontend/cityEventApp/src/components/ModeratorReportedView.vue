<script setup lang="ts">
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import ButtonGroup from "primevue/buttongroup";
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import Image from 'primevue/image';
import 'primeicons/primeicons.css'
import {useConfirm} from "primevue/useconfirm";
import {useToast} from 'primevue/usetoast';
import {useAuth} from "@/useAuth";
import useEventFetch from '../scripts/EventFetch';
import 'bootstrap-icons/font/bootstrap-icons.css';
import {ref, onMounted, onBeforeUnmount} from 'vue';
import {useRouter} from "vue-router";

const { token } = useAuth();
const {events, getImages} = useEventFetch();
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();
const limit = 20;          // Number of events to load at a time
let offset = ref(0); // Current offset
let isFetching = ref(false); // To prevent multiple fetches
let expandedRows = ref([]);  // Tracks expanded rows
let noMoreEvents = false;          // Track whether to fetch for more events when we scroll down
const noReportedEventsMessage = ref(false);
const selectedView = ref('')

const changeView = (view) => {
  selectedView.value = view
  router.push(`/moderator/${view}`) // Change the route
}

/**
 * Confirmation dialog box when we press the wastebasket in a specific row. Confirm clearing the reports.
 * @param eventsID
 * @param eventTitle
 */
const confirmClearReports = (eventsID : number, eventTitle : string) => {
  confirm.require({
    message: 'Are you sure you want to clear out the reports for this event?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Cancel',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Proceed'
    },
    accept: () => {
      clearReports(eventsID, eventTitle); // Clear the reports
    },
    reject: () => {
    }
  });
};

/**
 * Get the events reported three times and push it to the events list so they can be displayed.
 * @param limit
 * @param offsetValue
 */
const getReportedEvents = async (limit: number, offsetValue: any) => {
  isFetching.value = true;
  try {
    const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/api/get_reported_events', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: JSON.stringify({
        limit: limit,
        offset: offsetValue
      }),
    });

    if (!response.ok) {
      throw new Error(`Error fetching events: ${response.statusText}`);
    }

    const responseData = await response.json();

    // Ensure that the response is an array
    if (Array.isArray(responseData)) {
      // Only increment offset and push to the event list if the response array is not empty
      if (responseData.length > 0) {
        // Add the new events to the current array
        events.value.push(...responseData.map((event: any) => ({
          id: event.id,
          eventTitle: event.title,
          eventDescription: event.description,
          eventStartDate: event.startDate,
          eventEndDate: event.endDate,
          eventLocation: event.location,
          eventAudience: event.audience,
          eventCategory: event.category,
          eventCreator: event.creator,
          eventImages: event.images,
          eventLink: event.links,
          reports: [],
          imagePaths: []
        })));

        offset.value += limit;  // Update offset only if the response array is not empty
      } else {
        noMoreEvents = true;    // This disables the handleScroll
        console.log("No new events fetched.");
      }

      noReportedEventsMessage.value = events.value.length === 0; // Check whether to display the table or the no events message

    } else {
      console.error('Unexpected response format: expected an array');
    }
  } catch (error) {
    console.error('Error fetching events:', error);
    toast.add({ severity: 'error', summary: 'Server Error', detail: 'Try again later', life: 5000 });
  } finally {
    isFetching.value = false;
  }
};

/**
 * Fetch the report instances for a specific event
 * @param eventID
 */
const getReports = async (eventID : number) => {
  try {
    const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/api/get_reports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: JSON.stringify({ eventID }),
    });

    if (!response.ok) {
      throw new Error('Error fetching reports');
    }

    const data = await response.json();

    const targetEvent = events.value.find(ev => ev.id === data.eventID);
    if (targetEvent) {
      // Only add the report instances when the reports array is empty, we don't want to append reports repeatedly,
      //    only one time, when the row is first expanded.
      if (targetEvent.reports.length === 0) {
        const reportsToAdd = data.reportedInstances.map((instance: any) => ({
          reportId: instance.reportId,
          reportDate: instance.reportDate,
          reportTime: instance.reportTime,
          reason: instance.reason
        }));

        targetEvent.reports = [...targetEvent.reports, ...reportsToAdd];
      } else {
        console.error('Reports for that event was already fetched');
      }
    } else {
      console.error('Event not found for the given eventID');
    }
  } catch (error) {
    console.error('Error fetching reports:', error);
    return [];
  }
}

/**
 * Clear the report instances of that event and remove the event from the table
 * @param eventID
 * @param eventTitle
 */
const clearReports = async (eventID : number, eventTitle : string) => {
  try {
    const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/api/clear_reports', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: JSON.stringify({
        eventID: eventID
      }),
    });

    if (!response.ok) {
      throw new Error(`Error deleting reported events: ${response.statusText}`);
    }

    // If the deletion is successful, remove the event from the `events` array
    const eventIndex = events.value.findIndex(ev => ev.id === eventID);
    if (eventIndex !== -1) {
      // Remove event from the events array
      events.value.splice(eventIndex, 1);
    }

    toast.add({ severity: 'success', summary: 'Reports Cleared', detail: 'Reports for ' + eventTitle + ' have been cleared', life: 5000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Server Error', detail: 'Try deleting again later', life: 5000 });
    console.error('Error deleting reported events:', error);
  }
}

/**
 * Fetch and load the events
 */
const loadMoreEvents = async () => {
  if (isFetching.value) return; // Prevent duplicate calls
  isFetching.value = true; // Set fetching to true

  // Load more unsorted events
  await getReportedEvents(limit, offset.value); // Fetch events with pagination

  isFetching.value = false; // Reset fetching state
};

/**
 * Handler for when user clicks the expand '>' button
 * @param event
 */
const onRowExpand = (event: any) => {
  const expandedRow = event.data;  // Get the expanded row data
  const eventId = expandedRow.id;  // Access the id from the expanded row
  //console.log("Expanded row ID:", eventId);
  getReports(eventId); // Get the related reports for that event
  getImages(eventId);  // Get the related images for that event
};

// Load initial events
onMounted(() => {
  loadMoreEvents();

  const handleScroll = () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight) {
      loadMoreEvents();
    }

    if(noMoreEvents) {
      window.removeEventListener('scroll', handleScroll); // Clean up event listener

    }
  };

  window.addEventListener('scroll', handleScroll);

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll); // Clean up event listener
  });
});

const goToPending = () => {
  router.push("/moderator/pending")
}

const goToUsers = () => {
  router.push("/moderator/users")
}

const displayCategories = (array: any) => {
  let stringReturn = "";
  for (let category in array) {
    stringReturn += array[category] + ", ";
  }

  if (stringReturn !== "") {
    return stringReturn.substring(0, stringReturn.length - 2);
  } else {
    return "Others";
  }
}

</script>

<template>
  <ButtonGroup>
    <Button id="pendingTab" label="Pending" @click="goToPending" severity="secondary"/>
    <Button id="reportedTab" label="Reported" severity="warn" disabled />
    <Button id="usersTab" label="Users" @click="goToUsers" severity="secondary"/>
  </ButtonGroup>


  <div v-if="noReportedEventsMessage">
    <h1 id="noEventsMessage">No Upcoming Reported Events</h1>
  </div>
  <div v-else>
    <DataTable
        :value="events"
        class="p-datatable-striped"
        :responsiveLayout="'scroll'"
        :rowHover="true"
        :emptyMessage="'No events found.'"
        :expandedRows="expandedRows"
        data-key="id"
        @rowExpand="onRowExpand"
    >
      <Column expander style="width: 5rem" />
      <Column field="eventTitle" header="Title"></Column>
      <Column field="eventCategory" header="Category">
        <template #body="slotProps">
          {{ displayCategories(slotProps.data.eventCategory) }}
        </template>
      </Column>
      <Column field="eventAudience" header="Audience"></Column>
      <Column field="eventLocation" header="Location"></Column>
      <Column field="eventStartDate" header="Start Date"></Column>
      <Column field="eventEndDate" header="End Date"></Column>
      <Column header="">
        <template #body="{ data }">
          <span class="pi pi-thumbs-up-fill" style="font-size: 2rem" @click="confirmClearReports(data.id, data.eventTitle)" v-tooltip="{ value:'Clear reports', showDelay: 500}"></span>
        </template>
      </Column>

      <template #expansion="{ data }">
        <div class="p-3">
          <h4>{{ data.eventTitle }}</h4>
          <p><strong>Description:</strong> {{ data.eventDescription }}</p>
          <p><strong>Links:</strong> <a :href="data.eventLink" target="_blank">{{ data.eventLink }}</a></p>
          <p><strong>Creator:</strong> {{ data.eventCreator }}</p>
          <!-- Check if there are no images in the array -->
          <div v-if="data.imagePaths.length === 0">
            <p><strong>Images: </strong>No images included</p>
          </div>
          <!-- If there are images, render them -->
          <div v-else>
            <p><strong>Images:</strong></p>
            <Image v-for="(image, index) in data.imagePaths" :key="index" :src="image" alt="An Event Image" width="250" height="150" preview />
          </div>
          <h4>Reports</h4>
          <ul>
            <li v-for="report in data.reports" :key="report.reportId">
              <p>Reason: {{ report.reason }}, reported at {{ report.reportDate }} {{ report.reportTime }}</p>
            </li>
          </ul>
        </div>
      </template>

      <Toast></Toast>
    </DataTable>
  </div>
</template>


<style scoped>
.custom-button-active {
  background-color: #0d5aa7; /* Active button color */
}

.custom-button-secondary{
  background-color: gray; /* Active button color */
}

td {
  padding-left: 2em;
  padding-right: 2em;
  text-wrap: nowrap;
}

td.modView button {
  margin: 5px 1em;
}

strong {
  font-weight: bold;
}

a {
  color: cornflowerblue;
  text-decoration: underline;
}

label {
  padding-left: 5%;
  color: #f3e8e4;
  padding-bottom: 5px;
}

textarea {
  margin: 0 5%;
  min-width: 450px;
  width: 60vw;
  max-width: 90%;
  min-height: 100px;
  height: 280px;
  max-height: 280px;
  font-size: large;
}
</style>