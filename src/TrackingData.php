<?php

namespace SimpleStatsIo\PhpClient;

final class TrackingData
{
    public function __construct(
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $referer = null,
        public readonly ?string $source = null,
        public readonly ?string $medium = null,
        public readonly ?string $campaign = null,
        public readonly ?string $term = null,
        public readonly ?string $content = null,
        public readonly ?string $pageEntry = null,
    ) {}

    /**
     * Build tracking data from PHP superglobals ($_SERVER, $_GET).
     * Extracts UTM parameters, IP, user agent, referer, and page entry automatically.
     *
     * @param  string|null  $appUrl  Your application URL (to exclude self-referrals)
     */
    public static function fromGlobals(?string $appUrl = null): self
    {
        $ip = self::resolveIp();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? urlencode($_SERVER['HTTP_USER_AGENT']) : null;
        $referer = self::resolveReferer($appUrl);
        $pageEntry = $_SERVER['REQUEST_URI'] ?? null;

        return new self(
            ip: $ip,
            userAgent: $userAgent,
            referer: $referer,
            source: self::getTrackingParam(['utm_source', 'ref', 'referer', 'referrer']),
            medium: self::getTrackingParam(['utm_medium', 'adGroup', 'adGroupId']),
            campaign: self::getTrackingParam(['utm_campaign']),
            term: self::getTrackingParam(['utm_term']),
            content: self::getTrackingParam(['utm_content']),
            pageEntry: ! empty($pageEntry) ? strtok($pageEntry, '?') : null,
        );
    }

    /**
     * Convert to API payload array with the correct field names.
     *
     * @return array<string, string|null>
     */
    public function toPayloadArray(): array
    {
        return [
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'track_referer' => $this->referer,
            'track_source' => $this->source,
            'track_medium' => $this->medium,
            'track_campaign' => $this->campaign,
            'track_term' => $this->term,
            'track_content' => $this->content,
            'page_entry' => $this->pageEntry,
        ];
    }

    /**
     * @param  string[]  $paramNames
     */
    private static function getTrackingParam(array $paramNames): ?string
    {
        foreach ($paramNames as $param) {
            if (! empty($_GET[$param])) {
                return $_GET[$param];
            }
        }

        return null;
    }

    private static function resolveIp(): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            $candidate = trim(explode(',', $_SERVER[$header])[0]);

            if (self::isPublicIp($candidate)) {
                return $candidate;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private static function resolveReferer(?string $appUrl = null): ?string
    {
        if (empty($_SERVER['HTTP_REFERER'])) {
            return null;
        }

        $parsed = parse_url($_SERVER['HTTP_REFERER']);
        $host = $parsed['host'] ?? null;

        if ($host === null) {
            $parsed = parse_url('https://' . $_SERVER['HTTP_REFERER']);
            $host = $parsed['host'] ?? null;
        }

        if ($host === null) {
            return null;
        }

        $referer = preg_replace('/^www\./', '', $host);

        if ($appUrl !== null && str_contains($appUrl, $referer)) {
            return null;
        }

        return $referer;
    }
}
