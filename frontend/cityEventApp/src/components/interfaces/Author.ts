export interface Author
{
    id: number
    name: string
}
// 200-299 Range of ids to avoid conflicts

const options: Author[] = [
    {id: 200, name:'Local Organization'},
    {id: 201, name:'Non Profit'},
    {id: 202, name: 'Corporation'},
    {id: 203, name: 'Community Group'},
];

export function getAuthor(id: number): Author | undefined{
    return options.find(author=>author.id === id)
}
export const getAllAuthors = () => options

export type AuthorType = Author['name'];
