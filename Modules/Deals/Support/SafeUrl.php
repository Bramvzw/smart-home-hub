<?php

namespace Modules\Deals\Support;

/**
 * URL-scheme guard for untrusted retailer-supplied links and images.
 *
 * Retailer payloads are external input, so a listing url/image_url can carry a
 * dangerous scheme (javascript:, data:, vbscript:, file:). This guard returns
 * the URL only when it resolves to an http(s) link, and null otherwise, so it
 * can never be emitted into an href/src attribute.
 */
final class SafeUrl
{
    /**
     * Return the URL when it is a safe http(s) link, otherwise null.
     */
    public static function http(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    /**
     * Whether the URL is a safe http(s) link.
     */
    public static function isHttp(?string $url): bool
    {
        return self::http($url) !== null;
    }
}
