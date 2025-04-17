<script setup lang="ts">
import { GoogleLogin } from 'vue3-google-login';
import { useAuth } from '@/useAuth'
import {useRouter} from "vue-router";
const router = useRouter();
const { login } = useAuth();

// Callback function for handling Google Sign-In
// continueWithGoogle and fetchRequestToBackend
const continueWithGoogle = async (ggResponse: any) => {
  const idToken = ggResponse.credential;
  console.log('ggId', idToken);
  await fetchRequestToBackend(idToken);
}
const fetchRequestToBackend = async (idToken: any) => {
  // if (!idToken) { return }
  try {
    const response = await fetch(import.meta.env.VITE_BACKEND_URL + '/auth/google-login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        idToken: idToken,
      }),
    });

    // if getting successful feedback from backend
    if (response.ok) {
      const text = await response.text(); // Get the raw response text
      const data = JSON.parse(text);
      if (data.token) {
        login(data.username, data.token, data.userId, data.scope);
        await router.push('/');
      }
    } else {
      window.alert("There is invalid data from your google account, please check your google account again!");
    }
  } catch (error) {
    console.log('There is Error in frontend server', error)
  }

};
</script>

<template>
<!--  <div class="or-line">&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45; OR &#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;</div>-->
  <div class="googlelogin">
    <GoogleLogin :callback="continueWithGoogle" class="login" id="GoogleLogin" @click="fetchRequestToBackend('')"/>
  </div>
<!--  <div class="or-line">&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45; OR -&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;&#45;<span id="GoogleLogin" @click="fetchRequestToBackend('')">-</span></div>-->
</template>

<style>
/* Add any custom styles here */
@media (forced-colors: active) { /* New */}

/*.googlelogin {
  width: 97%;
  padding-top: 7px;
  background-color: #f54918;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}*/

.login {
}
/*.googlelogin:hover {
  background-color: #218838; !* Darker green on hover *!
}*/
.or-line {
  text-align: center;
  margin: 10px 0;
  font-weight: bold;
  color: #333;
}
</style>