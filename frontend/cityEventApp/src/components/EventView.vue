<script setup lang="ts">
import 'bootstrap-icons/font/bootstrap-icons.css';
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import useEventFetch from '../scripts/EventFetch';
import FilterView from "./FilterView.vue";
import {useAuth} from "@/useAuth";
import {useRouter, useRoute} from "vue-router";
import AdComponent from "@/components/AdComponent.vue";
import EventComponent from "@/components/EventComponent.vue";
import ToolbarComponent from '@/components/ToolbarComponent.vue'
import ProgressSpinner from 'primevue/progressspinner'

const router = useRouter();
const route = useRoute();
const paramEventView = ref({});
const eventViewPath = ref("/")

// Watch for changes in query parameters
watch(() => route.query, (newQuery) => {
  paramEventView.value = [];

  if (!newQuery.searchString) {
    searchInput.value = ""
    prevSearch.value = ""
    for (let key in newQuery) {
      paramEventView.value[key] = newQuery[key] || [];

      if (paramEventView.value[key] && !Array.isArray(paramEventView.value[key])) {
        paramEventView.value[key] = [paramEventView.value[key]];
      }
    }

    // Ensure paramEventView.value is always an array
    if (paramEventView.value && !Array.isArray(paramEventView.value)) {
      paramEventView.value = [paramEventView.value];
    }

    handleQueryURLChanging(paramEventView.value,"")
  } else {
    // Ensure paramEventView.value is always an array
    if (paramEventView.value && !Array.isArray(paramEventView.value)) {
      paramEventView.value = [paramEventView.value];
    }
    handleQueryURLChanging(paramEventView.value, newQuery.searchString)
  }
});

let searchInput = ref("")
let prevSearch = ref("")
let autocompleteEvents = ref ();

const selectedFilters = ref([]); // reactive array of selected Filters that will be compared against the table of events
const isMenuVisible = ref(false); // Variable used for modal visibility

// message to display if no events are there
const noEventsMessage = ref('Sorry, There are currently no events posted.');
const noSearchResults = ref('There are no events that match the given search criteria.')

const {events, error, noMoreEvents, getEventsWithFilterAndSorter} = useEventFetch();
const limit = 20; // Number of events to load at a time
let offset = ref(0); // Current offset
let isFetching = ref(false); // To prevent multiple fetches

let activeSortField = ref('eventStartDate'); // Sorting state
let sortOrder = ref('asc'); // Default order

const defaultFilter = {
    fieldName: 'moderatorApproval',
    criteria: [1], // Only approved events
}
const curFilters = ref<{fieldName:string,criteria:any[]}[]>([defaultFilter]);
const curSorter = ref<{ fieldName: string, order: 'ASC' | 'DESC' }>({ fieldName: 'eventStartDate', order: 'ASC'})


// Text that tells the user WHAT is currently being filtered by
const filterDisplay = computed(() => {
  let filterString = "Filtering by: ";

  curFilters.value.forEach((property) => {
    let propName = property.fieldName.toString().toLowerCase();
    if (propName.toString().indexOf("event") != -1) {
      propName = propName.substring(5);
    }

    propName = propName.replace("start", "");
    propName = propName.replace("end", "");

    if (property.fieldName != 'moderatorApproval') {
      for (let value in property.criteria) {
        filterString += (property.fieldName === 'eventStartDate' ? "Starting on " :
            (property.fieldName === 'eventEndDate' ? "Ending on " : ""))
            + property.criteria[value] + " (" + propName + "), "
      }
    }
  })

  if (filterString === "Filtering by: ") {
    filterString = "No filters applied";
  } else {
    filterString = filterString.substring(0, filterString.length - 2);
  }
  return filterString;
})


// IDs for advertisements
const clientID = import.meta.env.AD_DATA_CLIENT_ID;
const slotID = import.meta.env.AD_DATA_EVENT_SLOT;
const { isAuthenticated, scope, premiumState, logout } = useAuth();

const loadMoreEventWithFilterAndSorter = async () => {
  if (isFetching.value) return; // Prevent duplicate calls
  isFetching.value = true; // Set fetching to true

  // Load more events with optional filter and sorter
  await getEventsWithFilterAndSorter(limit, offset.value, curFilters.value, curSorter.value, false, prevSearch.value); // Fetch events with pagination

  offset.value += limit; // Update offset for next load
  isFetching.value = false; // Reset fetching state
}

let filteredEvents = computed(() => {
  return events.value;
});

const clearFilters = async() =>{
  selectedFilters.value = [defaultFilter];
  curFilters.value = [defaultFilter];
  await router.push({ path: `${eventViewPath.value}` });
  await updateSelectedFilters([]);
}

const clearSearch = async () => {
  searchInput.value = "";
  prevSearch.value = "";

  await router.push({ path: `${eventViewPath.value}` })
}


// Function to handle the advanced filter modal's emit
const updateSelectedFilters = async (newFilters:[]) => {
  curFilters.value = [
    defaultFilter,
    ...newFilters
  ]

  let queryObject = {};
  for (let item in newFilters) {
    let temp = newFilters[item].fieldName;
    temp = temp.toString().replace("event", "");
    queryObject[temp] = newFilters[item].criteria;
  }

  // clear the search now
  prevSearch.value = "";
  searchInput.value = "";

  await router.push({ path: `${eventViewPath.value}`, query: queryObject })
};
const handleClose = () => {
  isMenuVisible.value = false; // Handles closing the modal when the Close button is clicked
}

const resetEvents = () => {
  events.value = [];
  offset.value = 0;
  isFetching.value = false;
}


