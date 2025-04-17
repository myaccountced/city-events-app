<script setup lang="ts">
import 'bootstrap-icons/font/bootstrap-icons.css';
import Button from 'primevue/button'
import Toolbar from 'primevue/toolbar'
import AutoComplete from 'primevue/autocomplete'
import FilterView from '@/components/FilterView.vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
const router = useRouter();
const route = useRoute();

const emit = defineEmits(['search-submitted', 'open-filter-modal', 'clear-filters',
  'update-filters', 'close-filter-modal', 'clear-search', 'update-sort']);

const props = defineProps({
  noSearchResults: String
})

const searchInput = defineModel('searchInput');
const prevSearch = defineModel('prevSearch');
//const selectedFilters = defineModel('selectedFilters');
//const isMenuVisible = defineModel('isMenuVisible');
//const curFilters = defineModel('curFilters');
//const curSorter = defineModel('curSorter');
//const filters = defineModel('filters');
const activeSortField = defineModel('activeSortField');
const autocompleteEvents = defineModel('autocompleteEvents');
const sortOrder = defineModel('sortOrder');



</script>

<template>
  <Toolbar class="sortingToolbar">
    <template #start>
      <!-- Filter and Clear Buttons-->
      <Button id="filterButton" size="small" @click="emit('open-filter-modal')">Filter</Button>
      <Button id="clearFilters" size="small" @click="emit('clear-filters')">Clear Filters</Button>
    </template>

    <template #center>
      <strong>Sort By:</strong>
      <div class="sortTitle" @click="emit('update-sort', 'eventTitle')" style="margin-left: 1rem; margin-right: 1rem;">
        Title
        <span v-if="activeSortField !== 'eventTitle'">
              <i class="bi bi-arrow-down-up"></i>
            </span>
        <span v-else>
              <i v-if="sortOrder === 'asc'" class="bi bi-caret-up-fill"></i>
              <i v-else class="bi bi-caret-down-fill"></i>
            </span>
      </div>

      <div class="sortLocation" @click="emit('update-sort', 'eventLocation')" style="margin-left: 1rem; margin-right: 1rem;">
        Location
        <span v-if="activeSortField !== 'eventLocation'">
              <i class="bi bi-arrow-down-up"></i>
            </span>
        <span v-else>
              <i v-if="sortOrder === 'asc'" class="bi bi-caret-up-fill"></i>
              <i v-else class="bi bi-caret-down-fill"></i>
            </span>
      </div>

      <div class="sortStartDate" @click="emit('update-sort', 'eventStartDate')" style="margin-left: 1rem; margin-right: 1rem;">
        Start Date
        <span v-if="activeSortField !== 'eventStartDate'">
              <i class="bi bi-arrow-down-up"></i>
            </span>
        <span v-else>
              <i v-if="sortOrder === 'asc'" class="bi bi-caret-up-fill"></i>
              <i v-else class="bi bi-caret-down-fill"></i>
            </span>
      </div>
    </template>
    <!--      Search bar for events-->
    <template #end>
      <AutoComplete id="searchEvents" v-model="searchInput" :suggestions="autocompleteEvents" optionLabel="title"
                    placeholder="Search" @complete="emit('search-submitted')"
                    v-on:keyup.enter="emit('search-submitted')" :delay="500" :emptySearchMessage="props.noSearchResults"></AutoComplete>
      <Button icon="pi pi-times" rounded size="small" id="clearSearch" aria-label="Clear Search" v-tooltip="{value: 'Clear search'}"
              @click="emit('clear-search')" />
    </template>

  </Toolbar>
</template>

<style scoped>

  Toolbar {
    margin-left: auto;
    margin-right: auto;
  }

  Button {
    margin-right: 5px;
    margin-left: 5px;
  }


  @media (max-width: 600px) {
    .sortingToolbar {
      margin-right: 2rem;
      margin-left: 2rem;
    }
  }
</style>