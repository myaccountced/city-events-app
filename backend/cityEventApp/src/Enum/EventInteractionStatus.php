<?php

namespace App\Enum;

enum EventInteractionStatus: string
{
    case NO_INTERACTION = 'no_interaction';
    case INTERESTED = 'interested';
    case ATTENDING = 'attending';
}