const handleQueryURLChanging = async (selectFilts: [], searchTerm) => {
  if (!searchTerm || searchTerm == "") {
    selectedFilters.value = selectFilts;

    // there is query filter for a filter => Update the filter
    if (selectFilts) {
      curFilters.value = [ defaultFilter ];

      for (let key in selectFilts) {
        curFilters.value.push({
          fieldName: 'event' + key.charAt(0).toUpperCase() + key.substring(1),
          criteria: selectFilts[key]
        })
      }
    }

  } else {
    let queryObject = {};

    curFilters.value = [ defaultFilter ];
    prevSearch.value = searchTerm;
    searchInput.value = searchTerm;
    selectedFilters.value = [];
    queryObject['searchString'] = searchTerm;

    await router.push({ path: `${eventViewPath.value}`, query: queryObject });
  }
  resetEvents();
  await loadMoreEventWithFilterAndSorter();
}

const handleSearchQueryChange = () => {
// if the searchInput bar is blank, ie the search was put in the address bar...
  if (searchInput.value == "" && route.query.searchString)
  {
    prevSearch.value = route.query.searchString
    searchInput.value = prevSearch.value

  } else if (searchInput.value != "") {
    prevSearch.value = searchInput.value
  }

  let queryObject = {};

  if (prevSearch.value != "") {
    queryObject['searchString'] = prevSearch.value;
  }

  router.push({ path: `${eventViewPath.value}`, query: queryObject });
}

// Method to handle sorting
// Reset and reload events with new sorting
const getSortingCriteria = async (field: string) => {
  if (activeSortField.value === field) {
    // Toggle sorting order if the same field is clicked
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    //console.log(sortOrder.value)
  } else {
    // Set the new field and reset to descending
    activeSortField.value = field;
    sortOrder.value = 'desc';
  }
  // wrap sorter field and sorter order into object to handle load event function
  curSorter.value = {
    fieldName: activeSortField.value || 'eventStartDate',
    order: sortOrder.value.toLowerCase() === 'asc' ? 'ASC' : 'DESC',
  };
  // Reset offset and fetch sorted data
  resetEvents();
  await loadMoreEventWithFilterAndSorter();
};

// Load initial events
onMounted(() => {

  paramEventView.value = [];

  for (let key in route.query) {
    paramEventView.value[key] = route.query[key] || [];

    if (paramEventView.value[key] && !Array.isArray(paramEventView.value[key])) {
      paramEventView.value[key] = [paramEventView.value[key]];
    }
  }

  // Ensure paramEventView.value is always an array
  if (paramEventView.value && !Array.isArray(paramEventView.value)) {
    paramEventView.value = [paramEventView.value];
  }

  let searchTerm = route.query.searchString || ""

  handleQueryURLChanging(paramEventView.value, searchTerm)

  const handleScroll = () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 50) {
      loadMoreEventWithFilterAndSorter();
    }
  };
  window.addEventListener('scroll', handleScroll);

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll); // Clean up event listener
  });
});

</script>

<template>
  <!-- FilterView Modal -->
  <FilterView
    :is-menu-visible="isMenuVisible"
    :resetFilterView="selectedFilters"
    @apply-filters="updateSelectedFilters"
    :on-close="handleClose"
  />
  <div class="eventSpace">
    <ToolbarComponent @search-submitted="handleSearchQueryChange" @open-filter-modal="isMenuVisible = true"
                      @clear-filters="clearFilters" @update-filters="updateSelectedFilters" @close-filter-modal="handleClose"
                      @clear-search="clearSearch" @update-sort="getSortingCriteria"
                      v-model:searchInput="searchInput" v-model:prevSearch="prevSearch" v-model:selectedFilters="selectedFilters"
                      v-model:isMenuVisible="isMenuVisible" v-model:curFilters="curFilters" v-model:curSorter="curSorter"
                      v-model:activeSortField="activeSortField" v-model:autocompleteEvents="autocompleteEvents"
                      v-model:sortOrder="sortOrder"
                      :noSearchResult="noSearchResults"/>

    <div v-if="isFetching && offset == 0" id="loadingSpinner">
      <ProgressSpinner />
    </div>

    <div v-else class="eventContainer">

      <h1 v-if="error">{{ error }}</h1>
      <h2 v-if="events.length === 0 && !error  && !isFetching && !searchInput" style="margin-top: 3rem">{{noEventsMessage}}</h2>
      <h2 v-if="events.length === 0 && !error  && !isFetching && searchInput" style="margin-top: 3rem; text-align:center">{{noSearchResults}}</h2>
      <br/>

      <h6 id="filterDisplayText" v-if="filteredEvents.length || !isFetching">{{ filterDisplay }}</h6>

      <div v-for="(event, index) in filteredEvents" :key="event.id" style="margin: 1rem" id="eventFeed">
        <div v-if="event.id !== 'ad'">
          <EventComponent :event="event" :index="index" :currpath="eventViewPath" />
        </div>
        <div v-if="event.id == 'ad' && scope !== 'moderator' && !premiumState.status">
          <AdComponent  :style="'display: block;'" :client="clientID" :ad-slot="slotID" :format="'fluid'" :ad-type="'event-ad'"></AdComponent>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

.eventSpace {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  margin: 0 auto;
  margin-top: 2rem;
}

.eventSpace {
  margin-top: 3rem;
  margin-left: auto;
  margin-right: auto;
}

.eventContainer {
  width: 80%;
  margin-left: auto;
  margin-right: auto;
}

#filterDisplayText {
  text-align: center;
  color: gray;
}

#loadingSpinner {
  margin-top: 4rem;
}


</style>
