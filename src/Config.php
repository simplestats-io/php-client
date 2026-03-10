<?php

namespace SimpleStatsIo\PhpClient;

use GuzzleHttp\HandlerStack;
use SimpleStatsIo\PhpClient\Exceptions\ConfigurationException;

final class Config
{
    public readonly string $apiUrl;

    public readonly string $apiToken;

    public readonly int $timeout;

    public readonly int $maxRetries;

    public readonly bool $enabled;

    public readonly ?HandlerStack $handler;

    /**
     * @param  array{
     *     api_token: string,
     *     api_url?: string,
     *     timeout?: int,
     *     max_retries?: int,
     *     enabled?: bool,
     *     handler?: HandlerStack
     * }  $config
     */
    public function __construct(array $config)
    {
        if (empty($config['api_token'])) {
            throw new ConfigurationException('The "api_token" configuration is required.');
        }

        $this->apiToken = $config['api_token'];
        $this->apiUrl = rtrim($config['api_url'] ?? 'https://simplestats.io/api/v1/', '/') . '/';
        $this->timeout = $config['timeout'] ?? 5;
        $this->maxRetries = $config['max_retries'] ?? 3;
        $this->enabled = $config['enabled'] ?? true;
        $this->handler = $config['handler'] ?? null;
    }
}
