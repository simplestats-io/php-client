<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use SimpleStatsIo\PhpClient\Exceptions\ApiRequestFailed;
use SimpleStatsIo\PhpClient\SimplestatsClient;
use SimpleStatsIo\PhpClient\TrackingData;

function createClient(array $responses, array &$history = []): SimplestatsClient
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new SimplestatsClient([
        'api_token' => 'test-token',
        'handler' => $stack,
    ]);
}

function lastRequestPayload(array $history): array
{
    $request = end($history)['request'];

    return json_decode($request->getBody()->getContents(), true);
}

function lastRequestUri(array $history): string
{
    return end($history)['request']->getUri()->getPath();
}

/**
 * trackVisitor
 */
it('sends trackVisitor to stats-visitor endpoint', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{"status":"ok"}')], $history);

    $time = new DateTimeImmutable('2024-06-15 10:30:00', new DateTimeZone('UTC'));
    $result = $client->trackVisitor('abc123hash', time: $time);

    expect($result)->toBe(['status' => 'ok'])
        ->and(lastRequestUri($history))->toEndWith('/stats-visitor');

    $payload = lastRequestPayload($history);
    expect($payload['visitor_hash'])->toBe('abc123hash')
        ->and($payload['time'])->toBe('2024-06-15 10:30:00 +00:00');
});

it('includes TrackingData in trackVisitor payload', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $tracking = new TrackingData(
        ip: '1.2.3.4',
        userAgent: 'Mozilla/5.0',
        source: 'google',
        medium: 'cpc',
    );
    $time = new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC'));

    $client->trackVisitor('hash123', $tracking, $time);

    $payload = lastRequestPayload($history);
    expect($payload['ip'])->toBe('1.2.3.4')
        ->and($payload['user_agent'])->toBe('Mozilla/5.0')
        ->and($payload['track_source'])->toBe('google')
        ->and($payload['track_medium'])->toBe('cpc');
});

/**
 * trackUser
 */
it('sends trackUser to stats-user endpoint', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $registeredAt = new DateTimeImmutable('2024-03-01 14:00:00', new DateTimeZone('UTC'));
    $client->trackUser(42, $registeredAt, addLogin: true);

    expect(lastRequestUri($history))->toEndWith('/stats-user');

    $payload = lastRequestPayload($history);
    expect($payload['id'])->toBe(42)
        ->and($payload['add_login'])->toBeTrue()
        ->and($payload['time'])->toBe('2024-03-01 14:00:00 +00:00');
});

it('includes TrackingData in trackUser payload', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $tracking = new TrackingData(source: 'newsletter', campaign: 'launch');
    $registeredAt = new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC'));

    $client->trackUser(1, $registeredAt, $tracking);

    $payload = lastRequestPayload($history);
    expect($payload['track_source'])->toBe('newsletter')
        ->and($payload['track_campaign'])->toBe('launch');
});

/**
 * trackLogin
 */
it('sends trackLogin to stats-login endpoint', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $registeredAt = new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC'));
    $loginTime = new DateTimeImmutable('2024-06-15 08:00:00', new DateTimeZone('UTC'));

    $client->trackLogin(99, $registeredAt, '5.6.7.8', 'Chrome/120', $loginTime);

    expect(lastRequestUri($history))->toEndWith('/stats-login');

    $payload = lastRequestPayload($history);
    expect($payload['stats_user_id'])->toBe(99)
        ->and($payload['stats_user_time'])->toBe('2024-01-01 00:00:00 +00:00')
        ->and($payload['ip'])->toBe('5.6.7.8')
        ->and($payload['user_agent'])->toBe('Chrome/120')
        ->and($payload['time'])->toBe('2024-06-15 08:00:00 +00:00');
});

/**
 * trackPayment
 */
it('sends trackPayment with user association', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $paymentTime = new DateTimeImmutable('2024-06-15 12:00:00', new DateTimeZone('UTC'));
    $userRegisteredAt = new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC'));

    $client->trackPayment(
        id: 'pay_123',
        gross: 2000,
        net: 1800,
        currency: 'EUR',
        time: $paymentTime,
        userId: 42,
        userRegisteredAt: $userRegisteredAt
    );

    expect(lastRequestUri($history))->toEndWith('/stats-payment');

    $payload = lastRequestPayload($history);
    expect($payload['id'])->toBe('pay_123')
        ->and($payload['gross'])->toBe(2000)
        ->and($payload['net'])->toBe(1800)
        ->and($payload['currency'])->toBe('EUR')
        ->and($payload['stats_user_id'])->toBe(42)
        ->and($payload['stats_user_time'])->toBe('2024-01-01 00:00:00 +00:00')
        ->and($payload)->not->toHaveKey('visitor_hash');
});

