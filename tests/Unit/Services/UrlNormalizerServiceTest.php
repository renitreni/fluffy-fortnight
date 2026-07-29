<?php

namespace Tests\Unit\Services;

use App\Exceptions\InvalidUrlException;
use App\Services\UrlNormalizerService;
use Tests\TestCase;

/**
 * Unit tests for UrlNormalizerService.
 *
 * Covers the full normalization pipeline and all validation rules.
 * Network resolution is intentionally avoided: tests use publicly
 * routable hostnames (example.com) rather than anything that might
 * hit a real DNS server in CI (gethostbyname still runs but example.com
 * is guaranteed resolvable in all environments; for private/loopback
 * scenarios we use strings that parse_url detects before DNS lookup).
 */
class UrlNormalizerServiceTest extends TestCase
{
    private UrlNormalizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UrlNormalizerService;
    }

    // ── Scheme injection ──────────────────────────────────────────────────

    /**
     * A URL without any scheme should have https:// prepended.
     */
    public function test_missing_scheme_gets_https_injected(): void
    {
        $result = $this->service->normalize('www.example.com/path');
        $this->assertStringStartsWith('https://', $result);
    }

    /**
     * A URL that already has http:// should keep that scheme.
     */
    public function test_existing_http_scheme_is_preserved(): void
    {
        $result = $this->service->normalize('http://example.com/path');
        $this->assertStringStartsWith('http://', $result);
    }

    // ── Host normalization ────────────────────────────────────────────────

    /**
     * The host component should be lowercased.
     */
    public function test_host_is_lowercased(): void
    {
        $result = $this->service->normalize('https://WWW.EXAMPLE.COM/page');
        $this->assertStringContainsString('www.example.com', $result);
        $this->assertStringNotContainsString('WWW.EXAMPLE.COM', $result);
    }

    // ── Default port removal ──────────────────────────────────────────────

    /**
     * Port 80 should be removed from http:// URLs.
     */
    public function test_default_port_80_removed_for_http(): void
    {
        $result = $this->service->normalize('http://example.com:80/page');
        $this->assertStringNotContainsString(':80', $result);
    }

    /**
     * Port 443 should be removed from https:// URLs.
     */
    public function test_default_port_443_removed_for_https(): void
    {
        $result = $this->service->normalize('https://example.com:443/page');
        $this->assertStringNotContainsString(':443', $result);
    }

    /**
     * Non-default ports should be preserved.
     */
    public function test_non_default_port_is_preserved(): void
    {
        $result = $this->service->normalize('https://example.com:8443/page');
        $this->assertStringContainsString(':8443', $result);
    }

    // ── Tracking parameter stripping ──────────────────────────────────────

    /**
     * utm_source, utm_medium, utm_campaign, utm_term, utm_content should be preserved.
     */
    public function test_utm_params_are_preserved(): void
    {
        $url = 'https://example.com/page?utm_campaign=launch&utm_medium=cpc&utm_source=google&keep=yes';
        $result = $this->service->normalize($url);

        $this->assertStringContainsString('utm_source=google', $result);
        $this->assertStringContainsString('utm_medium=cpc', $result);
        $this->assertStringContainsString('utm_campaign=launch', $result);
        $this->assertStringContainsString('keep=yes', $result);
    }

    /**
     * fbclid should be stripped.
     */
    public function test_fbclid_is_stripped(): void
    {
        $url = 'https://example.com/?fbclid=AYo123&page=1';
        $result = $this->service->normalize($url);

        $this->assertStringNotContainsString('fbclid', $result);
        $this->assertStringContainsString('page=1', $result);
    }

    /**
     * gclid should be stripped.
     */
    public function test_gclid_is_stripped(): void
    {
        $result = $this->service->normalize('https://example.com/landing?gclid=Cj0KCQ&ref=ad');
        $this->assertStringNotContainsString('gclid', $result);
        $this->assertStringNotContainsString('ref=', $result); // 'ref' is also stripped
    }

    /**
     * When all query params are tracking params, the resulting URL should have no query string.
     */
    public function test_all_tracking_params_stripped_leaves_no_query_string(): void
    {
        $result = $this->service->normalize('https://example.com/page?fbclid=abc&gclid=123');
        $this->assertStringNotContainsString('?', $result);
    }

    // ── Remaining params are sorted ───────────────────────────────────────

    /**
     * Remaining query parameters should be sorted alphabetically for canonical form.
     */
    public function test_remaining_query_params_are_sorted(): void
    {
        $result = $this->service->normalize('https://example.com/?z=last&a=first&m=mid');
        $this->assertSame('https://example.com/?a=first&m=mid&z=last', $result);
    }

    // ── Trailing slash removal ────────────────────────────────────────────

    /**
     * Trailing slashes on paths other than root should be removed.
     */
    public function test_trailing_slash_is_removed_from_non_root_path(): void
    {
        $result = $this->service->normalize('https://example.com/blog/');
        $this->assertSame('https://example.com/blog', $result);
    }

    /**
     * The root path (/) should remain unchanged.
     */
    public function test_root_path_slash_is_preserved(): void
    {
        $result = $this->service->normalize('https://example.com/');
        $this->assertStringEndsWith('example.com/', $result);
    }

    // ── Validation: loopback ──────────────────────────────────────────────

    /**
     * localhost URLs should be rejected.
     */
    public function test_localhost_url_throws_invalid_url_exception(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->service->validate('http://localhost/admin');
    }

    /**
     * localhost.localdomain should also be rejected.
     */
    public function test_localhost_localdomain_is_rejected(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->service->validate('http://localhost.localdomain/');
    }

    // ── Validation: scheme ────────────────────────────────────────────────

    /**
     * An ftp:// URL should be rejected.
     */
    public function test_ftp_scheme_throws_invalid_url_exception(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->service->validate('ftp://files.example.com/data.zip');
    }

    /**
     * A javascript: URL should be rejected.
     */
    public function test_javascript_scheme_throws_invalid_url_exception(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->service->validate('javascript:alert(1)');
    }

    // ── Validation: malformed URL ─────────────────────────────────────────

    /**
     * A string that is not a URL at all should throw.
     */
    public function test_non_url_string_throws_invalid_url_exception(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->service->validate('not-a-url-at-all');
    }

    // ── Validation: valid pass-through ────────────────────────────────────

    /**
     * A well-formed HTTPS URL should not throw any exception.
     */
    public function test_valid_https_url_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validate('https://www.example.com/article?id=42');
    }

    /**
     * A well-formed HTTP URL should not throw any exception.
     */
    public function test_valid_http_url_does_not_throw(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validate('http://example.com/page');
    }
}
