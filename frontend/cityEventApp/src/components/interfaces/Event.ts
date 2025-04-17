// Event Interface
export interface Event {
    id: number;
    userId: number;
    title: string;
    description: string;
    category: string;
    audience: string;
    location: string;
    startDate: string;
    endDate: string;
    startTime: string;
    creator: string;
    links: string;
    images: string;
    expanded?: boolean;
}

// 100-199 Range of ids to avoid conflicts
const events: Event[] = [
    {
        id: 1001,
        userId: 1,
        title: 'Art Exhibition',
        description: 'Art.',
        category: 'Arts and Culture',
        audience: 'Public',
        location: 'City Art Gallery',
        startDate: '2025-12-01',
        endDate: '2025-12-10',
        startTime: '10:00 AM',
        creator: 'username1',
        links: 'https://example.com',
        images: 'image.jpg',
        expanded: false,
    },
    {
        id: 1021,
        userId: 2,
        title: 'Tech Conference',
        description: 'Technology.',
        category: 'Technology',
        audience: 'Professionals',
        location: 'Convention Center',
        startDate: '2025-11-15',
        endDate: '2025-11-17',
        startTime: '9:00 AM',
        creator: 'Jane Smith',
        links: 'https://techconference.com',
        images: 'image.jpg',
        expanded: false,
    },
    // Add more events as needed
];

// Get an event by its ID
export function getEvent(id: number): Event | undefined {
    return events.find((event) => event.id === id);
}

// Get all events
export const getAllEvents = (): Event[] => events;

// Type for the event title
export type EventTitle = Event['title'];