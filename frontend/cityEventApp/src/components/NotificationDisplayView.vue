<template>
  <div v-if="token">
    <Button @click="toggleNotificationPanel">
      <i class="pi pi-bell"></i>
      <span v-if="unreadNotifications.length" class="notification-badge">
        {{ unreadNotifications.length }}
      </span>
    </Button>

    <div v-if="showNotifications" class="notification-dropdown">
      <ul>
        <li v-for="notification in unreadNotifications" :key="notification.id">
          {{ notification.message }}
          <button @click="markAsRead(notification.id)">✔</button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Button from 'primevue/button';
import useNotificationFetch from "@/scripts/NotificationFetch"
import {useAuth} from "@/useAuth";

const {token, loadUserFromLocalStorage} = useAuth();
const {unreadNotifications, fetchUnreadNotification, markAsRead} = useNotificationFetch();
/*let unreadNotifications = computed( ()=> {
  return UnreadNotification.value;
})*/
const showNotifications = ref(false);

const toggleNotificationPanel = () => {
  showNotifications.value = !showNotifications.value;
};

onMounted(() => {
  fetchUnreadNotification();
});
</script>

<style scoped>
.notification-badge {
  background-color: red;
  color: white;
  border-radius: 50%;
  padding: 3px 6px;
  font-size: 12px;
}
.notification-dropdown {
  position: absolute;
  right: 0;
  background: aquamarine;
  border: 1px solid #ccc;
  width: 250px;
  max-height: 300px;
  overflow-y: auto;
}
</style>