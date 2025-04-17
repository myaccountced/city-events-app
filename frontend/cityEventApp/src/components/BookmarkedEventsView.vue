<script setup lang="ts">


import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import useEventFetch from '@/scripts/EventFetch'
import { getAllEventTypes } from '@/components/interfaces/EventType'
import { getAllCategories } from '@/components/interfaces/Category'
import { getAllCosts } from '@/components/interfaces/Cost'
import { getAllAudiences } from '@/components/interfaces/Audience'
import { getAllAuthors } from '@/components/interfaces/Author'
import { getAllAccessiblities } from '@/components/interfaces/Accessibility'
import { getAllFormats } from '@/components/interfaces/Format'
import EventComponent from '@/components/EventComponent.vue'
import AdComponent from "@/components/AdComponent.vue";
import ProgressSpinner from 'primevue/progressspinner'

const filters = ref({
  eventTypeFilters: getAllEventTypes(),
  categoryFilters: getAllCategories(),
  costFilters: getAllCosts(),
  audienceFilters: getAllAudiences(),
  authorFilters: getAllAuthors(),
  accessibilityFilters: getAllAccessiblities(),
  formatFilters: getAllFormats()
});

const selectedFilters = ref([]); // reactive array of selected Filters that will be compared against the table of events

const {events, error, getBookmarkedEvents, noMoreEvents} = useEventFetch();
const limit = 20; // Number of events to load at a time
let offset = ref(0); // Current offset
let isFetching = ref(false); // To prevent multiple fetaches

const noEventsMessage = ref('You have no events bookmarked.');
const bookmarkPath = ref("/bookmarks");

const loadMoreEvents = async () => {
  if (isFetching.value) return; // Prevent duplicate calls
  isFetching.value = true; // Set fetching to true

  // Load more unsorted events
  await getBookmarkedEvents(limit, offset.value); // Fetch events with pagination


  offset.value += limit; // Update offset for next load
  isFetching.value = false; // Reset fetching state
};


const formatDate = (dateObject: string | Date) => {
  if (!dateObject) return ''; // Handle null or undefined dates
  const options = { year: '2-digit', month: 'short', day: '2-digit' };
  const date = new Date(dateObject); // Convert to Date object
  date.setDate(date.getDate()  + 1);
  return date.toLocaleDateString('en-US', options).replace(/(\d{1,2})\/(\d{1,2})\/(\d{2})/, '$1-$2-$3');
};


let filteredEvents = computed(() => {
  return events.value;
});



onMounted(() => {
  loadMoreEvents();

  const handleScroll = () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 50) {
      loadMoreEvents();
    }
    if (noMoreEvents.value) {
      window.removeEventListener('scroll', handleScroll)
    }
  };
  window.addEventListener('scroll', handleScroll);

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll); // Clean up event listener
  });
})

</script>

<template>

  <h1 style="text-align: center; margin-top: 2rem;">My Bookmarked Events</h1>

  <div>
    <div v-if="isFetching" id="loadingSpinner">
      <ProgressSpinner />
    </div>
    <div v-else class="eventContainer">

      <h1 v-if="error">{{ error }}</h1>
      <h2 v-if="events.length === 0 && !error" style="margin-top: 3rem">{{noEventsMessage}}</h2>
      <br/>

      <div v-for="(event, index) in filteredEvents" :key="event.id" style="margin: 1rem" id="eventFeed">
        <div v-if="event.id !== 'ad'">
          <EventComponent :event="event" :index="index" :currpath="bookmarkPath"/>
        </div>
        <div v-if="event.id == 'ad' && scope !== 'moderator' && !premiumState.status">
          <AdComponent  :style="'display: block;'" :client="clientID" :ad-slot="slotID" :format="'fluid'" :ad-type="'event-ad'"></AdComponent>
        </div>
      </div>
    </div>
  </div>


</template>

<style scoped>

.eventContainer {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  width: 80%;
  margin-left: auto;
  margin-right: auto;
}

#loadingSpinner {
  margin-top: 4rem;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}

#eventFeed {
  width: 100%;
  margin-left: auto;
  margin-right: auto;
}

</style>