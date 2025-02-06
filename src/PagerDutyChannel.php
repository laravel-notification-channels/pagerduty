<?php

namespace NotificationChannels\PagerDuty;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Notifications\Notification;
use NotificationChannels\PagerDuty\Exceptions\ApiError;
use NotificationChannels\PagerDuty\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

class PagerDutyChannel
{
    public function __construct(protected Client $client)
    {
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     *
     * @throws ApiError|GuzzleException|CouldNotSendNotification
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $routingKey = $notifiable->routeNotificationFor('PagerDuty')) {
            return;
        }

        /** @var PagerDutyMessage $data */
        $data = $notification->toPagerDuty($notifiable);
        $data->setRoutingKey($routingKey);

        try {
            $response = $this->client->post('https://events.pagerduty.com/v2/enqueue', [
                'body' => json_encode($data->toArray()),
            ]);
        } catch (Exception $e) {
            throw CouldNotSendNotification::create($e);
        }

        $this->handleResponse($response);
    }

    /**
     * @param  ResponseInterface  $response
     *
     * @throws ApiError
     */
    public function handleResponse(ResponseInterface $response): void
    {
        switch ($response->getStatusCode()) {
            case 200:
            case 201:
            case 202:
                return;
            case 400:
                throw ApiError::serviceBadRequest($response->getBody());
            case 429:
                throw ApiError::rateLimit();
            default:
                throw ApiError::unknownError($response->getStatusCode());
        }
    }
}
