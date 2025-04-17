import { defineConfig } from "cypress";

export default defineConfig({
  e2e: {
    specPattern: "cypress/e2e/**/*.{cy,spec}.{js,jsx,ts,tsx}",
    baseUrl: "http://localhost:5173",
    mailHogUrl: "http://localhost:8025",
    experimentalRunAllSpecs: true,
    env: {
      API_BASE_URL: "http://127.0.0.1:8001",
      FRONTEND_URL: process.env.FRONTEND_URL || 'localhost:5173'
    }
  },

  component: {
    devServer: {
      framework: "vue",
      bundler: "vite",
    },
  },
});
