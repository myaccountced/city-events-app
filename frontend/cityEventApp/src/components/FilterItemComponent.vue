<script setup lang="ts">

import {Tag} from "primevue";
import {ref} from "vue";

const props = defineProps({
  filterProperty: String,
  filterValue: String
});

const emit = defineEmits<{
  (event: 'click-filter', selected: {filterValue:any,filterProperty:string}[]): void
}>()

let tagStyleClass = ref("");

if (props.filterProperty !== 'category') {
  tagStyleClass.value = "padding: 0; margin: 0; background: none; color: var(--text-color); font-size: 12pt; font-weight: normal";
} else {
  tagStyleClass.value = "padding: 0; margin: 0; background: none; color: var(--text-color)"
}

function handleClick() {
  emit('click-filter', { selectedValue: props.filterValue, selectedProperty: props.filterProperty})
}

</script>

<template>

  <Tag :style="tagStyleClass" :class="filterProperty"
       @click="handleClick()"
       style="cursor: pointer;"
       :data-cy="`${filterProperty}-tag-${filterValue.replace(/\s/g, '-').toLowerCase()}`"
  >
    <div class="flex items-center gap-2 px-1">
      <span class="text-base">{{ filterValue }}</span>
    </div>
  </Tag>

</template>

<style scoped>

tag {

}
</style>