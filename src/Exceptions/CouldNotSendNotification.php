<?php

namespace NotificationChannels\PagerDuty\Exceptions;

use Exception;

class CouldNotSendNotification extends Exception
{
    public static function create(Exception $e): static
    {
        return new static("Cannot send message to PagerDuty: {$e->getMessage()}", 0, $e);
    }
}
