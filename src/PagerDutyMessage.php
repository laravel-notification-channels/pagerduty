<?php

namespace NotificationChannels\PagerDuty;

use Illuminate\Support\Arr;

class PagerDutyMessage
{
    const EVENT_TRIGGER = 'trigger';
    const EVENT_RESOLVE = 'resolve';

    protected array $payload = [];
    protected array $meta = [];

    public static function create(): self
    {
        return new static();
    }

    public function __construct()
    {
        Arr::set($this->meta, 'event_action', self::EVENT_TRIGGER);
        Arr::set($this->payload, 'source', gethostname());
        Arr::set($this->payload, 'severity', 'critical');
    }

    public function setRoutingKey(string $value): self
    {
        return $this->setMeta('routing_key', $value);
    }

    public function resolve(): self
    {
        return $this->setMeta('event_action', self::EVENT_RESOLVE);
    }

    public function setDedupKey(string $key): self
    {
        return $this->setMeta('dedup_key', $key);
    }

    public function setSummary(string $value): self
    {
        return $this->setPayload('summary', $value);
    }

    public function setSource(string $value): self
    {
        return $this->setPayload('source', $value);
    }

    public function setSeverity(string $value): self
    {
        return $this->setPayload('severity', $value);
    }

    public function setTimestamp(string $value): self
    {
        return $this->setPayload('timestamp', $value);
    }

    public function setComponent(string $value): self
    {
        return $this->setPayload('component', $value);
    }

    public function setGroup(string $value): self
    {
        return $this->setPayload('group', $value);
    }

    public function setClass(string $value): self
    {
        return $this->setPayload('class', $value);
    }

    public function addCustomDetail(string $key, string $value): self
    {
        return $this->setPayload("custom_details.$key", $value);
    }

    protected function setPayload(string $key, mixed $value): self
    {
        Arr::set($this->payload, $key, $value);

        return $this;
    }

    protected function setMeta(string $key, mixed $value): self
    {
        Arr::set($this->meta, $key, $value);

        return $this;
    }

    public function toArray(): array
    {
        return Arr::collapse([$this->meta, ['payload' => $this->payload]]);
    }
}
