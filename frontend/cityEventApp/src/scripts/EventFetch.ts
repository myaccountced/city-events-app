import { ref } from 'vue';
import {type Category, getAllCategories} from "@/components/interfaces/Category";
import { useAuth } from '@/useAuth'

const backendUrl = import.meta.env.VITE_BACKEND_URL;
const noMoreEvents = ref(false)
const {user, token} = useAuth();

interface Event {
    id: number;
    eventTitle: string;
    eventDescription: string;
    eventCategory: Category;
    eventAudience: string;
    eventLocation: string;
    eventStartDate: string;
    eventEndDate: string;
    eventCreator: string;
    eventLink: string;
    eventImages: number;
    reports: Report[];
    imagePaths: string[];
    eventRecurringType: string;
}

interface Report {
    reportId: number,
    reportDate: string,
    reportTime: string,
    reason: string
}

export default function useEventFetch() {
    const events = ref<Event[]>([]);
    const userFutureEvents = ref<Event[]>([]);
    const userPastEvents = ref<Event[]>([]);
    const error = ref<string | null>(null);
    const categories = getAllCategories();

    //TODO THIS METHOD WILL ALSO GRAB IMAGES AND BOOKMARKS
    const getEventsWithFilterAndSorter = async (
        limit: number,
        offset: number,
        filters: { fieldName: string, criteria: any [] }[] = [],
        sorter?: { fieldName: string, order: 'ASC' | 'DESC' },
        isHistoric: bool,
        searchString?: string
    ) => {
        try {
            // Construct query parameters
            let queryParams = `limit=${limit}&offset=${offset}`;

            // Add filter criteria to the query string if provided
            if (filters.length > 0) {
                filters.forEach(filter => {
                    filter.criteria.forEach(value => {
                        queryParams += `&filter[${filter.fieldName}][]=${encodeURIComponent(value)}`;
                    });
                });
            }

            // Add sorting parameter if provided
            if (sorter) { queryParams += `&sortField=${sorter.fieldName}&sortOrder=${sorter.order}`; }

            if (isHistoric) { queryParams += '&isHistoric=true' }

            if (searchString && searchString != '') { queryParams += `&searchString=${searchString}`}


            // Make the request to the backend with filters and sorting
            const response = await fetch(`${backendUrl}/eventsWithFilterAndSorter?${queryParams}`);


            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const json = await response.json();
            // console.log("Parsed JSON:", json);
            // Directly assign the fetched events to the events array
            if (Array.isArray(json)) {
                noMoreEvents.value = json.length < limit;

                const HTTPURL = `${backendUrl}/uploads/`
                const mappedEvents = json.map(event => ({
                    ...event,
                    reports: [],
                    imagePaths: Array.isArray(event.images) ? event.images.map(img => HTTPURL + img) : []
                }))

                if (offset != 0)
                {
                    events.value = [...events.value, ...mappedEvents]; // Append new events
                }
                else {
                    events.value = mappedEvents;
                }

                offset += limit; // Update offset for next load

                // Appending ad to end of events
                if (mappedEvents.length > 0) {
                    events.value = [...events.value, { id: 'ad' }]
                }

            } else {
                throw new Error('Expected an array of events');
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An unknown error occurred';
            console.error('Error fetching events:', error.value);
            return [];
        }
    };


    //TODO STORY 60 - THIS WILL ALSO GET MEDIA AND IMAGES - SHOULDN'T HAVE TO CHANGE ANYTHING HERE
    const getBookmarkedEvents = async (limit: number, offset: number) => {

        try {
            const currentUser = useAuth().user.value; // Ensure username is sent
            const response = await fetch(backendUrl + `/events/bookmarks/user?limit=${limit}&offset=${offset}&currentUser=${currentUser}`, {
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                }
            });

            if (!response.status == 200) {
                //console.log(backendUrl + `/bookmarks/user?limit=${limit}&offset=${offset}&currentUser=${useAuth().user.value}`)
                throw new Error('Network response was not ok');
            }

            if(response.status !== 200){
                const errorText = await response.text(); // Read response body
                console.error("Error response body:", errorText);
                throw new Error(`Failed to fetch bookmarks. Status: ${response.status}`);
            }

            const json = await response.json();

            // Directly assign the fetched events to the events array
            const HTTPURL = `${backendUrl}/uploads/`
            if (Array.isArray(json)) {
                noMoreEvents.value = json.length < offset;
                const mappedEvents = json.map(event => ({
                    ...event,
                    //eventCategory: categories.find(category => category.id === event.eventCategory),
                    reports: [],
                    imagePaths: Array.isArray(event.images) ? event.images.map(img => HTTPURL + img) : []
                }))

                console.log("Mapped Events:", mappedEvents);
                events.value = [...events.value, ...mappedEvents]; // Append new events
                offset += limit; // Update offset for next load
            } else {
                throw new Error('Expected an array of events');
            }
            console.log("Response status:", response.status);
            console.log("Response body:", await response.text()); // Read raw response
        } catch (err) {
            //error.value = err instanceof Error ? err.message : 'An unknown error occurred';
            console.error('Error fetching events:', error.value);
            //console.error('Error fetching events:');
            return [];
        }
    };

    //TODO STORY 60 - THIS WILL ALSO GET MEDIA AND IMAGES - SHOULDN'T HAVE TO CHANGE ANYTHING HERE

    const getUsersEvents = async (
        currentUser: string,
    ) => {
        try {
            const response = await fetch(backendUrl + `/myevents?user=${currentUser}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`
                }
            })
            if (!response.ok) {
                throw new Error('Network response was not ok')
            }
            const json = await response.json()

            if (Array.isArray(json[0])) {
                const mappedEvents = json[0].map((event) => ({
                    ...event
                }))
                userFutureEvents.value = [...mappedEvents];
            } else {
                throw new Error('Expected an array of events')
            }

            // do the same for the second array of past events
            if (Array.isArray(json[1])) {
                const mappedEvents = json[1].map((event) => ({
                    ...event
                }))
                userPastEvents.value = [...mappedEvents];
            } else {
                throw new Error('Expected an array of events')
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An unknown error occurred'
            console.error('Error fetching events:', error.value)
            return []
        }
    }

    /**
     * Get the images related to the expanded event
     * @param eventID
     */
        //TODO STORY 60 DELETE THIS
    const getImages = async (eventID : number) => {
            // Find the event in the events array. If it exists and there are no image paths there, insert the image paths.
            const targetEvent = events.value.find(ev => ev.id === eventID);
            if (targetEvent) {
                if (targetEvent.imagePaths.length === 0) {
                    try {
                        const response = await fetch(import.meta.env.VITE_BACKEND_URL + `/events/media?eventID=${eventID}`, {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Error fetching images');
                        }

                        const data = await response.json();
                        for (const imagePath in data.images)
                        {
                            targetEvent.imagePaths.push(backendUrl + '/uploads/' + data.images[imagePath])
                        }
                    } catch (error) {
                        console.error('Error fetching images:', error);
                        return [];
                    }
                } else {
                    console.error('Images for that event was already fetched');
                }
            } else {
                console.error('Event not found for the given eventID');
            }
        }

    return {
        events,
        userFutureEvents,
        userPastEvents,
        error,
        getBookmarkedEvents,
        getEventsWithFilterAndSorter,
        noMoreEvents,
        getImages,
        getUsersEvents
    };
}
