<?php

namespace SimpleStatsIo\PhpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SimpleStatsIo\PhpClient\Exceptions\ApiRequestFailed;

/** @internal */
final class HttpClient
{
    private Client $client;

    public function __construct(Config $config)
    {
        if ($config->handler !== null) {
            $stack = $config->handler;
        } else {
            $stack = HandlerStack::create();
            $stack->push(Middleware::retry(
                $this->retryDecider($config->maxRetries),
                $this->retryDelay()
            ));
        }

        $this->client = new Client([
            'base_uri' => $config->apiUrl,
            'timeout' => $config->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config->apiToken,
                'X-SimpleStats-Client-Version' => $this->getVersion(),
            ],
            'handler' => $stack,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiRequestFailed
     */
    public function post(string $endpoint, array $payload): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if ($response === null) {
                throw new ApiRequestFailed(
                    'Request failed: ' . $e->getMessage(),
                    0,
                    ''
                );
            }

            $body = $response->getBody()->getContents();
            $json = json_decode($body, true) ?? [];
            $message = $json['message'] ?? 'unknown';

            throw new ApiRequestFailed(
                'Reason: ' . $message,
                $response->getStatusCode(),
                $body
            );
        }
    }

    private function retryDecider(int $maxRetries): callable
    {
        return function (int $retries, Request $request, ?Response $response = null, ?\Throwable $exception = null) use ($maxRetries): bool {
            if ($retries >= $maxRetries) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response !== null) {
                $status = $response->getStatusCode();

                return $status === 429 || $status >= 500;
            }

            return false;
        };
    }

    private function retryDelay(): callable
    {
        return function (int $retries): int {
            return (int) pow(2, $retries) * 1000;
        };
    }

    private function getVersion(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            return \Composer\InstalledVersions::getVersion('simplestats-io/php-client') ?? 'unknown';
        }

        return 'unknown';
    }
}
