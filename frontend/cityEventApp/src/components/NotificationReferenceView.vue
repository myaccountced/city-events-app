<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import 'primeicons/primeicons.css';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import InputSwitch from 'primevue/inputswitch';
import {useAuth} from "@/useAuth";
const backendUrl = import.meta.env.VITE_BACKEND_URL;
const {user, token} = useAuth();
interface NotificationPreference {
  wants_notifications: boolean;
  notification_methods: string[];
  notification_time: string[];
}

const wantsNotifications = ref<boolean>(false);
const selectedNotificationMethods = ref<string[]>([]);
const selectedNotificationTimes = ref<string[]>([]);
const previousState = ref<NotificationPreference>({
  wants_notifications: false,
  notification_methods: [],
  notification_time: []
});

const notificationMethods = ref<{ label: string; value: string }[]>([
  { label: 'Email', value: 'email' },
  // { label: 'In-App', value: 'in_app' }
]);

const notificationOptions = ref<{ label: string; value: string }[]>([
  { label: 'On the day of the event', value: 'day0' },
  { label: '1 day before the event', value: 'day1' },
  { label: '1 week before the event', value: 'day7' }
]);

const fetchPreferences = async () => {
  try {
    const response = await fetch(backendUrl + `/api/user/notification-preferences`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      }
    })
    if (response.ok) {
      const data = await response.json();
      Object.assign(previousState.value, data);
      resetPreferences();
    } else {
      console.error("Failed to fetch Notification status");
    }
  } catch (error) {
    console.error('Error fetching preferences:', error);
  }
};

const savePreferences = async () => {
  const preferences: NotificationPreference = {
    wants_notifications: wantsNotifications.value,
    notification_methods: selectedNotificationMethods.value, // Update to use array of methods
    notification_time: selectedNotificationTimes.value
  };
  console.log('update Notification: ', JSON.stringify(preferences));

  try {
    const response = await fetch(backendUrl + `/api/user/save-notification-preferences`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: JSON.stringify(preferences)
    })
    if (response.ok) {
      const data = await response.json();
      Object.assign(previousState.value, data);
      resetPreferences();
      alert('Your notification setting has been successfully updated.');
    } else {
      console.error("Failed to Update Notification status");
    }
  } catch (error) {
    console.error('Error saving preferences:', error);
  }
};

const resetPreferences = () => {
  wantsNotifications.value = previousState.value.wants_notifications;
  //selectedNotificationMethods.value = previousState.value.notification_methods || [];
  selectedNotificationMethods.value = ['email'];
  selectedNotificationTimes.value = previousState.value.notification_time || [];
};

onMounted(fetchPreferences);
</script>

<template>
  <div id="notification-setting">
    <div class="flex align-items-center gap-3 mb-4">
      <h3 class="text-3xl">Notification Preferences</h3>
    </div>

    <div class="notification-toggle">
      <h5>Do you want to receive notifications?</h5>
      <InputSwitch id="btnNotified" v-model="wantsNotifications"></InputSwitch>
    </div>

    <div v-if="wantsNotifications">
      <h3>Notification Method</h3>
      <div v-for="method in notificationMethods" :key="method.value" class="flex align-items-center gap-2">
        <Checkbox v-model="selectedNotificationMethods" :value="method.value" :disabled="true"></Checkbox>
        <label>{{ method.label }}</label>
      </div>

      <h3>Notification Timing</h3>
      <div v-for="option in notificationOptions" :key="option.value" class="flex align-items-center gap-2">
        <Checkbox v-model="selectedNotificationTimes" :value="option.value" :id="option.value"></Checkbox>
        {{option.label}}
      </div>
    </div>
    <div class="button-container">
      <Button id="btnSavePref" label="Save Preferences" @click="savePreferences"></Button>
      <Button id="btnCancelPref" label="Cancel" class="p-button-secondary" @click="resetPreferences"></Button>
    </div>
  </div>
</template>

<style scoped>
.notification-toggle {
  display: flex;
  align-items: center;
  gap: 10px; /* Space between text and InputSwitch */
}
.button-container {
  display: flex;
  justify-content: center;
  gap: 10px; /* Space between buttons */
  margin-top: 10px;
}
@media (min-width: 601px) {
  /* Add any custom styles here */
  #notification-setting {
    display: flex;
    flex-direction: column;
    border: 2px solid #ccc; /* Add a solid border */
    border-radius: 8px; /* Add rounded corners */
    padding: 10px;
    animation: backgroundColorAnimation 2s infinite;
    align-items: center;
    justify-content: center;
  }


  #notificationCard {
    width: 100%
  }
}

#notification-setting {
  margin-top: 1rem;
}
</style>