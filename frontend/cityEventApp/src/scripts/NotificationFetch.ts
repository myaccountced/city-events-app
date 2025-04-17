import { ref } from 'vue';
import {useAuth} from "@/useAuth";

const backendUrl = import.meta.env.VITE_BACKEND_URL;
const {userId, token} = useAuth();
interface Notification {
    id: number,
    message: string,
    createdAt: Date
}


export default function useNotificationFetch() {

    // This variables is to store notification messages of logged-in user
    const unreadNotifications = ref<Notification[]>([]);
    const fetchUnreadNotification = async () => {
        try {
            const response = await fetch( backendUrl + `/api/notifications/${userId.value}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`
                }
            });
            if (response.ok) {
                const data = await response.json();
                unreadNotifications.value = data.notifications;
            } else {
                console.error("Failed to fetch notification");
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    const markAsRead = async (id:Number) => {
        try {
            const response = await fetch( backendUrl + `/api/notifications/${id}/read`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`
                }
            });
            if (response.ok) {
                unreadNotifications.value = unreadNotifications.value.filter(n => n.id !== id);
            } else {
                console.error("Failed to update unread notification");
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    return {
        unreadNotifications,
        fetchUnreadNotification,
        markAsRead
    }
}