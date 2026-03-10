<?php

namespace SimpleStatsIo\PhpClient\Exceptions;

class ApiRequestFailed extends SimplestatsException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody = ''
    ) {
        parent::__construct($message, $statusCode);
    }
}
