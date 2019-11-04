<?php

namespace NotificationChannels\PagerDuty\Test;

use NotificationChannels\PagerDuty\PagerDutyMessage;
use PHPUnit\Framework\TestCase;

class PagerDutyMessageTest extends TestCase
{
    /** @test */
    public function basic_message_has_all_values()
    {
        $message = PagerDutyMessage::create()
            ->setRoutingKey('testIntegration01')
            ->setSummary('This is a test message');

        $body = $message->toArray();

        $this->assertArrayHasKey('event_action', $body);
        $this->assertArrayHasKey('routing_key', $body);
        $this->assertArrayHasKey('payload', $body);
        $this->assertArrayHasKey('source', $body['payload']);
        $this->assertArrayHasKey('severity', $body['payload']);
        $this->assertArrayHasKey('summary', $body['payload']);

        $this->assertTrue(is_string($body['payload']['source']));
    }

    /** @test */
    public function test_message_renders()
    {
        $message = PagerDutyMessage::create()
            ->setRoutingKey('testIntegration01')
            ->setSummary('This is a test message')
            ->setSource('testSource');

        $this->assertEquals(
            [
                'event_action' => 'trigger',
                'routing_key' => 'testIntegration01',
                'payload' => [
                    'source' => 'testSource',
                    'severity' => 'critical',
                    'summary' => 'This is a test message',
                ],
            ], $message->toArray()
        );
    }

    /** @test */
    public function test_message_renders_optional_params()
    {
        $message = PagerDutyMessage::create()
            ->setRoutingKey('testIntegration01')
            ->setDedupKey('testMessage01')
            ->setSeverity('error')
            ->setTimestamp('timestamp')
            ->setComponent('nginx')
            ->setGroup('app servers')
            ->setClass('ping failure')
            ->setSummary('This is a test message')
            ->setSource('testSource');

        $this->assertEquals(
            [
                'event_action' => 'trigger',
                'routing_key' => 'testIntegration01',
                'payload' => [
                    'source' => 'testSource',
                    'severity' => 'error',
                    'summary' => 'This is a test message',
                    'timestamp' => 'timestamp',
                    'component' => 'nginx',
                    'group' => 'app servers',
                    'class' => 'ping failure',
                ],
                'dedup_key' => 'testMessage01',
            ], $message->toArray()
        );
    }

    /** @test */
    public function test_message_renders_custom_details()
    {
        $message = PagerDutyMessage::create()
            ->setRoutingKey('testIntegration01')
            ->setSummary('This is a test message')
            ->setSource('testSource')
        ->addCustomDetail('ping time', '1500ms')
        ->addCustomDetail('load avg', '0.75');

        $this->assertEquals(
            [
                'event_action' => 'trigger',
                'routing_key' => 'testIntegration01',
                'payload' => [
                    'source' => 'testSource',
                    'severity' => 'critical',
                    'summary' => 'This is a test message',
                    'custom_details' => [
                        'ping time' => '1500ms',
                        'load avg' => '0.75',
                    ],
                ],
            ], $message->toArray()
        );
    }

    /** @test */
    public function test_message_renders_resolve()
    {
        $message = PagerDutyMessage::create()
            ->setRoutingKey('testIntegration01')
            ->setSource('testSource')
            ->setDedupKey('testMessage01')
            ->resolve();

        $this->assertEquals(
            [
                'event_action' => 'resolve',
                'routing_key' => 'testIntegration01',
                'payload' => [
                    'source' => 'testSource',
                    'severity' => 'critical',
                ],
                'dedup_key' => 'testMessage01',
            ], $message->toArray()
        );
    }
}
