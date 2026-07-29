<?php

namespace App\Services;

use App\Exceptions\InvalidUrlException;

/**
 * Responsible for validating and normalizing long URLs before they are shortened.
 *
 * Normalization produces a canonical form of the URL so that two logically
 * identical URLs (e.g., with different tracking parameters or casing differences
 * in the host) map to the same short code, enabling proper deduplication.
 *
 * ## Normalization pipeline (in order):
 *   1. Add `https://` scheme if no scheme is present.
 *   2. Lowercase the host component.
 *   3. Remove default ports (80 for http, 443 for https).
 *   4. Strip well-known tracking query parameters (utm_*, fbclid, gclid, etc.).
 *   5. Sort remaining query parameters alphabetically for a canonical query string.
 *   6. Remove a trailing slash from the path (unless the path is just `/`).
 *
 * ## Validation rules:
 *   - Scheme must be `http` or `https`.
 *   - Host must be present and non-empty.
 *   - Host must not resolve to a loopback or private-network address (SSRF prevention).
 *   - Host must not match the application's own domain (redirect loop prevention).
 */
class UrlNormalizerService
{
    /**
     * Query parameter names that are stripped during normalization.
     * These are common analytics tracking markers that do not affect destination content.
     *
     * @var list<string>
     */
    private const STRIP_PARAMS = [
        'fbclid',
        'gclid',
        'gclsrc',
        'mc_eid',
        'mc_cid',
        '_ga',
        'ref',
        'source',
        'trk',
        'twclid',
        'igshid',
        'msclkid',
        'dclid',
    ];

    /**
     * CIDR ranges considered private / loopback — blocked for SSRF prevention.
     * We match using string prefix checks and inet_pton for simplicity.
     *
     * @var list<array{cidr: string, prefix: int}>
     */
    private const PRIVATE_RANGES = [
        ['cidr' => '127.0.0.0',   'prefix' => 8],    // loopback
        ['cidr' => '10.0.0.0',    'prefix' => 8],    // RFC 1918
        ['cidr' => '172.16.0.0',  'prefix' => 12],   // RFC 1918
        ['cidr' => '192.168.0.0', 'prefix' => 16],   // RFC 1918
        ['cidr' => '169.254.0.0', 'prefix' => 16],   // link-local
        ['cidr' => '::1',         'prefix' => 128],   // IPv6 loopback
        ['cidr' => 'fc00::',      'prefix' => 7],     // IPv6 ULA
    ];

    /**
     * Validate and normalize the given URL string.
     *
     * This method first validates the URL then applies the normalization pipeline.
     * If the URL is invalid, an {@see InvalidUrlException} is thrown.
     *
     * @param  string  $url  The raw URL submitted by the user.
     * @return string The canonical, normalized URL ready for storage.
     *
     * @throws InvalidUrlException If the URL is structurally invalid, uses a
     *                             disallowed scheme, targets a private/loopback
     *                             host, or points back at this application.
     */
    public function normalize(string $url): string
    {
        $url = trim($url);

        // Step 1: inject scheme if completely missing
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }

        // Validate before continuing normalization
        $this->validate($url);

        $parts = parse_url($url);

        // Step 2: lowercase host
        $host = strtolower($parts['host']);

        // Step 3: remove default ports
        $port = $parts['port'] ?? null;
        $scheme = strtolower($parts['scheme']);
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        // Step 4 & 5: strip tracking params, sort remaining
        $query = '';
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);

            foreach (self::STRIP_PARAMS as $stripKey) {
                unset($params[$stripKey]);
            }

            if (! empty($params)) {
                ksort($params);
                $query = '?'.http_build_query($params);
            }
        }

        // Step 6: remove trailing slash (unless root path)
        $path = $parts['path'] ?? '/';
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Reassemble
        $normalized = $scheme.'://'.$host;
        if ($port !== null) {
            $normalized .= ':'.$port;
        }
        $normalized .= $path.$query;

        if (! empty($parts['fragment'])) {
            $normalized .= '#'.$parts['fragment'];
        }

        return $normalized;
    }

    /**
     * Validate the URL without applying normalization.
     *
     * Throws {@see InvalidUrlException} with a human-readable message on failure.
     *
     * @param  string  $url  The URL string to validate (must already have a scheme).
     *
     * @throws InvalidUrlException
     */
    public function validate(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            throw new InvalidUrlException('The URL could not be parsed. Please provide a valid URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidUrlException("URL scheme \"{$scheme}\" is not allowed. Only http and https URLs are accepted.");
        }

        $host = strtolower($parts['host']);

        // Validate that the host contains only valid hostname characters.
        // This catches strings like "not-a-url!!!" that parse_url extracts as a "host"
        // but are clearly not valid DNS names or IP addresses.
        if (! preg_match('/^[a-z0-9\-\.]+$/i', $host)) {
            throw new InvalidUrlException('The URL contains an invalid hostname. Please provide a valid URL.');
        }

        // Block loopback hostnames
        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            throw new InvalidUrlException('URLs pointing to localhost are not allowed.');
        }

        // Block the app's own domain (anti-redirect-loop)
        $appHost = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');
        if ($appHost !== '' && $host === $appHost) {
            throw new InvalidUrlException('Shortening a URL that points to this application is not allowed.');
        }

        // Block private/reserved IP ranges (SSRF prevention).
        // gethostbyname() returns the original hostname string unchanged on failure,
        // so we only apply the IP check when resolution actually produced an IP address.
        $resolved = gethostbyname($host);
        if ($resolved !== $host && $this->isPrivateOrReservedIp($resolved)) {
            throw new InvalidUrlException('URLs pointing to private or reserved IP addresses are not allowed.');
        }
    }

    /**
     * Determine whether the given IP address falls within a private or reserved range.
     *
     * @param  string  $ip  An IPv4 or IPv6 address string.
     * @return bool True if the IP is private/loopback/reserved.
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            // Unresolvable — treat as private to be safe
            return true;
        }

        $isIpv6 = str_contains($ip, ':');

        foreach (self::PRIVATE_RANGES as $range) {
            $rangeIpv6 = str_contains($range['cidr'], ':');
            if ($isIpv6 !== $rangeIpv6) {
                continue;
            }

            $packedCidr = inet_pton($range['cidr']);
            if ($packedCidr === false) {
                continue;
            }

            $bits = $range['prefix'];
            $fullBytes = (int) floor($bits / 8);

            // Compare full bytes
            if ($fullBytes > 0 && substr($packed, 0, $fullBytes) !== substr($packedCidr, 0, $fullBytes)) {
                continue;
            }

            // Handle the remaining partial byte
            $remainingBits = $bits % 8;
            if ($remainingBits === 0) {
                return true; // Full bytes matched perfectly
            }

            $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
            if ((ord($packed[$fullBytes]) & $mask) === (ord($packedCidr[$fullBytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
