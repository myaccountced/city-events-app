export interface Accessibility {
  id: number
  name:string
}
// 1-99 Range of ids to avoid conflicts
const options: Accessibility[] = [
  {id: 1, name:'Wheelchair Accessible'},
  {id: 2, name: 'Childcare Available'},
  {id: 3, name:'Language Options'},
];

export function getAccessible(id: number): Accessibility | undefined{
    return options.find(accessible=>accessible.id === id)
}

export const getAllAccessiblities = () => options

export type AccessibleType = Accessibility['name'];
