export interface Format
{
    id: number
    name: string
}
// 600-699 Range of ids to avoid conflicts
const options: Format[] =[
    {id: 600, name:'In-Person'},
    {id: 601, name:'Virtual/Online/Remote'},
    {id: 602, name:'Hybrid' },
];
export function getFormat(id: number): Format | undefined{
    return options.find(format=>format.id === id)
}

export const getAllFormats = () => options

export type FormatType = Format['name'];