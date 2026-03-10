<?php

use SimpleStatsIo\PhpClient\Config;
use SimpleStatsIo\PhpClient\Exceptions\ConfigurationException;

it('throws ConfigurationException when api_token is missing', function () {
    new Config([]);
})->throws(ConfigurationException::class, 'The "api_token" configuration is required.');

it('throws ConfigurationException when api_token is empty', function () {
    new Config(['api_token' => '']);
})->throws(ConfigurationException::class);

it('sets default values correctly', function () {
    $config = new Config(['api_token' => 'test-token']);

    expect($config->apiUrl)->toBe('https://simplestats.io/api/v1/')
        ->and($config->apiToken)->toBe('test-token')
        ->and($config->timeout)->toBe(5)
        ->and($config->maxRetries)->toBe(3)
        ->and($config->enabled)->toBeTrue()
        ->and($config->handler)->toBeNull();
});

it('accepts custom config values', function () {
    $config = new Config([
        'api_token' => 'my-token',
        'api_url' => 'https://custom.example.com/api/v2/',
        'timeout' => 10,
        'max_retries' => 5,
        'enabled' => false,
    ]);

    expect($config->apiUrl)->toBe('https://custom.example.com/api/v2/')
        ->and($config->timeout)->toBe(10)
        ->and($config->maxRetries)->toBe(5)
        ->and($config->enabled)->toBeFalse();
});

it('normalizes api_url to have trailing slash', function () {
    $config = new Config([
        'api_token' => 'test-token',
        'api_url' => 'https://example.com/api/v1',
    ]);

    expect($config->apiUrl)->toBe('https://example.com/api/v1/');
});

it('does not double trailing slash', function () {
    $config = new Config([
        'api_token' => 'test-token',
        'api_url' => 'https://example.com/api/v1/',
    ]);

    expect($config->apiUrl)->toBe('https://example.com/api/v1/');
});
