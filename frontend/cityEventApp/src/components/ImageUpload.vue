<script setup lang="ts">
import { ref, reactive } from 'vue';

interface FileError {
  [key: number]: string;
}

const files = ref<File[]>([]);
const errors = reactive<Record<number, string>>({});
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

const validateFiles = () => {
  // Reset errors
  Object.keys(errors).forEach((key) => delete errors[key]);

  // Check file count
  if (files.value.length > 3) {
    errors[0] = 'You can upload up to 3 images only.';
    return false;
  }

  // Validate each file
  files.value.forEach((file, index) => {
    if (!file.type.startsWith('image/')) {
      errors[index] = 'Invalid file type. Only image files are allowed.';
    } else if (file.size > MAX_FILE_SIZE) {
      errors[index] = `File size must not exceed 5 MB (${file.name}).`;
    }
  });

  // Return validation result
  return Object.keys(errors).length === 0;
};

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  files.value = Array.from(input.files || []);
  validateFiles();
};

// Expose methods to parent component
defineExpose({
  files,
  errors,
  validateFiles
});

</script>

<template>
  <div>
    <h5>Image Upload (Recommended)</h5>
    <p>(Three files max, each up to 5 MB, image only)</p>
    <input
        type="file"
        class="form-control"
        @change="handleFileChange"
        accept="image/*"
        multiple
        data-cy="photo-files"
    />
    <ul v-if="Object.keys(errors).length" class="text-danger" data-cy="error-message">
      <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
    </ul>
  </div>
</template>

<style scoped>
.text-danger {
  color: red;
}
</style>
