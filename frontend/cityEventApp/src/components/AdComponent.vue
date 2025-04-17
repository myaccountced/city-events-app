<script setup lang="ts">

import { Panel, Card } from "primevue"
import { onMounted, ref } from "vue";

const props = defineProps<{
  style: string,
  client: any,
  adSlot: any,
  format: any,
  adType: string
}>()

const inProd = ref(import.meta.env.PROD);

/**
 * This function pushes the ad on load to the google ads array, required for using google adSense.
 * It will only run in production.
 */
function loadAd() {
  if (inProd.value) {
    (window.adsbygoogle = window.adsbygoogle || []).push({});
  }
}

onMounted(() => {
  loadAd();
})
</script>

<template>

  <Panel v-if="adType === 'banner-ad'" v-bind:class="adType" >
    <div v-if="inProd" class="adsbygoogle">
      <KeepAlive>
        <ins
            v-bind:style="style"
            :data-ad-client="client"
            :data-ad-slot="slot"
            :data-ad-format="format"
            data-full-width-responsive="true">
        </ins>
      </KeepAlive>
    </div>

    <img v-if="!inProd" alt="Advertisement" src="../assets/testAd.png" >

  </Panel>

  <Card v-if="adType === 'event-ad'" v-bind:class="adType">
    <template #content>
      <div v-if="inProd" class="adsbygoogle">
        <KeepAlive>
          <ins
              v-bind:style="style"
              :data-ad-client="client"
              :data-ad-slot="slot"
              :data-ad-format="format"
              data-full-width-responsive="true">
          </ins>
        </KeepAlive>
      </div>

      <img v-if="!inProd" alt="Advertisement" src="../assets/testAd.png" >
    </template>

  </Card>

</template>

<style scoped>

img, ins {
  display: block;
  max-width: 100%;
}

.event-ad > * {
  margin-left: auto;
}

.event-ad, .banner-ad {
  background-color: var(--p-sky-900);
  overflow: hidden;
}

.banner-ad {
  width: 100%;
  overflow: hidden;
  max-width: 100vw;
  position: fixed;
  bottom: 0;
}

.event-ad {
  min-width: 100%;
  max-width: 100vw;
}

</style>