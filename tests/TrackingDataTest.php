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

afterEach(function () {
    unset($_GET['utm_source'], $_GET['ref'], $_GET['referer'], $_GET['referrer'], $_SERVER['HTTP_REFERER']);
});

it('reads the source from utm_source and ref, but never from referer/referrer query params', function () {
    $_GET['referer'] = 'newsletter';
    $_GET['referrer'] = 'https://news.ycombinator.com';

    // referer/referrer are no longer source aliases, so the source stays empty.
    expect(TrackingData::fromGlobals()->source)->toBeNull();

    $_GET['ref'] = 'newsletter';
    expect(TrackingData::fromGlobals()->source)->toBe('newsletter');

    $_GET['utm_source'] = 'google';
    expect(TrackingData::fromGlobals()->source)->toBe('google');
});

it('extracts the referer host from the HTTP referer header', function () {
    $_SERVER['HTTP_REFERER'] = 'https://www.news.ycombinator.com/item?id=1';

    expect(TrackingData::fromGlobals()->referer)->toBe('news.ycombinator.com');
});

it('excludes the exact own domain as a self-referral', function () {
    $_SERVER['HTTP_REFERER'] = 'https://www.my-app.test/dashboard';

    expect(TrackingData::fromGlobals('https://my-app.test')->referer)->toBeNull();
});

it('keeps a subdomain of the own domain as a referer (separate property)', function () {
    $_SERVER['HTTP_REFERER'] = 'https://account.my-app.test/login';

    expect(TrackingData::fromGlobals('https://my-app.test')->referer)->toBe('account.my-app.test');
});

it('does not treat an unrelated host that merely shares a substring as the own domain', function () {
    // app.test is a substring of your-app.test but a different host.
    $_SERVER['HTTP_REFERER'] = 'https://app.test';

    expect(TrackingData::fromGlobals('https://your-app.test')->referer)->toBe('app.test');
});
