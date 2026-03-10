<?php

use SimpleStatsIo\PhpClient\TrackingData;

it('sets all constructor properties', function () {
    $data = new TrackingData(
        ip: '1.2.3.4',
        userAgent: 'Mozilla/5.0',
        referer: 'google.com',
        source: 'google',
        medium: 'cpc',
        campaign: 'summer-sale',
        term: 'shoes',
        content: 'banner-ad',
        pageEntry: '/landing',
    );

    expect($data->ip)->toBe('1.2.3.4')
        ->and($data->userAgent)->toBe('Mozilla/5.0')
        ->and($data->referer)->toBe('google.com')
        ->and($data->source)->toBe('google')
        ->and($data->medium)->toBe('cpc')
        ->and($data->campaign)->toBe('summer-sale')
        ->and($data->term)->toBe('shoes')
        ->and($data->content)->toBe('banner-ad')
        ->and($data->pageEntry)->toBe('/landing');
});

it('defaults all properties to null', function () {
    $data = new TrackingData;

    expect($data->ip)->toBeNull()
        ->and($data->userAgent)->toBeNull()
        ->and($data->referer)->toBeNull()
        ->and($data->source)->toBeNull()
        ->and($data->medium)->toBeNull()
        ->and($data->campaign)->toBeNull()
        ->and($data->term)->toBeNull()
        ->and($data->content)->toBeNull()
        ->and($data->pageEntry)->toBeNull();
});

it('maps properties to correct API field names in toPayloadArray', function () {
    $data = new TrackingData(
        ip: '1.2.3.4',
        userAgent: 'Mozilla/5.0',
        referer: 'google.com',
        source: 'google',
        medium: 'cpc',
        campaign: 'summer-sale',
        term: 'shoes',
        content: 'banner-ad',
        pageEntry: '/landing',
    );

    $payload = $data->toPayloadArray();

    expect($payload)->toBe([
        'ip' => '1.2.3.4',
        'user_agent' => 'Mozilla/5.0',
        'track_referer' => 'google.com',
        'track_source' => 'google',
        'track_medium' => 'cpc',
        'track_campaign' => 'summer-sale',
        'track_term' => 'shoes',
        'track_content' => 'banner-ad',
        'page_entry' => '/landing',
    ]);
});

it('includes null values in toPayloadArray for unset properties', function () {
    $data = new TrackingData(source: 'google');

    $payload = $data->toPayloadArray();

    expect($payload)->toHaveKeys([
        'ip', 'user_agent', 'track_referer', 'track_source',
        'track_medium', 'track_campaign', 'track_term', 'track_content', 'page_entry',
    ])
        ->and($payload['track_source'])->toBe('google')
        ->and($payload['ip'])->toBeNull()
        ->and($payload['track_medium'])->toBeNull();
});
