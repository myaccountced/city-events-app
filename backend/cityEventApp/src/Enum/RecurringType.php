<?php

namespace App\Enum;

enum RecurringType: string
{
    // Enumeration for recurring type
    case WEEKLY = 'WEEKLY';
    case BI_WEEKLY = 'BI-WEEKLY';
    case MONTHLY = 'MONTHLY';
}
