<template>
  <div class="home">
    <div v-if="username.length">
      <h1>Welcome back {{username}}</h1>
    </div>
    <h1>This is the Home Page</h1>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
let username = ref('');

// Get the username from localStorage
if (localStorage.getItem("username")) {
  const usernameData = JSON.parse(localStorage.getItem("username"));
  username = ref(usernameData?.value);
}


</script>

<style scoped>
.home {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  font-family: 'Arial', sans-serif;
  text-align: center;
}

h1 {
  font-size: 2.5rem;
  margin-bottom: 20px;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

@media (max-width: 600px) {
  h1 {
    font-size: 2rem;
  }
}
</style>
