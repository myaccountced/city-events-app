<script setup lang="ts">
  import { Button, ButtonGroup, DataTable, Column, Image } from "primevue";
  import {useAuth} from "@/useAuth";

  import 'bootstrap-icons/font/bootstrap-icons.css';
  import {ref, onMounted, onBeforeUnmount, computed} from 'vue';
  import useEventFetch from '../scripts/EventFetch';
  import { useRouter} from "vue-router";
  const { token, scope } = useAuth();
  const {events, error, getEventsWithFilterAndSorter, noMoreEvents, getImages} = useEventFetch();
  const router = useRouter();
  const backendUrl = import.meta.env.VITE_BACKEND_URL;

// Event Approval/Rejection section:
const rejectionModalVisible = ref(false)
const openRejection = ref(false)
const rejectionError = ref('')
const rejectionReason = ref('')
const selectedEvent = ref(null)
const approveRejectStatus = ref('')
const hasResponse = computed(() => approveRejectStatus.value !== '')
const responseColor = ref('black')

  const limit = 20; // Number of events to load at a time
  let offset = ref(0); // Current offset
  let isFetching = ref(false); // To prevent multiple fetches
  let expandedRows = ref([]); // Tracks expanded rows

  const eventsWithoutAd = computed(() => {
    return events.value.filter(event => event.id !== 'ad'); // this one is to remove ad from the list of event
  })

