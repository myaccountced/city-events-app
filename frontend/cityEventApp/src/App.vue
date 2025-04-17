<script setup lang="ts">
import 'bootstrap-icons/font/bootstrap-icons.css';

import { RouterLink, RouterView, useRouter } from "vue-router";
import { onMounted, ref, watchEffect } from 'vue';
import { useAuth } from './useAuth';
import AdComponent from "@/components/AdComponent.vue";
const { loadUserFromLocalStorage, isAuthenticated, user, logout, scope, premiumState, isMod } = useAuth();
import Menubar from 'primevue/menubar';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import ToggleSwitch from 'primevue/toggleswitch';
import ScrollTop from 'primevue/scrolltop';
import { useWindowSize } from '@vueuse/core'
import ConfirmDialog from "primevue/confirmdialog";

const { width, height } = useWindowSize()
const router = useRouter();

let isDarkMode = ref(false);
const bgColor = ref('#ffffff');

// refs

// get the first letter from the username
let userInitial = ref("");

if (useAuth().user.value)
{
  userInitial.value = (useAuth().user.value).toString().at(0);
}

// Load user information from localStorage when the app is mounted
onMounted(() => {
  loadUserFromLocalStorage();

  if (width <= 600)
  {
    items.value.push({
      icon: 'pi pi-ellipsis-v',

    })
  }

});

watchEffect(() => {
  if (useAuth().user.value)
  {
    userInitial.value = (useAuth().user.value).toString().at(0);
  }
  else {
    userInitial.value = "";
  }

  // if the user is signed in with a moderator account, also show the moderator tool option in the navbar
  if (useAuth().isAuthenticated.value && useAuth().isMod.value)
  {
    items.value.push({
      label: 'Moderator Tools',
      route: '/moderator/pending'
    })
  }
})

// Watch for changes in scope.value
watchEffect(() => {
  isMod.value = scope.value === 'moderator';
});

// Destructure the methods and refs from the useAuth composable


// Function to navigate to registration page
function goToRegistration() {
  router.push("/registration");
}

// Function to toggle authentication state
function toggleAuth() {
  if (isAuthenticated.value) { // If we are signed in
    logout(); // Handle logout logic
    router.push('/signin');
  } else { // If we are not signed in
    router.push('/signin');
  }
}

const clientID = import.meta.env.AD_DATA_CLIENT_ID;
const slotID = import.meta.env.AD_DATA_BANNER_SLOT;

const items = ref([
  {
    label: 'Events',
    route: '/'
  },
  {
    label: 'Profile',
    route: '/profile'
  },
  {
    label: 'My Events',
    route: '/myevents'
  },
  {
    label: 'Past Events',
    route: '/historic'
  },
  {
    label: 'My Bookmarked Events',
    route: '/bookmarks'
  },
  {
    label: 'Post New Event',
    route: '/postevent'
  },
  {

  },
  {
    label: 'Buttons'
  }
]);
// the blank item spot above is there for a reason, don't remove it!!

function toggleDarkMode()
{
  document.documentElement.classList.toggle('toggleTheme');
  // if is already in darkmode...
  if (isDarkMode.value) {
    //change to light mode
    bgColor.value = "#ffffff"
    isDarkMode.value = false;
    if (document.body.classList.contains("dark"))
    {
      document.body.classList.remove("dark")
    }
    document.body.classList.add("light")
  } else {
    // change to dar mode
    bgColor.value = "#222222"
    isDarkMode.value = true;
    if (document.body.classList.contains("light"))
    {
      document.body.classList.remove("light")
    }
    document.body.classList.add("dark")
  }
}

</script>

<template>
  <header>
    <div class="card">
      <Menubar :model="items">
        <template #start>
