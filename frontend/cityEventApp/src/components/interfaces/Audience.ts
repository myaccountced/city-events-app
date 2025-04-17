export interface Audience
{
    id: number
    name: string
}
// 100-199 Range of ids to avoid conflicts

const options: Audience[] = [
    {id: 100, name: 'Family Friendly'},
    {id: 101, name: 'Adult Only'},
    {id: 102, name: 'Youth'},
    {id: 103, name: 'Teens and Up'},
    {id: 104, name: 'General'},
];
 export function getAudience(id: number): Audience | undefined{
     return options.find(audience=>audience.id === id)
 }

export const getAllAudiences = () => options

 export type AudienceType = Audience['name'];