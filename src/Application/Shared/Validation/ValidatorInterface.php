<?php

namespace CharosEMR\Application\Shared\Validation;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): ValidationResult;
}
