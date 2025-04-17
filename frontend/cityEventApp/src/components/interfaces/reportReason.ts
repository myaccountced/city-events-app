export interface ReportReason {
    id: number;
    reason: string;
}

// Example data
export const reportReasons: ReportReason[] = [
    { id: 1, reason: 'Spam' },
    { id: 2, reason: 'Inappropriate Content' },
    { id: 3, reason: 'Harassment or abuse' },
    { id: 4, reason: 'False Information' },
    { id: 5, reason: 'Illegal activity' },
    { id: 6, reason: 'Misleading location or time' },
    { id: 7, reason: 'Other' },
];