<?php

namespace CharosEMR\Application\Shared\Events;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
    public function addListener(string $eventName, callable $listener): void;
}
