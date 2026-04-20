<?php

namespace CharosEMR\Domain\User\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case PATIENT = 'patient';
}
