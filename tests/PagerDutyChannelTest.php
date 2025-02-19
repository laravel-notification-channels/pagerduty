<?php

namespace NotificationChannels\PagerDuty\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Mockery;
use NotificationChannels\PagerDuty\PagerDutyChannel;
use NotificationChannels\PagerDuty\PagerDutyMessage;
use PHPUnit\Framework\TestCase;

class PagerDutyChannelTest extends TestCase
{
    public function tearDown(): void
    {
        $this->addToAssertionCount(
            \Mockery::getContainer()->mockery_getExpectationCount()
        );
        Mockery::close();
    }

    public function test_it_can_send_a_notification()
    {
        $response = new Response(200);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->with(
                'https://events.pagerduty.com/v2/enqueue',
                [
                    'body' => '{"event_action":"trigger","routing_key":"eventSource01","payload":{"source":"testSource","severity":"critical"}}',
                ]
            )
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    public function test_it_skips_when_not_routable()
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('post');
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiableUnRoutable(), new TestNotification());
    }

    public function test_it_throws_an_exception_when_400_bad_request()
    {
        $this->expectException('\NotificationChannels\PagerDuty\Exceptions\ApiError');
        $this->expectExceptionMessage(
            'PagerDuty returned 400 Bad Request: Event object is invalid - Length of \'routing_key\' is incorrect (should be 32 characters)'
        );

        $responseBody = '{
          "status": "invalid event",
          "message": "Event object is invalid",
          "errors": [
            "Length of \'routing_key\' is incorrect (should be 32 characters)"
          ]
        }';

        $response = new Response(400, [], $responseBody, '1.1', 'Bad Request');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    public function test_it_throws_the_expected_exception_if_error_response_doesnt_contain_expected_body_on_error()
    {
        $this->expectException('\NotificationChannels\PagerDuty\Exceptions\ApiError');
        $this->expectExceptionMessage('PagerDuty returned 400 Bad Request:  -');

        $response = new Response(400, [], '', '1.1', 'Bad Request');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    public function test_it_throws_exception_on_rate_limit()
    {
        $this->expectException('\NotificationChannels\PagerDuty\Exceptions\ApiError');
        $this->expectExceptionMessage('PagerDuty returned 429 Too Many Requests');

        $response = new Response(429, [], '', '1.1', 'Too Many Requests');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    public function test_it_throws_exception_on_unexpected_code()
    {
        $this->expectException('\NotificationChannels\PagerDuty\Exceptions\ApiError');
        $this->expectExceptionMessage('PagerDuty responded with an unexpected HTTP Status: 503');

        $response = new Response(503, [], '', '1.1', 'Service Unavailable');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    public function test_it_rethrows_exception_on_client_exception()
    {
        $this->expectException('\NotificationChannels\PagerDuty\Exceptions\CouldNotSendNotification');
        $this->expectExceptionMessage('Cannot send message to PagerDuty: Test Exception');
        $e = new \Exception('Test Exception');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andThrow($e);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }
}

class TestNotifiable
{
    use \Illuminate\Notifications\Notifiable;

    /**
     * @return int
     */
    public function routeNotificationForPagerDuty()
    {
        return 'eventSource01';
    }
}

class TestNotifiableUnRoutable
{
    use \Illuminate\Notifications\Notifiable;
}

class TestNotification extends Notification
{
    public function toPagerDuty($notifiable)
    {
        return PagerDutyMessage::create()
            ->setSource('testSource');
    }
}