<!--          <img alt="logo" src="../assets/thumbnail_Motodo.png" />-->
          <h1 id="motodo" style="margin-left: 1rem"><span style="color: var(--p-red-600)">Mo</span><span style="color: var(--p-blue-700)">todo</span></h1>
        </template>
        <template #item="{ item, props }">
          <RouterLink v-if="item.route" v-slot="{href, navigate}" :to="item.route" custom>
            <a :href="href" v-bind="props.action" @click="navigate">
              <span>{{ item.label }}</span>
            </a>
          </RouterLink>
          <span v-if="width <= 600 && !item.route && !item.label" class="toggleSection">
              <i class="pi pi-sun themeToggle"></i>
              <ToggleSwitch class="themeToggle" @click="toggleDarkMode()" id="themeToggler"/>
              <i class="pi pi-moon themeToggle"></i>
          </span>
          <span v-if="item.label == 'Buttons' && width <= 600">
            <Button id="signInOut" @click="toggleAuth" class="normalButton">{{isAuthenticated ? "Sign Out" : "Sign In"}}</Button>
            <Button @click="goToRegistration" class="signup-button normalButton" v-if="!isAuthenticated">Sign Up</Button>
          </span>

        </template>
        <template #end>
          <!--    toggle for light/dark mode-->
          <div id="endSection">
            <span v-if="width > 600">
              <span class="toggleSection">
                <i class="pi pi-sun themeToggle"></i>
                <ToggleSwitch class="themeToggle" @click="toggleDarkMode()" id="themeToggler"/>
                <i class="pi pi-moon themeToggle"></i>
              </span>
              <Button @click="goToRegistration" class="signup-button normalButton" v-if="!isAuthenticated">Sign Up</Button>
              <Button id="signInOut" @click="toggleAuth" class="normalButton">{{isAuthenticated ? "Sign Out" : "Sign In"}}</Button>
            </span>
            <Avatar :v-if="useAuth().isAuthenticated" icon="pi pi-user" :label=userInitial shape="circle" style="margin-right: 1rem" class="profilePicture"/>
            <Avatar v-if="useAuth().isAuthenticated == false" icon="pi pi-user" shape="circle" style="margin-right: 1rem" class="profilePicture"/>
          </div>

        </template>
      </Menubar>
    </div>

  </header>
  <RouterView />
  <ScrollTop target="window" id="scrollToTop"/>

  <footer>
    <div v-if="scope !== 'moderator' && !premiumState.status">
      <AdComponent  :style="'display: block;'" :client="clientID" :ad-slot="slotID" :format="'auto'" :ad-type="'banner-ad'"></AdComponent>
      <div class="adSpaceDoNotDelete">
        &nbsp;
      </div>
    </div>
  </footer>

  <ConfirmDialog />
  <Toast group="ce" position="top-center" successIcon="pi pi-face-smile" errorIcon="pi pi-exclamation-circle" id="globalToast"/>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Madimi+One&display=swap');
main {
  margin: 0 auto;
}

@media (min-width: 1024px) {
  .toggleSection i {
    color: white;
  }
}

.toggleSection {
  align-items: center;
}

.toggleSection i {
  padding: 5px;
}

.adSpaceDoNotDelete {
  height: 170px;
}

.normalButton {
  margin-right: 5px;
  margin-left: 5px;
  background-color: var(--p-sky-700) !important;
}

body {
  background-color: v-bind(bgColor);
}

Menubar {
  background-color: var(--p-sky-500);
}

.p-menubar .p-menubar-item-content:active
{
  background-color: var(--p-sky-900)
}

.menuItem:active {
  background-color: var(--p-sky-900)
}

#endSection {
  display: flex;
  justify-content: center;
  align-items: center;
}

.endSection i {
  margin-left: 4px;
  margin-right: 4px;
  color: white;
}

.dark {
  color: white !important;
}

.light {
  color: black !important;
}

#motodo {
  font-family: "Madimi One", sans-serif;
  text-align: center;
  justify-self: center;
  //text-shadow: -1px -1px 0 #ffffff, 1px -1px 0 #ffffff, -1px 1px 0 #ffffff, 1px 1px 0 #ffffff;
  text-shadow: 2px 2px 4px rgb(255, 255, 255);
  margin-top: 5px;
}

</style>
