<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TO_DO = 'to-do';
    case COMPLETED = 'completed';
}
