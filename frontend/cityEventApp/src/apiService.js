//Imports
import {ref} from "vue"
// Variable to temporarily store fetched data
let data
const err = ref('')
/**
 * Fetches product data from the API.
 * @returns {Promise<Object[]>} - A promise that resolves to an array of product objects.
 * @throws Will throw an error if the network response is not ok or if there is a fetch error.
 */
export const fetchData = async () => {
  try {
    // Send a request to the API to fetch products
    const response = await fetch(`/events`)

    // Check if the response is OK
    if (!response.ok) {
      err.value('Network did not respond')// Throw an error if the response is not ok
      console.error('Network Error', err)
      return
    }

    // Parse the JSON from the response
    data = await response.json()
    // Return the fetched data
    return data
  } catch (error) {
    // console.log('Error fetching data:', error)
    // Global Error Handle to be displayed if fetch fails
    console.error(' Failed to fetch data', error)
  }
}