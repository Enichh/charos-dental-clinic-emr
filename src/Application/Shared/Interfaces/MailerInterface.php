<?php

namespace CharosEMR\Application\Shared\Interfaces;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body): bool;
}
