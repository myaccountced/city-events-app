<script setup lang="ts">
import {reactive, ref, toRefs, watch} from 'vue'
import { Dialog, Button, Tree, InputText, DatePicker } from "primevue"

// Defining used props used by the FilterView
const props = defineProps<{
  isMenuVisible: Boolean, //Controls modal visibility
  // filters: usedFilters, //Sets Filters to be used by the FilterView
  resetFilterView: any[],
  onClose: () => void, //returns void on modal closing
}>()

const emit = defineEmits<{
  (event: 'apply-filters', selected: {fieldName:string,criteria:any[]}[]): void // Sets up the emit for apply filters when the button is clicked to update the table
}>()


const categoryNames = ['Arts and Culture', 'Education', 'Food and Drink', 'Health and Wellness', 'Music', 'Nature and Outdoors', 'Sports', 'Technology', 'Others'];
let categoryChildren = [];
for (let i = 0; i < categoryNames.length; i++) {
  let categoryObject = {
    key: 'category-' + (categoryNames[i].toString().replaceAll(" ", "-")),
    label: categoryNames[i],
    checked: false
  };
  categoryChildren.push(categoryObject);
}

const categories = {
  key: 'category',
  label: 'Categories',
  children: categoryChildren
}

const audienceNames = ['Adult Only', 'Family Friendly', 'Teens and Up', 'Youth', 'General']
let audienceChildren = [];
for (let i = 0; i < audienceNames.length; i++) {
  let audienceObject = {
    key: 'audience-' + (audienceNames[i].toString().replaceAll(" ", "-")),
    label: audienceNames[i],
    checked: false
  };
  audienceChildren.push(audienceObject);
}

const audiences = {
  key: 'audience',
  label: 'Audiences',
  children: audienceChildren
}

const chooseFilters = ref([
  categories,
  audiences
]);
const locationText = ref("");
const startDate = ref()
const endDate = ref()
const expandedKeys = ref([]);
const selectedKeys = ref([]);
const onFirstOpen = ref(true);


watch(() => props.isMenuVisible, () => {
  if (props.isMenuVisible && onFirstOpen.value) {
    expandedKeys.value = [];
    selectedKeys.value = [];
    locationText.value = "";
    startDate.value = null;
    endDate.value = null;

    for (let fil in props.resetFilterView) {
      if (fil === 'searchString') {
        continue;
      }

      if (fil === 'location') {
        locationText.value = props.resetFilterView[fil][0];
      } else if (fil === 'startDate') {
        let temp = new Date(props.resetFilterView[fil][0]);
        let oneDay = new Date(0);
        oneDay.setDate(32);
        startDate.value = new Date(temp.valueOf() + oneDay.valueOf());
      } else if (fil === 'endDate') {
        let temp = new Date(props.resetFilterView[fil][0]);
        let oneDay = new Date(0);
        oneDay.setDate(32);
        endDate.value = new Date(temp.valueOf() + oneDay.valueOf());
      } else {
        expandedKeys.value[fil] = true;
        selectedKeys.value[fil] = { checked: false, partialChecked: true };

        for (let opt in props.resetFilterView[fil]) {
          let filterValue = props.resetFilterView[fil][opt];
          filterValue = filterValue.toString().replaceAll(" ", "-");
          filterValue = fil + "-" + filterValue;
          selectedKeys.value[filterValue] = { checked: true, partialChecked: false };
        }
      }

    }

    onFirstOpen.value = false;
  }
})



const applyFilters = () => {

  const appliedFilters = [];

  let filtOb = {};

  for (let item in selectedKeys.value) {
    let separator = item.indexOf("-")
    if (separator != -1) {
      let filterType = item.substring(0, separator);
      let filterValue = item.substring(separator + 1).replaceAll("-", " ");

      if (filtOb[filterType]) {
        filtOb[filterType].push(filterValue);
      } else {
        filtOb[filterType] = [ filterValue ];
      }
    }
  }

  for (let filter in filtOb) {
    appliedFilters.push({ fieldName: filter, criteria: filtOb[filter]});
  }

  if (locationText.value.toString().trim() !== '') {
    appliedFilters.push({ fieldName: 'location', criteria: [locationText.value.toString().trim()] });
  }

  if (startDate.value) {
    let temp = new Date(startDate.value);
    // Format to yyyy-mm-dd
    let formattedStartDate = temp.getFullYear()+"-"+ (temp.getMonth()+1) + '-' + temp.getDate();
    appliedFilters.push({ fieldName: 'startDate', criteria: [formattedStartDate] });
    //appliedFilters.push({ fieldName: 'endDate', criteria: [temp.toLocaleDateString()] });
  }

  if (endDate.value) {
    let temp = new Date(endDate.value);
    let formattedEndDate = temp.getFullYear()+"-"+ (temp.getMonth()+1) + '-' + temp.getDate();
    appliedFilters.push({ fieldName: 'endDate', criteria: [formattedEndDate] });
    //appliedFilters.push({ fieldName: 'endDate', criteria: [temp.toLocaleDateString()] });
  }

  // In this case the key will be of type string
  emit('apply-filters', appliedFilters) // checks to see applyFilters button and method are run and apply the selected string array of filters

  onFirstOpen.value = true;
  props.onClose() // Close the modal
}

const close = () => {
  onFirstOpen.value = true;
  props.onClose() // Closes the modal via the Close button
}

</script>

<template>
  <Dialog class="modal" v-bind:visible="isMenuVisible" modal header="Advanced Filters" :closable="false" :close-on-escape="false">

    <div class="mb-3">
      <Tree v-model:selection-keys="selectedKeys" :value="chooseFilters" :expanded-keys="expandedKeys" selection-mode="checkbox"></Tree>
    </div>


    <div class="d-flex flex-column justify-content-start  gap-2 mb-3">
      <label for="locationFilterInput" class="mx-3">Location</label>
      <InputText v-model="locationText" id="locationFilterInput" class="mx-3" />
    </div>

    <div class="d-flex flex-row justify-content-center mt-5">
      <div class="d-flex flex-column justify-content-start gap-2 mb-3">
        <!--      year-month-day-->
        <label for="startDateInput" class="mx-3">Starting on:</label>
        <DatePicker v-model="startDate" date-format="yy-mm-dd" input-id="startDateInput" class="mx-3" />
      </div>

      <div class="d-flex flex-column justify-content-start  gap-2 mb-3">
        <label for="endDateInput" class="mx-3">Ending on:</label>
        <DatePicker v-model="endDate" date-format="yy-mm-dd" input-id="endDateInput" class="mx-3" />
      </div>
    </div>


    <template #footer>
      <div class="d-flex justify-content-end gap-2">
        <Button id="cancelFilters" type="button" label="Cancel" severity="secondary" @click="close"></Button>
        <Button id="applyFilters" type="button" label="Apply" @click="applyFilters"></Button>
      </div>
    </template>


  </Dialog>
</template>


<style scoped>

</style>
