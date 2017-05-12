<?php

namespace NotificationChannels\PagerDuty;

use Illuminate\Support\Arr;

class PagerDutyMessage
{
    const EVENT_TRIGGER = 'trigger';
    const EVENT_RESOLVE = 'resolve';

    protected $payload = [];
    protected $meta = [];

    public static function create()
    {
        return new static();
    }

    public function __construct()
    {
        Arr::set($this->meta, 'event_action', self::EVENT_TRIGGER);

        Arr::set($this->payload, 'source', gethostname());
        Arr::set($this->payload, 'severity', 'critical');
    }

    public function setRoutingKey($value)
    {
        return $this->setMeta('routing_key', $value);
    }

    public function resolve()
    {
        return $this->setMeta('event_action', self::EVENT_RESOLVE);
    }

    public function setDedupKey($key)
    {
        return $this->setMeta('dedup_key', $key);
    }

    public function setSummary($value)
    {
        return $this->setPayload('summary', $value);
    }

    public function setSource($value)
    {
        return $this->setPayload('source', $value);
    }

    public function setSeverity($value)
    {
        return $this->setPayload('severity', $value);
    }

    public function setTimestamp($value)
    {
        return $this->setPayload('timestamp', $value);
    }

    public function setComponent($value)
    {
        return $this->setPayload('component', $value);
    }

    public function setGroup($value)
    {
        return $this->setPayload('group', $value);
    }

    public function setClass($value)
    {
        return $this->setPayload('class', $value);
    }

    public function addCustomDetail($key, $value)
    {
        return $this->setPayload("custom_details.$key", $value);
    }

    protected function setPayload($key, $value)
    {
        Arr::set($this->payload, $key, $value);

        return $this;
    }

    protected function setMeta($key, $value)
    {
        Arr::set($this->meta, $key, $value);

        return $this;
    }

    public function toArray()
    {
        return Arr::collapse([$this->meta, ['payload' => $this->payload]]);
    }
}
