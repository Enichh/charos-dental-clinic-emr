<?php

namespace CharosEMR\Domain\User\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DENTIST = 'dentist';
    case PATIENT = 'patient';
}
