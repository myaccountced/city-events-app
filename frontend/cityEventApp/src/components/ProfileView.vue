<script setup lang="ts">
import {ref, onMounted, watch} from 'vue'
import SubscriptionPlan from '@/components/SubscriptionPlan.vue'
import { useAuth } from '@/useAuth'
import Avatar from 'primevue/avatar'
import Card from 'primevue/card'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import NotificationReferenceView from "@/components/NotificationReferenceView.vue";
import {useRoute} from "vue-router";

const username = ref<string>('');
const profileUserid = ref<string>('');
const userEmail = ref<string>('');
const userMod = ref<boolean>(false);
const userCreation = ref<object>();
const { premiumState, premiumDaysRemaining, token, userId } = useAuth();
const isSubscriptionSubmitted = ref<boolean>(false);
const backendUrl = import.meta.env.VITE_BACKEND_URL;
const isFetching = ref(false);

// This variable checks if the current user at this profile is logged in
const loggedIn = ref(false);

let userInitial = ref("");

if (useAuth().user.value) {
  loggedIn.value = true;
  userInitial.value = (useAuth().user.value).toString().at(0).toUpperCase();
}

// THis variable checks if we are searching this profile via user id 'id' or username 'name'
const profileMode = ref("id");

// This variable checks if the current user owns the profile page
const ownProfile = ref(true);

// route to watch for query
const route = useRoute();

// Variable to display the follow/unfollow text
const followButtonText = ref("Follow");


/**
 * Toggles whether the current user is following the profile user.
 */
