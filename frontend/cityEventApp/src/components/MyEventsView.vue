<script setup lang="ts">
import Button from 'primevue/button';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuth,} from '@/useAuth'
import { useRouter } from 'vue-router'
import EventComponent from '@/components/EventComponent.vue';
import ButtonGroup from 'primevue/buttongroup'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import useEventFetch from '@/scripts/EventFetch'
import ProgressSpinner from 'primevue/progressspinner'
import { $dt } from '@primevue/themes'
import SelectButton from 'primevue/selectbutton';

const isShowingPastEvents = ref(false)
const backendUrl = import.meta.env.VITE_BACKEND_URL
const backendMediaURL = 'http://127.0.0.1:8001/uploads/'

const { user, token } = useAuth()
const { userFutureEvents, userPastEvents, error, getUsersEvents } = useEventFetch()
const currentPath = "/myevents";
const isFetching = ref(false);
const isDeleting = ref(false)
const router = useRouter()

const confirm = useConfirm()

const loadEvents = async () => {
  if (isFetching.value) return; // Prevent duplicate calls
  isFetching.value = true; // Set fetching to true

  if (useAuth().user.value !== null) {

    await getUsersEvents(user.value);

  } else {
    console.error('User ID is null. Unable to load user events.')
  }

  isFetching.value = false; // Reset fetching state
};
const confirmDelete = (eventId) => {
  console.log('Called dialog for del')
  // confirmation dialog
  confirm.require({
    message: 'Are you sure you want to delete this event? This cannot be undone',
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => deleteEvent(eventId),
    reject: () => {
     // do nothing
    }
  })
}
const confirmEdit = (eventId) => {
  console.log('Called dialog for edit')
  // confirmation dialog
  confirm.require({
    message: 'Are you sure you want to edit this event? Previously approved events will be re-submitted for moderator approval upon updating',
    header: 'Edit Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => editEvent(eventId),
    reject: () => {
     // do nothing
    }
  })
}

const deleteEvent = async (eventId) => {
  try {
    isDeleting.value = true

    const response = await fetch(`${backendUrl}/myevents/${eventId}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token.value}`
      }
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Failed to delete event')
    }

    // Refresh the events list after successful deletion
    await loadEvents();

  } catch (error) {
    console.error('Error deleting event:', error)
  } finally {
    isDeleting.value = false
  }
}

const editEvent = (eventId: number) => {
  router.push(`/myevents/edit/${eventId}`)
}

const toggleExpand = (eventId: number, tab: 'future' | 'past') => {
  const targetEvents = tab === 'future' ? futureEvents : pastEvents
  targetEvents.value = targetEvents.value.map((event) =>
    event.id === eventId ? { ...event, expanded: !event.expanded } : event
  )
}

const getLocalStore = (key: string): string | null => {
  const itemStr = localStorage.getItem(key)
  if (!itemStr) {
    return null
  }
  const item = JSON.parse(itemStr)
  const now = new Date()

  if (now.getTime() > item.expiry) {
    localStorage.removeItem(key)
    return null
  }
  return item.value // Return the value if not expired
}

const swapEvents = async () => {
  isShowingPastEvents.value = !isShowingPastEvents.value;
}

const futureEvents = computed(() =>  {
  return userFutureEvents.value;
});

const pastEvents = computed(() => {
  return userPastEvents.value;
});

// Load future events on component mount
onMounted(async () => {
  await loadEvents();
})

// for the select button:
const options = ref(['Upcoming Events', 'Past Events']);
const selected = ref('Upcoming Events');

</script>

<template>
  <h1 style="text-align: center; margin-top: 2rem;">My Posted Events</h1>
  <h1 v-if="error">{{ error }}</h1>
  <br />

  <SelectButton v-model="selected" :options="options" @click="swapEvents" class="selectButton"/>

  <div class="eventSpace">

    <div v-if="isFetching" id="loadingSpinner">
      <ProgressSpinner/>
    </div>

    <div v-else-if="!isShowingPastEvents" class="eventContainer">
      <div v-if="futureEvents.length === 0 && !error" class="no-events">
        You don't have any future events.
      </div>
      <div v-for="(event, index) in futureEvents" :key="event.id" style="margin: 1rem; width:80%" id="eventFeed">
        <EventComponent :event="event" :index="index" :currpath="currentPath" style="width:100%"/>
        <div class="eventActions">
          <Button
              v-bind:id="'edit' + (event.id).toString()"
              icon="pi pi-pencil"
              label="Edit"
              @click="confirmEdit(event.id)"
              class="p-button-primary"
          />
          <Button
              v-bind:id="'delete' + (event.id).toString()"
              icon="pi pi-trash"
              label="Delete"
              @click="confirmDelete(event.id)"
              class="p-button-danger"
              :disabled="isDeleting"
          />
        </div>
      </div>
    </div>

    <div v-else-if="isShowingPastEvents" class="eventContainer">
      <div v-if="pastEvents.length === 0 && !error" class="no-events">
        You don't have any past events.
      </div>
      <div v-for="(event, index) in pastEvents" :key="event.id" style="margin: 1rem; width:80%" id="eventFeed">
        <EventComponent :event="event" :index="index" :currpath="currentPath" style="width:100%"/>
      </div>
    </div>

  </div>

</template>

<style scoped>
.button-group {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.eventContainer {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  margin: 0 auto;
}

.selectButton {
  display: flex;
  justify-content: center;
  align-items: center;
}

Button .selectButton {
  margin-left: 0;
  margin-right: 0;
  border-radius: 0;
}

#loadingSpinner {
  margin-top: 4rem;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}
</style>
