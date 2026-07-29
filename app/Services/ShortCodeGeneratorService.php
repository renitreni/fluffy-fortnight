<?php

namespace App\Services;

use App\Models\Link;

/**
 * Generates and decodes Base62-encoded short codes for URL links.
 *
 * ## Strategy: DB auto-increment + Base62 encoding
 *
 * We use the database-assigned `id` of the newly-created Link row as the seed
 * for short-code generation. Because `id` is an auto-increment primary key, it
 * is globally unique by construction — eliminating any need for collision
 * detection or retry loops.
 *
 * Base62 uses 62 characters (0-9, A-Z, a-z), producing short, URL-safe codes:
 *   - id 1        → "1"      (1 char)
 *   - id 62       → "Z"      (1 char)
 *   - id 238,329  → "ZZZ"    (3 chars)
 *   - id 3.5M     → "4-char" code
 *   - id 56.8B    → "8-char" code
 *
 * ## Generation flow (used by LinkController):
 *   1. Insert a Link record without a short_code to obtain the DB-assigned `id`.
 *   2. Encode the `id` via `encode()`.
 *   3. Update the record's `short_code` column with the encoded value.
 *
 * This two-step approach avoids any race conditions or retry logic because
 * the id is allocated atomically by the database.
 */
class ShortCodeGeneratorService
{
    /**
     * The Base62 character alphabet, ordered: digits → uppercase → lowercase.
     */
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * The base of the encoding (length of ALPHABET).
     */
    private const BASE = 62;

    /**
     * Encode a positive integer into a Base62 string.
     *
     * @param  int  $id  The integer to encode. Must be ≥ 1.
     * @return string The Base62-encoded string (e.g. "aB3x").
     *
     * @throws \InvalidArgumentException If $id is less than 1.
     */
    public function encode(int $id): string
    {
        if ($id < 1) {
            throw new \InvalidArgumentException("ID must be a positive integer, got {$id}.");
        }

        $result = '';
        $n = $id;

        while ($n > 0) {
            $result = self::ALPHABET[$n % self::BASE].$result;
            $n = intdiv($n, self::BASE);
        }

        return $result;
    }

    /**
     * Decode a Base62 string back to its original integer.
     *
     * Useful for debugging and analytics lookups. Unknown characters are silently
     * ignored — callers should validate the code before relying on the result.
     *
     * @param  string  $code  The Base62-encoded short code to decode.
     * @return int The decoded integer ID.
     */
    public function decode(string $code): int
    {
        $result = 0;
        $length = strlen($code);

        for ($i = 0; $i < $length; $i++) {
            $charIndex = strpos(self::ALPHABET, $code[$i]);
            if ($charIndex === false) {
                continue; // Skip unknown characters
            }
            $result = $result * self::BASE + $charIndex;
        }

        return $result;
    }

    /**
     * Generate and persist a short code for a newly-created Link record.
     *
     * This method accepts an unsaved (or just-saved) Link model that already has a
     * database-assigned `id`. It encodes the id, updates the `short_code` column,
     * and returns the generated code.
     *
     * @param  Link  $link  A Link model with a valid `id`.
     * @return string The generated and persisted short code.
     *
     * @throws \InvalidArgumentException If the link has no id (not yet saved).
     */
    public function generateForLink(Link $link): string
    {
        if (! $link->id) {
            throw new \InvalidArgumentException('Link must be persisted (have an id) before a short code can be generated.');
        }

        $shortCode = $this->encode($link->id);
        $link->short_code = $shortCode;
        $link->save();

        return $shortCode;
    }

    /**
     * Return the Base62 alphabet used by this encoder.
     * Exposed for testing and external validation.
     */
    public function getAlphabet(): string
    {
        return self::ALPHABET;
    }
}
