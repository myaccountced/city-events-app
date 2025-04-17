import {ref} from 'vue';

const backendUrl = import.meta.env.VITE_BACKEND_URL;

export default function useEventPost() {
    const uploadEventImages = async (formData: FormData) => {
        try {
            const response = await fetch(backendUrl + `/events`, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Image upload failed');
            }

            return await response.json(); // The server should return the saved image paths or success message
        } catch (error) {
            console.error('Error uploading images:', error);
            throw error;
        }
    }
    return {uploadEventImages};
}