<?php

namespace Tests\Unit\Services;

use App\Services\ShortCodeGeneratorService;
use Tests\TestCase;

/**
 * Unit tests for ShortCodeGeneratorService.
 *
 * Verifies:
 *   - encode() produces valid Base62 strings.
 *   - decode() is the exact inverse of encode().
 *   - encode() raises an exception for invalid (non-positive) IDs.
 *   - Code lengths are reasonable across a wide range of IDs.
 *   - All codes for IDs 1–100 are unique.
 */
class ShortCodeGeneratorServiceTest extends TestCase
{
    private ShortCodeGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShortCodeGeneratorService;
    }

    // ── Encoding ──────────────────────────────────────────────────────────

    /**
     * encode(1) should return a single character from the Base62 alphabet.
     */
    public function test_encode_id_1_returns_single_char(): void
    {
        $code = $this->service->encode(1);
        $this->assertSame('1', $code);
    }

    /**
     * encode(62) should return '10' because the alphabet is:
     * 0-9 (indices 0-9), A-Z (indices 10-35), a-z (indices 36-61).
     * 62 = 1*62 + 0, so it encodes as '10'.
     */
    public function test_encode_id_62_returns_correct_base62(): void
    {
        $code = $this->service->encode(62);
        $this->assertSame('10', $code);

        // Also verify the encoding of 10 (which should give 'A', index 10 in the alphabet)
        $this->assertSame('A', $this->service->encode(10));
        // And 36 → 'a' (first lowercase letter, index 36)
        $this->assertSame('a', $this->service->encode(36));
    }

    /**
     * All encoded codes should only contain valid Base62 characters (0-9, A-Z, a-z).
     */
    public function test_encoded_codes_only_contain_base62_chars(): void
    {
        foreach ([1, 10, 61, 62, 63, 100, 1000, 999999, 3521614606207] as $id) {
            $code = $this->service->encode($id);
            $this->assertMatchesRegularExpression(
                '/^[0-9A-Za-z]+$/',
                $code,
                "Code for id={$id} contains non-Base62 characters: {$code}"
            );
        }
    }

    /**
     * Encoding a zero or negative ID should throw an InvalidArgumentException.
     */
    public function test_encode_zero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->encode(0);
    }

    /**
     * Encoding a negative ID should throw an InvalidArgumentException.
     */
    public function test_encode_negative_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->encode(-5);
    }

    // ── Decode ────────────────────────────────────────────────────────────

    /**
     * decode(encode(n)) should round-trip to the original integer.
     */
    public function test_encode_decode_roundtrip(): void
    {
        $ids = [1, 2, 61, 62, 63, 100, 3843, 238327, 14776335, 999999999];

        foreach ($ids as $id) {
            $code = $this->service->encode($id);
            $decoded = $this->service->decode($code);
            $this->assertSame($id, $decoded, "Roundtrip failed for id={$id}, code={$code}");
        }
    }

    // ── Code Lengths ──────────────────────────────────────────────────────

    /**
     * For IDs 1–1,000,000 the encoded code should be no longer than 4 characters
     * (62^4 = 14,776,336 > 1,000,000).
     */
    public function test_ids_up_to_one_million_produce_codes_of_4_chars_or_fewer(): void
    {
        foreach ([1, 100, 10000, 100000, 1000000] as $id) {
            $code = $this->service->encode($id);
            $this->assertLessThanOrEqual(
                4,
                strlen($code),
                "Code for id={$id} has length ".strlen($code)." (expected ≤4): {$code}"
            );
        }
    }

    /**
     * IDs up to 56 billion should produce codes of 8 characters or fewer
     * (62^8 = 218,340,105,584,896).
     */
    public function test_very_large_ids_produce_reasonable_code_lengths(): void
    {
        $largeId = 56_800_235_584; // ~56.8 billion
        $code = $this->service->encode($largeId);
        $this->assertLessThanOrEqual(8, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $code);
    }

    // ── Uniqueness ────────────────────────────────────────────────────────

    /**
     * All codes generated for consecutive IDs 1–100 should be unique.
     */
    public function test_consecutive_ids_produce_unique_codes(): void
    {
        $codes = [];
        for ($id = 1; $id <= 100; $id++) {
            $code = $this->service->encode($id);
            $this->assertArrayNotHasKey(
                $code,
                $codes,
                "Duplicate code '{$code}' produced for id={$id}"
            );
            $codes[$code] = $id;
        }
    }

    // ── Alphabet ──────────────────────────────────────────────────────────

    /**
     * The alphabet should contain exactly 62 unique characters.
     */
    public function test_alphabet_has_62_unique_characters(): void
    {
        $alphabet = $this->service->getAlphabet();
        $this->assertSame(62, strlen($alphabet));
        $this->assertSame(62, count(array_unique(str_split($alphabet))));
    }
}
