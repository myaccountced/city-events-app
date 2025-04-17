<script setup lang="ts">
import useVuelidate from '@vuelidate/core'; // Vuelidate's core validation func, bind the form data to validation rule
import {required, minLength, email, maxLength, helpers} from "@vuelidate/validators";
import { computed, reactive, ref } from 'vue'
import axios from "axios";
import { useAuth } from '@/useAuth';
import {useRouter} from "vue-router";
import SubscriptionPlan from "@/components/SubscriptionPlan.vue";
import AuthView from "@/components/AuthView.vue";
import Button from 'primevue/button';
import Card from 'primevue/card';

const { login } = useAuth(); // Get the login method from useAuth

const router = useRouter();
const form = reactive( // and initialize fields
  {
    username: "",
    email: "",
    password: "",
    retypePassword: "",
    isSubmitted: false,
  });
const errMessage = reactive({
  password: '',
  retypePassword: ''
})
const successMessage = reactive({show:''});
const triggerSubmit = ref(false);

// Custom validator for password matching
const matchPassword = helpers.withMessage(
  "Passwords do not match",
  (value) => value === form.password
);

const rules = { // rules used for validation
  username: {required, minLength: minLength(5), maxLength: maxLength(25)},
  email: {required, email},
  password: {required, minLength: minLength(8), maxLength: maxLength(20)},
  retypePassword: {required, matchPassword},
};
const vuelidate = useVuelidate(rules, form);

const loginAfterRegister = async () => {
  try {
    const response = await fetch(import.meta.env.VITE_AUTH_SIGNIN + '/auth/signin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        identifier: form.username, // Pass username as 'identifier' to match backend logic
        password: form.password,
      }),
    });

    const text = await response.text(); // Get the raw response text

    if (response.ok) {
      const data = JSON.parse(text);

          const token = data.token;
          await login(form.username, token, data.userId); // Store the token and username in the application
          await router.push('/');
    }
  } catch (error) {
    console.log(error);
  }
}
const handleSubmission = async () => {
  vuelidate.value.$touch();
  errMessage.username = '';
  errMessage.email = '';
  errMessage.password = '';
  errMessage.retypePassword = '';
  if (vuelidate.value.$invalid) {
    // Extract violation and customise errors messages
    vuelidate.value.$errors.forEach(err => {
      switch (err.$validator) {
        case 'required':
          errMessage[err.$property] = err.$property + ' is required';
          break;
        case 'minLength':
          errMessage[err.$property] = err.$property + ' must be at least ' + rules[err.$property].minLength.$params.min + ' characters';
          break;
        case 'maxLength':
          errMessage[err.$property] = err.$property + ' cannot be longer than ' + rules[err.$property].maxLength.$params.max + ' characters';
          break;
        case 'email':
          errMessage[err.$property] = 'invalid email address';
          break;
        case 'matchPassword':
          errMessage[err.$property] = 'Passwords do not match';
          break;
        default:
          errMessage[err.$property] = 'invalid data input';
          break;
      }
    })
    console.log("Form submitted:", errMessage);
  } else {
    successMessage.show = '';
    // If no validation errors, send data to the backend
    try {
      const response = await axios.post(import.meta.env.VITE_AUTH_SIGNIN + '/api/registration', { // post request to backend api
        username: form.username,
        email: form.email,
        password: form.password
      }, {
        baseURL: import.meta.env.VITE_BACKEND_URL,
      });
      //console.log(response);
      if (response.status === 201) {
        // Trigger the subscription POST request by changing the triggerSubmit value
        form.isSubmitted = true;
        // Force sign-in
        await loginAfterRegister();
        //successMessage.show = response.data.message;
      } else {
        console.log('Unexpected error occurred. Please try again.');
      }
    } catch (error) {
      console.log(error)
      if (!error.response) {
        alert('Server is not available at the moment! \n Please try again later ...')
      } else {
        errMessage.username = error.response.data.errors.username ?? '';
        errMessage.email = error.response.data.errors.email ?? '';
        errMessage.password = error.response.data.errors.password ?? '';
      }
      //console.log('response error: ', errMessage);
    }
  }
};

</script>

