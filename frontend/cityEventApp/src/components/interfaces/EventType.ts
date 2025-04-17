export interface EventType
{
 id: number
 name: string
}
// 500-599 Range of ids to avoid conflicts
const options: EventType[] = [
    {id: 500, name: 'Art Exhibition'},
    {id: 501, name: 'Concert'},
    {id: 502, name: 'Conference'},
    {id: 503, name: 'Community Event'},
    {id: 504, name: 'Festival' },
    {id: 505, name: 'Workshop'},
    {id: 506, name: 'Sports Event'},
];
export function getEventType(id:number): EventType | undefined{
    return options.find(eventType=>eventType.id === id)
}

export const getAllEventTypes = () => options

export type EventTypes= EventType['name']