const loadMoreEvents = async () => {
  if (isFetching.value) return // Prevent duplicate calls
  isFetching.value = true // Set fetching to true

    // Load more unsorted events
    //await getPendingEvents(limit, offset.value); // Fetch events with pagination
    await getEventsWithFilterAndSorter(limit, offset.value,[{ fieldName: 'moderatorApproval', criteria: [0],}],undefined)

  offset.value += limit // Update offset for next load
  isFetching.value = false // Reset fetching state
}

  const formatDate = (dateObject: string | Date) => {
    if (!dateObject) return ''; // Handle null or undefined dates
    const options = { year: '2-digit', month: 'short', day: '2-digit' };
    const date = new Date(dateObject); // Convert to Date object
    date.setDate(date.getDate()  + 1);
    return date.toLocaleDateString('en-US', options).replace(/(\d{1,2})\/(\d{1,2})\/(\d{2})/, '$1-$2-$3');
  };


  /**
   * Sends an approval request to the server.
   */
  async function onApproveEvent(eventData: any) {
    const payload = {
      'id': eventData.id,
      'status': true
    }

    const res = await fetch(backendUrl + '/events/mod', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token.value
      },
      body: JSON.stringify(payload)
    })

    if (res.ok) {
      responseColor.value = 'green'
      receiveApproveRejectResponse("Event '" + eventData.title + "' was approved!");
    } else {
      const data = await res.json();
      responseColor.value = 'red'
      receiveApproveRejectResponse(res.status + ": " + data.error)
    }
  }

  function onRejectEvent(eventData: any) {
    selectedEvent.value = eventData;
    rejectionModalVisible.value = true;
  }

  /**
   * Checks if the user has input good rejection input, and sends the request to the server.
   */
  const handleRejection = async () => {
    let isGood = true;

    if (rejectionReason.value.length < 10 || rejectionReason.value.length > 255) {
      isGood = false;
      rejectionError.value = 'Reason for rejection must be between 10 and 255 characters!';
    }
    if (rejectionReason.value === '' || rejectionReason.value.toString().trim() === '') {
      isGood = false;
      rejectionError.value = 'Reason for rejection is required';
    }

    if (isGood)
    {
      const payload = {
        id: selectedEvent.value.id,
        status: false,
        reason: rejectionReason.value
      };

      dismissRejection();

      try {
        const res = await fetch(`${backendUrl}/events/mod`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token.value}`,
          },
          body: JSON.stringify(payload),
        });

        if (res.ok) {
          rejectionModalVisible.value = false;
          responseColor.value = 'green';
          receiveApproveRejectResponse(`Event "${selectedEvent.value.title}" rejected successfully.`);
        } else {
          const error = await res.json();
          responseColor.value = 'red';
          receiveApproveRejectResponse(`Error: ${error.message}`);
        }
      } catch (error) {
        responseColor.value = 'red';
        receiveApproveRejectResponse(`Unexpected error: ${error.message}`);
      }
    }

  };


  /**
   * Closes the rejection modal.
   */
  function dismissRejection() {
    openRejection.value = false;
    rejectionReason.value = ''
    rejectionModalVisible.value = false;
  }

  /**
   * Displays a message to the user at the top of the screen for 5 seconds.
   * @param string Message to be displayed to the user
   */
  function receiveApproveRejectResponse(string) {
    events.value = [];
    offset.value = 0;
    loadMoreEvents()
    approveRejectStatus.value = string;
    setTimeout(() => {
      approveRejectStatus.value = '';
    }, 5000);
  }

  // Load initial events
  onMounted(() => {
    loadMoreEvents();

    const handleScroll = () => {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 50 ) {
        loadMoreEvents(); // Trigger the loading logic
      }

      if(noMoreEvents.value)
      {
        window.removeEventListener('scroll', handleScroll)
      }
    };

    window.addEventListener('scroll', handleScroll);

    onBeforeUnmount(() => {
      window.removeEventListener('scroll', handleScroll); // Clean up event listener
    });
  });



  const onRowExpand = (event: any) => {
    const expandedRow = event.data;  // Get the expanded row data
    const eventId = expandedRow.id;  // Access the id from the expanded row
    //getImages(eventId);  // Get the related images for that event
  };
  const goToReported = () => {
    router.push("/moderator/reported")
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
    <Button id="pendingTab" label="Pending" severity="warn"  disabled />
    <Button id="reportedTab" label="Reported" severity="secondary" @click="goToReported"/>
    <Button id="usersTab" label="Users" @click="goToUsers" severity="secondary"/>
  </ButtonGroup>

  <h1 v-if="events.length === 0" id="no-pending">There are currently no pending events.</h1>
  <DataTable
      id="pendingTable"
      :value="eventsWithoutAd"
      class="p-datatable-striped event"
      :responsiveLayout="'scroll'"
      :rowHover="true"
      :emptyMessage="'No events found.'"
      :expandedRows="expandedRows"
      data-key="id"
      @rowExpand=onRowExpand
  >
    <!-- Expander Column -->
    <Column expander id="expander" style="width: 5rem" />

    <!-- Event Title Column -->
    <Column field="title" header="Title"  bodyClass="event-title"></Column>

    <!-- Event Category Column -->
    <Column field="category" header="Category"  bodyClass="event-category">
      <template #body="slotProps">
        {{ displayCategories(slotProps.data.category) }}
      </template>
    </Column>

    <!-- Audience -->
    <Column field="audience" header="Audience"  bodyClass="event-audience"></Column>

    <!-- Location -->
    <Column field="location" header="Location" bodyClass="event-location"></Column>

    <Column
      field="startDate"
      header="Start Date"
      :body="formatDate"
      bodyClass="event-startDate"
    ></Column>

    <Column field="endDate" header="End Date" :body="formatDate" bodyClass="event-endDate"></Column>

    <!-- Expanded Row Template -->
    <template #expansion="{ data }">
      <div class="p-3">
        <h4>{{ data.title }}</h4>
        <p class="eventDescription"><strong>Description:</strong> {{ data.description }}</p>
        <p class="eventCreator"><strong>Creator:</strong> {{ data.creator }}</p>
        <p><strong>Links:</strong> <a :href="data.links" target="_blank">{{ data.links }}</a></p>
        <!-- Check if there are no images in the array -->
        <div v-if="data.imagePaths.length === 0">
          <p><strong>Images: </strong>No images included</p>
        </div>
        <!-- If there are images, render them -->
        <div v-else>
          <p><strong>Images:</strong></p>
          <Image v-for="(image, index) in data.imagePaths" :key="index" :src="image" alt="An Event Image" width="250" height="150" preview />
        </div>
        <p>
          <button class="approveBtn" @click="onApproveEvent(data)">Approve Event</button>
          <button class="rejectBtn" @click="onRejectEvent(data)">Reject Event</button>
        </p>
      </div>
    </template>
  </DataTable>

  <div v-if="rejectionModalVisible" class="modal reject" >
    <h3>Rejection Form</h3>
    <textarea v-model="rejectionReason" id="reason" name="reason" class="rejectReason" placeholder="Enter rejection reason"></textarea>
    <div v-if="rejectionError" class="errorMessage">
      <p v-if="rejectionError" class="error">{{ rejectionError }}</p>
    </div>
    <button class="submitBtn" @click="handleRejection">Submit</button>
    <button @click="() => (rejectionModalVisible = false)">Cancel</button>
  </div>

  <tr class="success-message" >
    <td v-if="hasResponse" v-bind:class="responseColor" >
      {{ approveRejectStatus }}
    </td>
  </tr>
</template>

<style scoped>
td {
  padding-left: 2em;
  padding-right: 2em;
  text-wrap: nowrap;
}

td.collapseView {
  border: black 2px solid;
}

td.modView {
  text-align: center;
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

.icon {
  color: cornflowerblue;
}

.success-message {
  position: fixed;
  top: 20px;
  left: 40%;
  border: 1px solid black;
  font-weight: bold;
  background-color: white;
}

.red {
  color: red;
}

.green {
  color: green;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 165, 0, 0.8); /* Lighter orange semi-transparent background */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  display: block;
  background-color: #222222; /* White background for modal */
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.4);

  min-width: 500px;
  max-width: 60vw;
  overflow-y: auto;
  height: 80%;
  max-height: 500px;
  top: 10%;
  left: 20%;
}

.modal > div {
  text-align: center;
  margin-bottom: 1em;
}

.modal > div > div {
  width: fit-content;
  max-width: 100%;
  max-height: 80%;
  text-align: left;
  margin-left: auto;
  margin-right: auto;
  padding: 0 2.5%;
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

.modal button {
  background-color: #aa330e; /* Orange button */
  color: #ffffff;
  border: none;
  padding: 12px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.3s;
  margin-left: 5px;
  margin-right: 5px;
}

.modal button:hover {
  background-color: #e65c00; /* Darker orange on hover */
}

.modal-buttons {
  text-align: center;
}
</style>