const toggleFollowing = async () => {
  let bodyObject = {
    userId: userId.value,
    setFollowTo: followButtonText.value === "Follow"
  };

  try {
    const res = await fetch(`${backendUrl}/user/follow/${profileUserid.value}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + token.value
      },
      body: JSON.stringify(bodyObject)
    });

    if (res.ok) {
      followButtonText.value = followButtonText.value == "Follow" ? "Unfollow" : "Follow";
    }

  } catch (e) {
    console.log(e);
  }
}


/**
 * Finds the proper value for profileUserid for the user of the profile page.
 */
const getProfileUser = async () => {
  profileMode.value = "id";

  // The profile page has been queried by ID
  if (route.query['userid']) {
    ownProfile.value = false;
    profileMode.value = "id";

    profileUserid.value = route.query['userid'];

  } else if (route.query['username']) {
    // The profile page has been queried by username
    ownProfile.value = false
    profileMode.value = "name";

    profileUserid.value = route.query['username'];

  } else {
    // The profile page is for the current user
    ownProfile.value = true;
    let curID = localStorage.getItem('userid');
    profileUserid.value = JSON.parse(curID)?.value
  }

  // Loading the correct profile page
  await loadProfileInfo();
}

// Watching if the query changes to someone else's profile
watch(() => route.query, () => {
  getProfileUser();
});


/**
 * Loads the user represented by profileUserid based on the profileMode.
 */
const loadProfileInfo = async () => {
  if (isFetching.value) {return;}
  isFetching.value = true;

  // profileUserid is currently set
  if (profileUserid.value !== '') {
    try {
      let res;

      // If we are getting a user based on a user's id
      if (profileMode.value == "id") {
        res = await fetch(`${backendUrl}/user/${profileUserid.value}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token.value
          }
        });
      } else {
        // They are searching by NAME, and we only want ONE result
        res = await fetch(`${backendUrl}/users?like=${profileUserid.value}&limit=1`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token.value
          }
        });
      }

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`)
      }

      // The user received
      const data = await res.json()

      // If there is a user with that user id or that exact username
      if (data && data.length != 0 && ( profileMode.value != 'name' ||
          (profileMode.value == 'name' && data[0].username.toLowerCase() == profileUserid.value.toString().toLowerCase()) ) ) {

        // Now we have the ID
        profileUserid.value = data[0].id

        username.value = data[0].username
        userInitial.value = (username.value).toString().at(0).toUpperCase();
        userEmail.value = data[0].email
        userMod.value = data[0].mod
        userCreation.value = new Date(data[0].creationDate.date).toLocaleDateString()

        // The current user on the site is looking at their own page
        if (data[0].id == JSON.parse(localStorage.getItem('userid'))?.value) {
          ownProfile.value = true;
        }

        // If the user is logged in, check for follow status
        if (loggedIn.value && !ownProfile.value) {
          const followResponse = await fetch(`${backendUrl}/user/follow/${profileUserid.value}?userId=${userId.value}`, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              Authorization: 'Bearer ' + token.value
            }
          });

          if (followResponse.ok) {
            const followData = await followResponse.json();
            followButtonText.value = followData.Following ? "Unfollow" : "Follow";
          }

        }

      } else {
        // The user you are trying to find does not exist
        username.value = "";
      }

    } catch (e) {
      console.error('Error fetching user data:', e);
    }
  } else {
    // The profile user is unset, and needs to be set
    await getProfileUser();
  }

  isFetching.value = false;
}


onMounted(() => {
  getProfileUser();
})


const handleSubscriptionSubmitted = async () => {
  isSubscriptionSubmitted.value = true
}


const customAvatar = ref({
  xl: {
    width: '10rem',
    height: '10rem',
    fontSize: '8rem',
  }
})
</script>

<template>
  <div class="p-4 flex align-items-center" id="profile-page">
    <!-- Header Section with Premium Status -->
    <div class="flex align-items-center gap-3 mb-4">
      <h1 class="text-3xl">Account Information</h1>
    </div>

    <div v-if="isFetching" id="loadingSpinner">
      <ProgressSpinner />
    </div>

    <div v-else-if="username == ''">
      <h3>Unable to find user</h3>
    </div>

    <!-- User Information Card -->
    <Card v-else id="infoCard">
      <template #content>
        <div>
          <div id="container">
            <Avatar :label=userInitial shape="circle" size="xlarge" :dt="customAvatar" style="margin-top: 2rem" id="avatar"/>
            <div class="grid" id="userInformation">
              <div v-if="userMod" class="flex align-items-center">
                <img
                  src="../assets/icon/mod-acc-icon.svg"
                  alt="Moderator_Icon"
                  style="width: 40px; height: 40px; margin-left: 10px"
                />
              </div>
              <div class="col-12 md:col-6">
                <div class="mb-3">
                  <label class="block text-gray-600 mb-1 fw-bolder">Username:</label>
                  <span v-if="followButtonText == 'Unfollow'" class="float-end fw-light fst-italic" id="followStatus">Following</span>
                  <div id="username" class="text-xl">{{ username }}</div>
                </div>
              </div>

              <div class="col-12 md:col-6" v-if="!ownProfile && loggedIn">
                <div class="mb-3">
                  <Button id="followUnfollowButton" @click="toggleFollowing">{{ followButtonText }}</Button>
                </div>
              </div>
              <div class="col-12 md:col-6" v-if="ownProfile">
                <div class="mb-3">
                  <label class="block text-gray-600 mb-1 fw-bolder">Email:</label>
                  <div id="userEmail" class="text-xl">{{ userEmail }}</div>
                </div>
              </div>
              <div class="col-12 md:col-6">
                <div class="mb-3">
                  <label class="block text-gray-600 mb-1 fw-bolder">Account Creation Date:</label>
                  <div id="userCreation" class="text-xl">{{ userCreation }}</div>
                </div>
              </div>

            </div>
          </div>

          <div v-if="premiumState.status" id="premiumStatusArea">
            <img
              src="../assets/icon/premium-acc_icon.png"
              alt="Premium_Icon"
              style="width: 40px; height: 40px"
            />
            <span id="premium-message" class="font-bold">{{ premiumDaysRemaining }} days remaining of your
            premium status</span>
          </div>
          <!-- Subscription Section -->
          <form v-if="!userMod && ownProfile"  id="profile-subscription" @submit.prevent="handleSubscriptionSubmitted">
            <!-- Subscription Plan Component -->
            <SubscriptionPlan
              :trigger-submit="isSubscriptionSubmitted"
              :current-username="username"
              @reset:triggerSubmit="isSubscriptionSubmitted = false"
            />
            <Button type="submit" style="margin-top: 1rem">Submit</Button>
          </form>
          <NotificationReferenceView/>
        </div>
      </template>
    </Card>

  </div>
</template>

<style scoped>

#profile-subscription {
  display: flex;
  flex-direction: column;
  border: 2px solid #ccc; /* Add a solid border */
  border-radius: 8px; /* Add rounded corners */
  //margin: 20px 100px; /* Add space around the fieldset */
  padding: 10px;
  animation: backgroundColorAnimation 2s infinite;
  align-items: center;
  justify-content: center;
}


h1 {
  font-size: 2.5rem;
/*  margin-bottom: 20px;*/
  text-align: center;
}

@media (max-width: 600px) {
  h1 {
    font-size: 2rem;
  }

  #userInformation {
    font-size: 14pt;
    margin-left: 0.5rem;
  }

  #infoCard {
    width: 100%;
  }

  #avatar {
    display: none;
  }

  #premium-message {
    display: flex;
  }

  img {
    display: flex;
    float: left;
    margin-right: 3px;
  }

  #premiumStatusArea {
    margin-bottom: 1rem;
  }

  #container {
    display: flex;
  }
}

@media (min-width: 601px) {

  Avatar {
    display: flex;
    float: left;
  }

  #userInformation {
    font-size: 14pt;
    margin-left: 3rem;
    margin-top: 1rem;
    float: right;
  }

  #infoCard {
    width: 40%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #premiumStatusArea {
    display: flex;
    align-items: center;
    justify-items: center;
    margin-bottom: 1rem;
  }

  img {
    display: flex;
    float: left;
    margin-right: 3px;
  }

  #container {
    display: flex;
  }

  #profile-subscription {
    display: flex;
    clear: left;
    border: 2px solid #ccc; /* Add a solid border */
    border-radius: 8px; /* Add rounded corners */
    //margin: 20px 100px; /* Add space around the fieldset */
    padding: 10px;
    animation: backgroundColorAnimation 2s infinite;
    align-items: center;
    justify-content: center;
  }

}

@keyframes backgroundColorAnimation {
  0% {
    background-color: #e8e085;
  }
  50% {
    background-color: white;
  }
  100% {
    background-color: #e8e085;
  }
}

#profile-page {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  margin: 0 auto;
}

#loadingSpinner {
  margin-top: 4rem;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}

</style>