<template>
  <div class="p-4 flex align-items-center" id="sign-up-page">
    <div class="flex align-items-center gap-3 mb-4">
      <h1 class="text-3xl">Sign Up</h1>
    </div>
    <div class="intro-text">
      <h4>Welcome to Motodo!</h4>
      <hr />
      <p>
        Discover the vibrant happenings in your city with our comprehensive event listings!
        Whether you’re looking for concerts, festivals, art exhibitions, or local community gatherings,
        we’ve got you covered. Explore a diverse range of activities that cater to all interests and ages.
        Join us in celebrating the unique culture and spirit of our city—your next adventure awaits!
      </p>
      <p>
        Start exploring and find your next favorite event today!
      </p>
    </div>

    <Card>
      <template #content>
        <form id="registration-form" @submit.prevent="handleSubmission" v-if="!successMessage.show">
          <div class=" mb-3">
            <label for="username" style="font-weight: bold; margin-bottom: 3px">Username:</label>
            <input type="text"
                   v-model="form.username"
                   :class="{ 'is-invalid': vuelidate.username.$error }"
                   class="form-control" />
            <span v-if="errMessage.username" class="error">{{ errMessage.username }}</span>
          </div>
          <div class=" mb-3">
            <label for="email" style="font-weight: bold; margin-bottom: 3px">Email:</label>
            <input type="email"
                   v-model="form.email"
                   :class="{ 'is-invalid': vuelidate.email.$error }"
                   class="form-control"
                   placeholder="example@example.com"/>
            <span v-if="errMessage.email" class="error">{{ errMessage.email }}</span>
          </div>
          <div class="mb-3">
            <label for="password" style="font-weight: bold; margin-bottom: 3px">Password:</label>
            <input type="password"
                   v-model="form.password"
                   :class="{ 'is-invalid': vuelidate.password.$error }"
                   class="form-control" />
            <span v-if="errMessage.password" class="error">{{ errMessage.password }}</span>
          </div>

          <div class="mb-3">
            <label for="retype-password" style="font-weight: bold; margin-bottom: 3px">Retype Password:</label>
            <input type="password"
                   v-model="form.retypePassword"
                   :class="{ 'is-invalid': vuelidate.retypePassword.$error }"
                   class="form-control"
                   placeholder="Please retype your password"/>
            <span v-if="errMessage.retypePassword" class="error">{{ errMessage.retypePassword }}</span>
          </div>

          <div class="submit-area">
            <Button type="submit" id="submitButton">Create Account</Button>
            <span id="or-text">or</span>
            <AuthView/>
          </div>

          <!-- Subscription Plan Component -->
          <span id="profile-subscription">
            <SubscriptionPlan
              :triggerSubmit="form.isSubmitted"
              :current-username="form.username"
              @reset:triggerSubmit="form.isSubmitted = false"
            />
          </span>

        </form>
      </template>
    </Card>

    <!-- Success message -->
    <div v-if="successMessage.show" class="successMS">
      <img src="../assets/icon/registration-success-icon.png" height="200" width="200"/>
      <h1>Succesful!</h1>
      <span>{{ successMessage.show }}</span>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 600px) {
  h1 {
    font-size: 2rem;
    text-align: center;
  }

  h4 {
    text-align: center;
  }

  .intro-text {
    width: 100%;
  }

  #submitButton {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    margin: 0 auto;
  }

  .submit-area {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
    margin-bottom: 10px;
  }
}

@media (min-width: 601px) {
  .intro-text {
    width: 60%;
  }

  #profile-subscription {
    display: flex;
    flex-direction: column;
    border: 2px solid #ccc; /* Add a solid border */
    border-radius: 8px; /* Add rounded corners */
    margin: 20px 100px; /* Add space around the fieldset */
    padding: 20px;
    animation: backgroundColorAnimation 2s infinite;
    align-items: center;
    justify-content: start;
  }

  #sign-up-page {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    margin: 0 auto;
  }

  #submitButton {
    width: 160px;
    height: 38px;
    border-radius: 4px;
  }

  .submit-area {
    display: flex;
    justify-content: center;
    margin: 0 auto;
  }

  #or-text {
    color: gray;
    margin-top: 8px;
    margin-right: 10px;
    margin-left: 10px;
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
</style>
