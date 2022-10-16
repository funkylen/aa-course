<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case CHIEF = 'chief';
    case DEV = 'dev';
    case MANAGER = 'manager';
}
