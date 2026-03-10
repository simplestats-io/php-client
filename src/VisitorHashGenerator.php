<?php

namespace SimpleStatsIo\PhpClient;

final class VisitorHashGenerator
{
    /**
     * Generate a GDPR-compliant visitor hash.
     *
     * Creates a SHA-256 hash from IP + User-Agent + date + secret key,
     * truncated to 32 characters. The daily date rotation ensures
     * visitor hashes are not persistent across days.
     */
    public static function generate(
        string $ip,
        string $userAgent,
        string $secretKey,
        ?string $date = null
    ): string {
        $date = $date ?? date('Y-m-d');
        $hash = hash('sha256', $ip.$userAgent.$date.$secretKey);

        return substr($hash, 0, 32);
    }
}
