<?php

namespace NotificationChannels\PagerDuty\Test;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Notification;
use NotificationChannels\PagerDuty\PagerDutyChannel;
use NotificationChannels\PagerDuty\PagerDutyMessage;

class ChannelTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $response = new Response(200);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->with('https://events.pagerduty.com/v2/enqueue',
                [
                    'body' => '{"event_action":"trigger","routing_key":"eventSource01","payload":{"source":"testSource","severity":"critical"}}',
                ])
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    /** @test */
    public function it_skips_when_not_routable()
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('post');
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiableUnRoutable(), new TestNotification());
    }

    /**
     * @test
     * @expectedException \NotificationChannels\PagerDuty\Exceptions\ApiError
     * @expectedExceptionMessage PagerDuty returned 400 Bad Request: Event object is invalid - Length of 'routing_key' is incorrect (should be 32 characters)
     */
    public function it_throws_an_exception_when_400_bad_request()
    {
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

    /**
     * @test
     * @expectedException \NotificationChannels\PagerDuty\Exceptions\ApiError
     * @expectedExceptionMessage PagerDuty returned 400 Bad Request:  -
     */
    public function it_throws_the_expected_exception_if_error_response_doesnt_contain_expected_body_on_error()
    {
        $response = new Response(400, [], '', '1.1', 'Bad Request');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    /**
     * @test
     * @expectedException \NotificationChannels\PagerDuty\Exceptions\ApiError
     * @expectedExceptionMessage PagerDuty returned 429 Too Many Requests
     */
    public function it_throws_exception_on_rate_limit()
    {
        $response = new Response(429, [], '', '1.1', 'Too Many Requests');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    /**
     * @test
     * @expectedException \NotificationChannels\PagerDuty\Exceptions\ApiError
     * @expectedExceptionMessage PagerDuty responded with an unexpected HTTP Status: 503
     */
    public function it_throws_exception_on_unexpected_code()
    {
        $response = new Response(503, [], '', '1.1', 'Service Unavailable');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('post')
            ->once()
            ->andReturn($response);
        $channel = new PagerDutyChannel($client);
        $channel->send(new TestNotifiable(), new TestNotification());
    }

    /**
     * @test
     * @expectedException \NotificationChannels\PagerDuty\Exceptions\CouldNotSendNotification
     * @expectedExceptionMessage Cannot send message to PagerDuty: Test Exception
     */
    public function it_rethrows_exception_on_client_exception()
    {
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
