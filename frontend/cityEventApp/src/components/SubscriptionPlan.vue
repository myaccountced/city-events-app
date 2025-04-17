<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { plans } from "./interfaces/SubscriptionPlan";
import { useAuth} from "@/useAuth";
import {Card} from "primevue"


// component properties linked with parent
const props = defineProps({
  currentUsername: String,
  triggerSubmit: Boolean, // listen submit event from Parent
});

const {premiumDaysRemaining, setSubscriptionStatus, premiumState } = useAuth();

const emit = defineEmits(["reset:triggerSubmit"]);

// Internal state for the selected plan, because can not modify selectedPlan directly to bind with parent component
const selectedPlan = ref<number>(-1);

// Watch for changes from parent
watch(
    () => props.triggerSubmit,
    async (newVal) => {
      if (newVal) {
        await makePostRequest();
        emit("reset:triggerSubmit");
        selectedPlan.value = -1
      }
    }
);

const makePostRequest = async () => {
  if (selectedPlan.value === -1) {
    console.log("No subscription plan selected.");
    return;
  }
  //console.log('send Subscription data to backend: ', props.currentUsername, selectedPlan.value)
  try {
    const subscriptionResponse = await fetch(import.meta.env.VITE_AUTH_SIGNIN + '/api/subscription', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        username: props.currentUsername, // Pass username as 'identifier' to match backend logic
        selectedPlan: selectedPlan.value,
      }),
    });
    if (subscriptionResponse.ok) {
      const data = await subscriptionResponse.json(); // Parse the JSON response
      const isPremium = data.isPremium; // Extract 'isPremium'
      const expireDate = data.expireDate; // Extract 'dayRemaining'

      setSubscriptionStatus(isPremium, expireDate);

    } else {
      console.log('Subscription backend feedback:', subscriptionResponse);
    }
  } catch (error) {
    console.log('Error creating subscription:', error);
  }

};

// Plan selection logic
const selectPlan = (planId: number) => {
  selectedPlan.value = selectedPlan.value === planId ? -1 : planId; // Toggle selection
}
</script>

<template>
  <Card class="subscription-container">
    <template #title>
      <h3 v-if="!premiumState.status" style="text-align: center">Upgrade to Premium</h3>
      <h3 v-if="premiumState.status" style="text-align: center">Extend Your Premium Status</h3>

    </template>
    <template #subtitle>
      <div style="text-align: center">
        Enjoy an ad-free experience and more!
        <br />
        Choose one:
      </div>
    </template>
    <template #content>
      <Card v-for="plan in plans"
        :id="'card-' + plan.id"
        @click="selectPlan(plan.id)"
        :class="{ selected: selectedPlan === plan.id }">
        <template #title>
          <h1>{{plan.name}}</h1>
        </template>
        <!--        <template #content>{{plan.description}}</template>-->
        <template #subtitle>Price: CA${{plan.price}}/ Month</template>

        <template #footer>
          <div v-if="plan.isPopular" class="badge">Most Popular</div>
        </template>
      </Card>
    </template>

  </Card>
</template>

<style scoped>
.subscription-container {
  display: flex;
  gap: 1rem;
}


.p-card.selected {
  //background-color: greenyellow;
  background-color: var(--p-sky-500);
}

.badge {
  background-color: var(--p-yellow-400);
  color: black;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  margin-top: 0.5rem;
  font-size: 0.8rem;
}
</style>
