<?php

namespace SimpleStatsIo\PhpClient;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use SimpleStatsIo\PhpClient\Exceptions\ApiRequestFailed;

final class SimplestatsClient
{
    public const TIME_FORMAT = 'Y-m-d H:i:s P';

    private Config $config;

    private HttpClient $httpClient;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->httpClient = new HttpClient($this->config);
    }

    /**
     * Track a unique visitor.
     *
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function trackVisitor(
        string $visitorHash,
        ?TrackingData $trackingData = null,
        ?DateTimeInterface $time = null
    ): array {
        if (! $this->config->enabled) {
            return [];
        }

        $payload = [
            'visitor_hash' => $visitorHash,
            'time' => self::formatTime($time ?? $this->now()),
        ];

        if ($trackingData !== null) {
            $payload = array_merge($payload, $trackingData->toPayloadArray());
        }

        return $this->httpClient->post('stats-visitor', $payload);
    }

    /**
     * Track a user registration.
     *
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function trackUser(
        string|int $id,
        DateTimeInterface $registeredAt,
        ?TrackingData $trackingData = null,
        bool $addLogin = false
    ): array {
        if (! $this->config->enabled) {
            return [];
        }

        $payload = [
            'id' => $id,
            'add_login' => $addLogin,
            'time' => self::formatTime($registeredAt),
        ];

        if ($trackingData !== null) {
            $payload = array_merge($payload, $trackingData->toPayloadArray());
        }

        return $this->httpClient->post('stats-user', $payload);
    }

    /**
     * Track a user login event.
     *
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function trackLogin(
        string|int $userId,
        DateTimeInterface $userRegisteredAt,
        ?string $ip = null,
        ?string $userAgent = null,
        ?DateTimeInterface $time = null
    ): array {
        if (! $this->config->enabled) {
            return [];
        }

        $payload = [
            'stats_user_id' => $userId,
            'stats_user_time' => self::formatTime($userRegisteredAt),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'time' => self::formatTime($time ?? $this->now()),
        ];

        return $this->httpClient->post('stats-login', $payload);
    }

    /**
     * Track a payment. Provide either userId + userRegisteredAt or visitorHash.
     *
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function trackPayment(
        string|int $id,
        int $gross,
        int $net,
        string $currency,
        DateTimeInterface $time,
        string|int|null $userId = null,
        ?DateTimeInterface $userRegisteredAt = null,
        ?string $visitorHash = null
    ): array {
        if (! $this->config->enabled) {
            return [];
        }

        $payload = [
            'id' => $id,
            'gross' => $gross,
            'net' => $net,
            'currency' => $currency,
            'time' => self::formatTime($time),
        ];

        if ($userId !== null) {
            $payload['stats_user_id'] = $userId;

            if ($userRegisteredAt !== null) {
                $payload['stats_user_time'] = self::formatTime($userRegisteredAt);
            }
        } elseif ($visitorHash !== null) {
            $payload['visitor_hash'] = $visitorHash;
        }

        return $this->httpClient->post('stats-payment', $payload);
    }

    /**
     * Track a custom event. Provide either userId + userRegisteredAt or visitorHash.
     *
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function trackCustomEvent(
        string $id,
        string $name,
        ?DateTimeInterface $time = null,
        string|int|null $userId = null,
        ?DateTimeInterface $userRegisteredAt = null,
        ?string $visitorHash = null
    ): array {
        if (! $this->config->enabled) {
            return [];
        }

        $payload = [
            'id' => $id,
            'name' => $name,
            'time' => self::formatTime($time ?? $this->now()),
        ];

        if ($userId !== null) {
            $payload['stats_user_id'] = $userId;

            if ($userRegisteredAt !== null) {
                $payload['stats_user_time'] = self::formatTime($userRegisteredAt);
            }
        } elseif ($visitorHash !== null) {
            $payload['visitor_hash'] = $visitorHash;
        }

        return $this->httpClient->post('stats-custom-event', $payload);
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled;
    }

    public static function formatTime(DateTimeInterface $time): string
    {
        if ($time instanceof DateTimeImmutable) {
            return $time->setTimezone(new DateTimeZone('UTC'))->format(self::TIME_FORMAT);
        }

        $clone = clone $time;

        return $clone->setTimezone(new DateTimeZone('UTC'))->format(self::TIME_FORMAT);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