it('sends trackPayment with visitor hash', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $paymentTime = new DateTimeImmutable('2024-06-15 12:00:00', new DateTimeZone('UTC'));

    $client->trackPayment(
        id: 'pay_456',
        gross: 1000,
        net: 900,
        currency: 'USD',
        time: $paymentTime,
        visitorHash: 'visitor-hash-abc'
    );

    $payload = lastRequestPayload($history);
    expect($payload['visitor_hash'])->toBe('visitor-hash-abc')
        ->and($payload)->not->toHaveKey('stats_user_id');
});

/**
 * trackCustomEvent
 */
it('sends trackCustomEvent with user association', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $time = new DateTimeImmutable('2024-06-15 10:00:00', new DateTimeZone('UTC'));
    $registeredAt = new DateTimeImmutable('2024-01-01 00:00:00', new DateTimeZone('UTC'));

    $client->trackCustomEvent('evt-1', 'Button Clicked', $time, userId: 42, userRegisteredAt: $registeredAt);

    expect(lastRequestUri($history))->toEndWith('/stats-custom-event');

    $payload = lastRequestPayload($history);
    expect($payload['id'])->toBe('evt-1')
        ->and($payload['name'])->toBe('Button Clicked')
        ->and($payload['stats_user_id'])->toBe(42)
        ->and($payload['stats_user_time'])->toBe('2024-01-01 00:00:00 +00:00')
        ->and($payload)->not->toHaveKey('visitor_hash');
});

it('sends trackCustomEvent with visitor hash', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $time = new DateTimeImmutable('2024-06-15 10:00:00', new DateTimeZone('UTC'));

    $client->trackCustomEvent('evt-2', 'Page Viewed', $time, visitorHash: 'visitor-abc');

    $payload = lastRequestPayload($history);
    expect($payload['visitor_hash'])->toBe('visitor-abc')
        ->and($payload)->not->toHaveKey('stats_user_id');
});

/**
 * Disabled tracking
 */
it('returns empty array for all methods when disabled', function () {
    $client = new SimplestatsClient([
        'api_token' => 'test-token',
        'enabled' => false,
    ]);

    $time = new DateTimeImmutable();

    expect($client->trackVisitor('hash'))->toBe([])
        ->and($client->trackUser(1, $time))->toBe([])
        ->and($client->trackLogin(1, $time))->toBe([])
        ->and($client->trackPayment(1, 100, 90, 'USD', $time))->toBe([])
        ->and($client->trackCustomEvent('e1', 'test'))->toBe([]);
});

it('reports isEnabled correctly', function () {
    $enabled = new SimplestatsClient(['api_token' => 'test', 'enabled' => true, 'handler' => HandlerStack::create(new MockHandler([]))]);
    $disabled = new SimplestatsClient(['api_token' => 'test', 'enabled' => false]);

    expect($enabled->isEnabled())->toBeTrue()
        ->and($disabled->isEnabled())->toBeFalse();
});

/**
 * Error handling
 */
it('throws ApiRequestFailed on error response', function () {
    $client = createClient([
        new Response(422, [], json_encode(['message' => 'Validation failed'])),
    ]);

    $time = new DateTimeImmutable();
    $client->trackVisitor('hash', time: $time);
})->throws(ApiRequestFailed::class, 'Reason: Validation failed');

it('includes status code in ApiRequestFailed exception', function () {
    $client = createClient([
        new Response(403, [], json_encode(['message' => 'Forbidden'])),
    ]);

    try {
        $client->trackVisitor('hash', time: new DateTimeImmutable());
        $this->fail('Expected ApiRequestFailed');
    } catch (ApiRequestFailed $e) {
        expect($e->statusCode)->toBe(403)
            ->and($e->responseBody)->toContain('Forbidden');
    }
});

/**
 * Time formatting
 */
it('converts non-UTC times to UTC', function () {
    $time = new DateTimeImmutable('2024-06-15 15:00:00', new DateTimeZone('America/New_York'));

    $formatted = SimplestatsClient::formatTime($time);

    expect($formatted)->toBe('2024-06-15 19:00:00 +00:00');
});

it('handles mutable DateTime without modifying original', function () {
    $time = new DateTime('2024-06-15 10:00:00', new DateTimeZone('Europe/Berlin'));
    $originalTz = $time->getTimezone()->getName();

    SimplestatsClient::formatTime($time);

    expect($time->getTimezone()->getName())->toBe($originalTz);
});

/**
 * Authorization header
 */
it('sends bearer token in authorization header', function () {
    $history = [];
    $client = createClient([new Response(200, [], '{}')], $history);

    $client->trackVisitor('hash', time: new DateTimeImmutable());

    $authHeader = end($history)['request']->getHeader('Authorization')[0];
    expect($authHeader)->toBe('Bearer test-token');
});
