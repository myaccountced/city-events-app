<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/useAuth';
import Button from 'primevue/button';
import Card from 'primevue/card';
import AuthView from '@/components/AuthView.vue'

const router = useRouter();
const { login, isMod } = useAuth(); // Get the login method from useAuth
const identifier = ref('');
const password = ref('');
const errorMessage = ref('');
const welcomeBackUsername = ref<string[]>([]); // Changed to an array to hold multiple usernames
const rememberMe = ref(false);
const userId = ref('');
const backendUrl = import.meta.env.VITE_BACKEND_URL;

/**
 * Handle the sign in function. Sign in using credentials
 */
const handleSignIn = async () => {
  errorMessage.value = ''; // Reset the error message on each attempt
  welcomeBackUsername.value = []; // Reset the welcomeBackUsername array

  try {
    const response = await fetch(import.meta.env.VITE_AUTH_SIGNIN + '/auth/signin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        identifier: identifier.value, // Pass the username or the email as identifier
        password: password.value,
        rememberMe: rememberMe.value
      }),
    });

    //console.log("Here")
    const text = await response.text(); // Get the raw response text

    if (response.ok) {
      //console.log("Here")
      const data = JSON.parse(text);

      if (data.token) {
        // If token is returned, store the token and navigate to home
        const token = data.token;
        userId.value = data.userId;
        login(data.username, token, data.userId, data.scope); // Store the token and username in the local storage
        router.push("/");
      }
    } else {
      // Handle errors if there's an issue with the login
      const errorData = JSON.parse(text);
      if (errorData.error) {
        errorMessage.value = errorData.error || 'Sign in failed.';
      }
      console.error('Sign in failed:', errorData.error);
    }
  } catch (error) {
    // Catch any network errors or unexpected issues
    errorMessage.value = 'Error during sign-in. Please try again later.';
    console.error('Error during sign-in:', error);
  }
};

/**
 * Navigate to the registration page
 */
function navigateToRegister() {
  router.push("/registration");
}

/**
 * Sign in using a valid token
 * @param username
 */
function navigateToHome(username: string) {
  // Store the selected username
  login(username, null, userId);
  router.push("/");
}

/**
 * Handles removing a specified token. Used in when a user wants to forget their remembered account.
 * @param index
 */
function handleForget(index: number) {
  // Get the corresponding token from localStorage and remove it
  const tokenKey = `token${welcomeBackUsername.value[index]}`;
  localStorage.removeItem(tokenKey); // Remove the token from localStorage

  // Remove the username from the array
  welcomeBackUsername.value.splice(index, 1);
  //console.log(`Token for ${welcomeBackUsername.value[index]} has been removed`);
}

// Everytime sign in page is loaded, we send tokens (if they exist in local storage) for validation to the backend
// so we can display remembered users in the sign-in page.
onMounted(() => {
  // Initialize an array to hold all tokens found in localStorage
  const tokens = [];

  // Iterate through all keys in localStorage and find tokens (those with names like 'tokenusername')
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key && key.includes('token')) {
      const token = localStorage.getItem(key);
      if (token) {
        tokens.push(token); // Add token to the array
      }
    }
  }

  if (tokens.length > 0) {
    // Wrap the async logic in an async function, we do this because onMounted is not an async function.
    const fetchTokenData = async () => {
      try {
        const response = await fetch(import.meta.env.VITE_AUTH_SIGNIN + '/auth/signin', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            tokens: tokens // Send all the tokens in the request body
          }),
        });

        const text = await response.text(); // Get the raw response text

        if (response.ok) {
          const data = JSON.parse(text);

          if (data.usernames) {
            // We will filter out the invalid ones: 'not remembered' and 'expired'
            const validUsernames = [];

            data.usernames.forEach((username) => {
              if (username !== 'not remembered' && username !== 'expired') {
                validUsernames.push(username); // Keep the valid usernames
              }
            });

            // Set the valid usernames to welcomeBackUsername
            welcomeBackUsername.value = validUsernames;
          }
        }
      } catch (error) {
        router.push('/signin');
        console.error('Error during sign-in page navigation:', error);
      }
    };

    fetchTokenData(); // Call the async function
  }
});
</script>

