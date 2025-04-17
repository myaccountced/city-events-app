export interface Cost
{
    id: number
    name: string
}
// 400-499 Range of ids to avoid conflicts
const options: Cost[] = [
    {id: 400, name: 'Free Events'},
    {id: 401, name: 'Paid Events'},
    {id: 402, name: 'VIP/Exclusive Events'},
];
export function getCost(id:number): Cost | undefined{
    return options.find(cost=>cost.id === id)
}
export const getAllCosts = () => options

export type CostType = Cost['name'];
