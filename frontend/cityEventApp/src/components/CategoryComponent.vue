<script setup lang="ts">
import {computed, inject} from 'vue'
import {Tag} from "primevue"
import type {Category} from "@/components/interfaces/Category";
import { getCategoryByName } from "@/components/interfaces/Category";
import FilterItemComponent from "@/components/FilterItemComponent.vue";

const props = defineProps({
  cateName: String
});

const emit = defineEmits(['click-category'])
const defaultCategory: Category = { id: 399, name: 'Others', color: 'var(--p-gray-400)', icon: 'pi pi-question-circle'};

const myCategory = computed<Category>(() => {
  return props.cateName ? getCategoryByName(props.cateName) || defaultCategory : defaultCategory;
});

function handleClick() {
  emit('click-category', { selectedValue: myCategory.value.name, selectedProperty: 'category'})
}

</script>

<template>
  <Tag :style="`background: ${myCategory.color}; color: var(--text-color); margin-left: 2px; margin-right: 2px`"
       @click="handleClick()"
       style="cursor: pointer;"
       :data-cy="`category-tag-${myCategory.name.replace(/\s/g, '-').toLowerCase()}`"
       rounded
  >
    <div class="flex items-center gap-2 px-1">
      <i :class="myCategory.icon" class="text-sm-center" style="font-size: 1rem; margin-right: 4px;"
         :data-cy="`event-category-icon-${myCategory.name.replace(/\s/g, '-').toLowerCase()}`"></i>
<!--      <span class="text-base">{{ myCategory.name }}</span>-->
      <FilterItemComponent :filter-value="myCategory.name" :filter-property="'category'"></FilterItemComponent>
    </div>
  </Tag>
</template>

<style scoped>
.dark i {
  color: #ffffff;
}

.light i {
  color: #222222;
}
</style>
