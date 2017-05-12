<?php

namespace NotificationChannels\PagerDuty\Exceptions;

class CouldNotSendNotification extends \Exception
{
    public static function create(\Exception $e)
    {
        return new static("Cannot send message to PagerDuty: {$e->getMessage()}", 0, $e);
    }
}
