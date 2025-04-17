<script setup lang="ts">
import { GlExternalLink } from '@kalimahapps/vue-icons'

import {reportReasons} from "@/components/interfaces/reportReason";
import {computed, ref, onMounted} from "vue";
import ReportView from "@/components/ReportView.vue";
import {useAuth} from "@/useAuth";
import {useRoute} from 'vue-router'

import { FlBookmark } from '@kalimahapps/vue-icons';
import { FlFilledBookmark } from '@kalimahapps/vue-icons';
import useEventFetch from '@/scripts/EventFetch'
import axios from 'axios'
import type {Category} from "@/components/interfaces/Category";
import EventComponent from "@/components/EventComponent.vue";

const backendUrl = import.meta.env.VITE_BACKEND_URL;
// const props = defineProps<{
//   event: object,
//   index: number
// }>()
const route = useRoute();
let id = route.query.id;

const {user, token} = useAuth();


interface Event {
  id: number;
  eventTitle: string;
  eventDescription: string;
  eventCategory: Category;
  eventAudience: string;
  eventLocation: string;
  eventStartDate: string;
  eventEndDate: string;
  eventCreator: string;
  eventLink: string;
  eventImages: string;
  reports: Report[];
  imagePaths: string[];
}
// the route for this will be /event?id=<event id>
// will need to fetch and set the event to this result
const event = ref<Event>(null);

const getEvent = async () => {
  // Makes a fetch request to backendurl/event/{eventID}
  // receives a JSON response of the event
  // Map the received response to an event object
  const eventId = route.params.id;

  const response = await fetch(`${backendUrl}/event/${eventId}`);
  if (!response.ok) {
    throw new Error('Failed to fetch event');
  }

  const json = await response.json();

  // assign the json to the event
  event.value = json;
};


onMounted(async () => {
  await getEvent();
  //console.log(event.value)
  //console.log("Show something!!")
  // Once data is loaded, set dynamic meta tags
/*  useHead({
    title: event.value.title,
    meta: [
      { property: 'og:title', content: event.value.title },
      { property: 'og:description', content: event.value.description },
      { property: 'og:image', content: <CHANGE TO FIRST IMAGE FROM MEDIA>},
      { property: 'og:url', content: window.location.href },
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: event.value.title },
      { name: 'twitter:description', content: event.value.description },
      { name: 'twitter:image', content: <CHANGE TO FIRST IMAGE FROM MEDIA> }
    ]
  });*/
});




</script>

<template>
  <div v-if="event" class="eventContainer">
    <EventComponent :event="event" />
  </div>
</template>

<style scoped>

</style>