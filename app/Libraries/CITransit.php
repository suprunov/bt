<?php

namespace App\Libraries;

/**
 * Provides data transit from ci4 to ci1
 */
class CITransit
{
    public array $events = [];

    public function setFlashEvent($order, $event): void
    {
        session()->setFlashdata('events', (session()->getFlashdata('events') ?? []) + [$order => $event]);
    }
}