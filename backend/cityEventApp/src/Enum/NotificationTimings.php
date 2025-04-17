<?php

namespace App\Enum;

enum NotificationTimings: string
{
    case DAY0_BEFORE = 'day0';
    case DAY1_BEFORE = 'day1';
    case DAY7_BEFORE = 'day7';
}