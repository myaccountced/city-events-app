export interface SubscriptionPlan {
    id: number;
    name: string;
    description: string;
    price: number;
    isPopular: boolean;
}

// Example data
export const plans :SubscriptionPlan[] = [
    {
        id: 1,
        name: "1 MONTH",
        description: "just pay for what you use",
        price: 10,
        isPopular: false,
    },
    {
        id: 2,
        name: "1 YEAR",
        description: "the more you use, the cheaper",
        price: 8,
        isPopular: true,
    },
];