<template>
  <div class="signin">
    <div class="flex align-items-center gap-3 mb-4">
      <h1>Sign In to Motodo</h1>
    </div>

    <Card class="container" id="signInCard">
      <template #content>
        <form @submit.prevent="handleSignIn">
          <!-- Show saved usernames with 'Continue' and 'Forget' buttons -->
          <div v-if="welcomeBackUsername.length">
            <div v-for="(username, index) in welcomeBackUsername" :key="username" class="button-group">
              <div style="font-weight: bold; font-size: 13pt" id="welcome">Welcome back {{ username }}!</div>
              <div id="continue-or-forget-btns">
                <Button type="button" class="continue-button" @click="navigateToHome(username)" label="Sign Back In"/>
                <Button type="button" class="forget-button" @click="handleForget(index)" label="Forget Account" />
              </div>
            </div>
          </div>

          <input id="identifier" v-model="identifier" type="text" placeholder="Username or email" required />
          <input id="password" v-model="password" type="password" placeholder="Password" required />
          <div v-if="errorMessage" class="error">{{ errorMessage }}</div>

          <!-- Remember Me Checkbox and Text -->
          <div id="remember-forgot-password">
            <label for="rememberMe" class="remember-me">
              <input id="rememberMe" type="checkbox" v-model="rememberMe" />Remember me
            </label>
            <a class="forgotPassword" :href="`${backendUrl}/reset-password`">Forgot Password</a>
          </div>

          <div class="submit-area">
            <Button type="submit" class="submit-button">Sign In</Button>
            <span id="or-text">or</span>
            <AuthView/>
          </div>

          <div class="or-line">Don't have an account?</div>

          <Button type="button" class="register-button" @click="navigateToRegister">Create Account</Button>
        </form>
      </template>

    </Card>

  </div>
</template>

<style scoped>

@media (max-width: 600px) {
  .container {
    width: 90%;
    display: flex;
/*    justify-content: flex-end;*/
    align-items: center;
    border-radius: 12px;
  }

  input {
    display: flex;
  }
  #identifier, #password {
    width: 100%;
    padding: 10px; /* Increased padding for comfort */
    margin: 20px 100px 10px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    transition: border-color 0.3s;
  }

  Button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .forgotPassword {
    display: flex;
    margin-left: 2rem;
  }

  .remember-me {
    display: flex;
    float: left;
  }

  #or-text {
    color: gray;
  }

  .submit-button {
    width: 180px;
    height: 38px;
    border-radius: 4px;
  }

  .submit-area {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
    margin-top: 1rem;
  }
  #remember-forgot-password {
    display: flex;
    margin-bottom: 1rem;
    font-size: 11pt;
  }

  .continue-button {
    margin-bottom: 0.5rem;
  }

}

@media (min-width: 601px) {
  .container {
    display: flex;
    justify-content: center;
    align-items: center;
/*    width: 100%;
    max-width: 800px; !* Optional max width *!*/
    border-radius: 12px;
  }

  #identifier, #password {
    width: 97%;
    padding: 10px; /* Increased padding for comfort */
    margin: 20px 100px 10px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    transition: border-color 0.3s;
  }

  .register-button {
    width: 97%;
  }

  .submit-button {
    width: 180px;
    height: 38px;
    border-radius: 4px;
  }

  .forget-button {
    //width: 28%;
    //display: flex;
    //float: right;
    //margin-right: 1rem;
  }

  .continue-button {
    //width: 28%;
    //clear: left;
    //margin-bottom: 1rem;
    margin-right: 1rem;
  }

  .button-group {
    justify-content: center;
    align-content: center;
    margin: 0 auto;
  }

  #welcome {
    float: left;
    display: flex;
    justify-content: center;
    margin: 0 auto;
    margin-bottom: 10px;
    margin-top: 5px;
  }

  .submit-area {
    display: flex;
    justify-content: center;
    margin: 0 auto;
    margin-top: 1rem;
  }

  #or-text {
    color: gray;
    margin-top: 8px;
    margin-right: 10px;
    margin-left: 10px;
  }

  /* Ensure the container holding the checkbox aligns content properly */
  .remember-me {
    display: flex; /* Using flexbox to align items */
    align-items: center; /* Vertically center the checkbox and the text */
    margin-left: 0; /* Ensure no margin pushes the label away from the checkbox */
/*    margin-bottom: 10px;*/
  }

  .container {
    width: 40%;
  }

  .forgotPassword {
    margin-left: 23rem;
  }

  #remember-forgot-password {
    display: flex;
    margin-bottom: 2rem;
  }

  #continue-or-forget-btns {
    //display: block;
    display: flex;
    //align-content: end;
    float: right;
    margin-bottom: 10px;

  }
}

/*
.remember-me {
  display: flex;
}*/
/* specifically on the remember me checkbox */
#rememberMe {
  margin-right: 5px;
}

.signin {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
/*  height: 100vh;*/
  font-family: Arial, sans-serif;
  margin-top: 2rem;
}

form {
  padding-bottom: 20px;
  width: 100%;
}

input:focus {
  border-color: #007bff;
  outline: none;
}

button:hover {
  background-color: #0056b3;
}

.error {
  color: red;
  margin-top: 10px;
  margin-bottom: 10px;
  font-size: 14px;
}

.or-line {
  text-align: center;
  font-size: 11pt;
  margin: 10px 0;
  color: #817777;
}

@media (max-width: 400px) {
  input {
    font-size: 14px;
  }
}
.middle {
  text-align: center;
  margin: 5px 0;
}
</style>