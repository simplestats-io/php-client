<?php

use SimpleStatsIo\PhpClient\VisitorHashGenerator;

it('generates a 32-character hash', function () {
    $hash = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret-key', '2024-01-15');

    expect($hash)->toHaveLength(32);
});

it('produces deterministic results for the same inputs', function () {
    $args = ['192.168.1.1', 'Chrome/120', 'my-secret', '2024-06-01'];

    $hash1 = VisitorHashGenerator::generate(...$args);
    $hash2 = VisitorHashGenerator::generate(...$args);

    expect($hash1)->toBe($hash2);
});

it('produces different hashes for different dates', function () {
    $hash1 = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret', '2024-01-01');
    $hash2 = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret', '2024-01-02');

    expect($hash1)->not->toBe($hash2);
});

it('produces different hashes for different IPs', function () {
    $hash1 = VisitorHashGenerator::generate('10.0.0.1', 'Mozilla/5.0', 'secret', '2024-01-01');
    $hash2 = VisitorHashGenerator::generate('10.0.0.2', 'Mozilla/5.0', 'secret', '2024-01-01');

    expect($hash1)->not->toBe($hash2);
});

it('produces different hashes for different secret keys', function () {
    $hash1 = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'key-a', '2024-01-01');
    $hash2 = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'key-b', '2024-01-01');

    expect($hash1)->not->toBe($hash2);
});

it('uses today date when date is null', function () {
    $hash = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret');
    $expected = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret', date('Y-m-d'));

    expect($hash)->toBe($expected);
});

it('contains only hexadecimal characters', function () {
    $hash = VisitorHashGenerator::generate('127.0.0.1', 'Mozilla/5.0', 'secret', '2024-01-01');

    expect($hash)->toMatch('/^[a-f0-9]{32}$/');
});
