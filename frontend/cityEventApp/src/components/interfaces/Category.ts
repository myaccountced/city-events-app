export interface Category
{
    id: number
    name: string
    color: string
    icon: string
    bgColorTesting: string
}
// 300-399 Range of ids to avoid conflicts

const options: Category[] = [
    { id: 300, name: 'Arts and Culture', color: 'var(--p-red-400)', icon: 'pi pi-palette',bgColorTesting: 'rgb(248, 113, 113)' }, // Palette icon
    { id: 301, name: 'Education', color: 'var(--p-blue-400)', icon: 'pi pi-graduation-cap', bgColorTesting: 'rgb(96, 165, 250)' }, // Book icon
    { id: 302, name: 'Health and Wellness', color: 'var(--p-green-400)', icon: 'pi pi-heart', bgColorTesting: 'rgb(74, 222, 128)' }, // Heart icon
    { id: 303, name: 'Food and Drink', color: 'var(--p-orange-400)', icon: 'pi pi-shopping-cart', bgColorTesting: 'rgb(251, 146, 60)' }, // Utensils icon
    { id: 304, name: 'Music', color: 'var(--p-purple-400)', icon: 'pi pi-headphones', bgColorTesting: 'rgb(192, 132, 252)' }, // Music icon
    { id: 305, name: 'Nature and Outdoors', color: 'var(--p-teal-400)', icon: 'pi pi-sun', bgColorTesting: 'rgb(45, 212, 191)' }, // Tree icon
    { id: 306, name: 'Sports', color: 'var(--p-yellow-400)', icon: 'pi pi-trophy', bgColorTesting: 'rgb(250, 204, 21)' }, // Football icon
    { id: 307, name: 'Technology', color: 'var(--p-cyan-400)', icon: 'pi pi-desktop', bgColorTesting: 'rgb(34, 211, 238)' }, // Desktop icon
    { id: 308, name: 'Others', color: 'var(--p-gray-400)', icon: 'pi pi-question-circle', bgColorTesting: 'rgb(156, 163, 175)' }, // Question mark icon
];

export function getCategory(id: number): Category | undefined {
    return options.find(category => category.id === id)
}
export function getCategoryByName(name: string): Category | undefined {
    return options.find(category => category.name.toLowerCase() === name.toLowerCase());
}
export const getAllCategories = () => options;
export const getAllCategoryNames = () => {
    return options.map(category => category.name);
}

export type CategoryType = Category['name'];
