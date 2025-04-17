import {computed, ref} from 'vue';
import { HttpStatusCode } from 'axios'

const isAuthenticated = ref(false);
const user = ref<string | null>(null); // username
const token = ref<string | null>(null); // For storing the JWT token
const userId = ref<number | null>(null); // User ID
const scope = ref<string | null>(null); // For storing the scope
const premiumState = ref<{status: boolean, expireDate: string | null}>({
    status: false,
    expireDate: null,
});
const isMod = ref(false);
const premiumDaysRemaining = computed(() => {
    if (!premiumState.value.expireDate) return 0;
    const expiration = new Date(premiumState.value.expireDate);
    const now = new Date();
    const timeDiff = expiration.getTime() - now.getTime();
    return timeDiff > 0 ? Math.floor(timeDiff / (1000 * 60 * 60 * 24)) : 0; // Convert milliseconds to days
});


const EXPIRATION_HOURS = 5; // 5 hours

const setItemWithExpiration = (key: string, value: string, expirationInHours: number) => {
    const now = new Date();
    const item = {
        value: value,
        expiry: now.getTime() + expirationInHours * 60 * 60 * 1000, // Expiration time in milliseconds
    };
    localStorage.setItem(key, JSON.stringify(item)); // Store in localStorage
};

export const getItemWithExpiration = (key: string): string | null => {
    const itemStr = localStorage.getItem(key);
    if (!itemStr) {
        return null;
    }
    const item = JSON.parse(itemStr);
    const now = new Date();

    if (now.getTime() > item.expiry) {
        localStorage.removeItem(key); // Remove expired item
        return null; // Item is expired, so return null
    }
    return item.value; // Return the value if not expired
};


export const useAuth = () => {
    const login = async (username: string, jwtToken: string, id: number, scopeIn: string) => {
        isAuthenticated.value = true;
        user.value = username; // Store the username
        token.value = jwtToken; // Store the token
        userId.value = id;
        scope.value = scopeIn;
        setItemWithExpiration('username', username, EXPIRATION_HOURS);// Store in localStorage with expiration
        setItemWithExpiration('userid', id.toString(), EXPIRATION_HOURS);
        setItemWithExpiration('scope', scopeIn, EXPIRATION_HOURS); // Store in localStorage with expiration
        if (jwtToken != null) {
            localStorage.setItem(`token${username}`, jwtToken); // Store the token in localStorage
        }
        isMod.value = scope.value === 'moderator'; // Set `isMod` when logging in

        await checkSubscriptionStatus(username);
    };

    const logout = () => {
        isAuthenticated.value = false;
        user.value = null;
        userId.value = null;
        scope.value = null;
        token.value = null; // Clear token
        isMod.value = false;
        localStorage.removeItem('username'); // Clear localStorage on logout
        localStorage.removeItem('userid')
        localStorage.removeItem('scope'); // Clear localStorage on logout
        localStorage.removeItem(`token${user.value}`); // Remove token from localStorage
        localStorage.removeItem('scope'); // Clear localStorage on logout
        setSubscriptionStatus(false, null);
    };

    const loadUserFromLocalStorage = async () => {
        const storedUsername = getItemWithExpiration('username');
        const storedScope = getItemWithExpiration('scope');
        const storedUserId = getItemWithExpiration('userid');

        if (storedUsername) {
            if (storedScope) {
                scope.value = storedScope;
            }
            if (storedUserId) {
                // Convert the stored string back to a number
                userId.value = parseInt(storedUserId, 10);
            }
            const storedToken = localStorage.getItem(`token${storedUsername}`);
            if (storedToken) {
                isAuthenticated.value = true;
                user.value = storedUsername;
                token.value = storedToken; // Load the token

                await checkSubscriptionStatus(storedUsername);

                token.value = storedToken;
                isMod.value = storedScope === 'moderator';
            } else {
                logout();
            }
        } else {
            logout();
        }
    };

    const setSubscriptionStatus = (in_isPremium: boolean, in_expireDate: string | null) => {
        premiumState.value = {status: in_isPremium, expireDate: in_expireDate};
    };

    // Method to fetch premium status from backend
    const checkSubscriptionStatus = async (username: string) => {
        try {
            const response = await fetch(import.meta.env.VITE_AUTH_SIGNIN +`/api/subscription/${username}`, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                },
            });


            if (response.ok) {
                const data = await response.json();
                premiumState.value = {status: data.isPremium, expireDate: data.expireDate};
            } else {
                console.error("Failed to fetch subscription status");
            }
        } catch (error) {
            console.error("Error fetching subscription status:", error);
        }
    };



    return { isAuthenticated, user, token, userId,  scope, premiumState,  premiumDaysRemaining, login, logout,
        loadUserFromLocalStorage, setSubscriptionStatus, checkSubscriptionStatus, isMod };
};
