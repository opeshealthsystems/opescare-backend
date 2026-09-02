<?php
/**
 * OpesCare i18n Parity Audit
 * Usage: php scripts/i18n-audit.php
 *
 * Checks every lang/en/*.php against lang/fr/*.php on three axes:
 *
 *   1. KEY PARITY   - the two trees hold exactly the same dot-notation keys.
 *   2. ENCODING     - each file is valid UTF-8 and free of double-encoded UTF-8.
 *   3. PLACEHOLDERS - a translation uses the same :placeholders as its source,
 *                     so no message silently drops a variable.
 *
 * Exits non-zero on any failure.
 *
 * Why 2 and 3 exist: on 2026-09-02 lang/fr/api.php was found to be double
 * encoded in 625 places - French readers were served 'ParamAtre A< ... A>'
 * where 'Parametre << ... >>' belonged. This audit had passed every single
 * time, because it only ever compared keys. A message can be present, counted,
 * and still be garbage; and ':count appointments' translated without its
 * :count is a bug no key comparison can see.
 */

$enDir = __DIR__ . '/../lang/en';
$frDir = __DIR__ . '/../lang/fr';

$parityErrors      = 0;
$encodingErrors    = 0;
$placeholderErrors = 0;
$totalKeys         = 0;

/** Flatten nested array to dot-notation keys. */
$flat = function (array $arr, string $prefix = '') use (&$flat): array {
    $result = [];
    foreach ($arr as $k => $v) {
        $key = $prefix ? "$prefix.$k" : (string) $k;
        if (is_array($v)) {
            $result = array_merge($result, $flat($v, $key));
        } else {
            $result[$key] = $v;
        }
    }

    return $result;
};

/**
 * Find double-encoded UTF-8 in a string.
 *
 * Mojibake is text whose UTF-8 bytes were once read as CP1252 and re-encoded,
 * so one character (2 bytes) became two characters (4 bytes). Detection is the
 * inverse: a run of 2-4 characters that encodes cleanly back to CP1252 and then
 * decodes as UTF-8 to exactly one character was mojibake.
 *
 * The test is self-guarding, which is what makes it safe on a mixed file: a
 * correctly encoded accented character is ONE codepoint whose single CP1252
 * byte is not valid UTF-8 on its own, so it can never match. iconv() without
 * //TRANSLIT or //IGNORE returns false rather than substituting, so an
 * unmappable character cannot produce a false positive either.
 */
$mojibake = function (string $s): array {
    if ($s === '' || ! preg_match('/[^\x00-\x7F]/', $s)) {
        return [];
    }

    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
        return [];
    }

    $n    = count($chars);
    $hits = [];

    for ($i = 0; $i < $n;) {
        if (mb_ord($chars[$i], 'UTF-8') < 0x80) {
            $i++;
            continue;
        }

        $matched = false;
        for ($len = 4; $len >= 2; $len--) {
            if ($i + $len > $n) {
                continue;
            }

            $chunk = implode('', array_slice($chars, $i, $len));
            $bytes = @iconv('UTF-8', 'CP1252', $chunk);

            if ($bytes === false || ! mb_check_encoding($bytes, 'UTF-8')) {
                continue;
            }

            if (mb_strlen($bytes, 'UTF-8') === 1 && mb_ord($bytes, 'UTF-8') >= 0xA0) {
                $hits[]  = $chunk . ' -> ' . $bytes;
                $i      += $len;
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            $i++;
        }
    }

    return $hits;
};

/** Extract :placeholder tokens, ignoring '::' and time-like '12:30'. */
$placeholders = function ($v): array {
    if (! is_string($v)) {
        return [];
    }

    preg_match_all('/(?<![:A-Za-z0-9]):([A-Za-z_][A-Za-z0-9_]*)/', $v, $m);
    $found = array_map('strtolower', $m[1]);
    $found = array_values(array_unique($found));
    sort($found);

    return $found;
};

/** Report invalid UTF-8 / mojibake for one file on disk. Returns 1 if bad. */
$checkEncoding = function (string $path, array $flatValues) use ($mojibake): int {
    $name = basename(dirname($path)) . '/' . basename($path);
    $raw  = (string) file_get_contents($path);

    if (! mb_check_encoding($raw, 'UTF-8')) {
        echo "\n";
        echo "x NOT VALID UTF-8 : $name\n";

        return 1;
    }

    $bad = 0;
    foreach ($flatValues as $key => $value) {
        if (! is_string($value)) {
            continue;
        }

        $hits = $mojibake($value);
        if (! $hits) {
            continue;
        }

        if ($bad === 0) {
            echo "\n";
            echo "x DOUBLE-ENCODED  : $name\n";
        }
        if ($bad < 5) {
            echo '    ' . $key . ' : ' . implode(', ', array_slice($hits, 0, 4)) . "\n";
        }
        $bad++;
    }

    if ($bad > 5) {
        echo '    ... and ' . ($bad - 5) . " more string(s) in this file\n";
    }

    return $bad > 0 ? 1 : 0;
};

$enFiles = glob($enDir . '/*.php');
sort($enFiles);

foreach ($enFiles as $enFile) {
    $name   = basename($enFile);
    $frFile = $frDir . '/' . $name;

    if (! file_exists($frFile)) {
        echo "x MISSING FR FILE : $name\n";
        $parityErrors++;
        continue;
    }

    $en = require $enFile;
    $fr = require $frFile;

    if (! is_array($en) || ! is_array($fr)) {
        echo "x NOT AN ARRAY    : $name\n";
        $parityErrors++;
        continue;
    }

    $enFlat = $flat($en);
    $frFlat = $flat($fr);

    $missingFr = array_diff_key($enFlat, $frFlat);
    $missingEn = array_diff_key($frFlat, $enFlat);

    if ($missingFr || $missingEn) {
        echo "\n";
        echo 'x PARITY MISMATCH : ' . $name . ' (' . count($enFlat) . ' EN / ' . count($frFlat) . " FR)\n";
        foreach ($missingFr as $k => $_) {
            echo "    MISSING IN FR  : $k\n";
        }
        foreach ($missingEn as $k => $_) {
            echo "    MISSING IN EN  : $k\n";
        }
        $parityErrors++;
        continue;
    }

    $count      = count($enFlat);
    $totalKeys += $count;

    $fileBad          = $checkEncoding($enFile, $enFlat) + $checkEncoding($frFile, $frFlat);
    $encodingErrors  += $fileBad;

    // A translation must carry the same variables as its source, or the
    // rendered French message loses the number, name or date it was about.
    $drift = [];
    foreach ($enFlat as $k => $v) {
        $a = $placeholders($v);
        $b = $placeholders($frFlat[$k]);
        if ($a !== $b) {
            $drift[$k] = [$a, $b];
        }
    }

    if ($drift) {
        echo "\n";
        echo "x PLACEHOLDER DRIFT : $name\n";
        $shown = 0;
        foreach ($drift as $k => $pair) {
            if ($shown++ < 8) {
                echo '    ' . $k . ' : EN {' . implode(',', $pair[0]) . '} vs FR {' . implode(',', $pair[1]) . "}\n";
            }
        }
        if (count($drift) > 8) {
            echo '    ... and ' . (count($drift) - 8) . " more\n";
        }
        $placeholderErrors++;
        continue;
    }

    if ($fileBad === 0) {
        echo "OK                : $name ($count keys)\n";
    }
}

// Check FR files without EN counterpart
foreach (glob($frDir . '/*.php') as $frFile) {
    $name = basename($frFile);
    if (! file_exists($enDir . '/' . $name)) {
        echo "x MISSING EN FILE : $name\n";
        $parityErrors++;
    }
}

$errors = $parityErrors + $encodingErrors + $placeholderErrors;

echo "\n";
echo str_repeat('-', 50) . "\n";
echo 'Total files : ' . count($enFiles) . "\n";
echo 'Total keys  : ' . $totalKeys . "\n";
echo 'Parity      : ' . ($parityErrors ? "FAIL - $parityErrors file(s)" : 'OK - 1:1') . "\n";
echo 'Encoding    : ' . ($encodingErrors ? "FAIL - $encodingErrors file(s)" : 'OK - clean UTF-8') . "\n";
echo 'Placeholders: ' . ($placeholderErrors ? "FAIL - $placeholderErrors file(s)" : 'OK - consistent') . "\n";

if ($errors === 0) {
    echo "Result      : ALL FILES PASS\n";
} else {
    echo "Result      : $errors CHECK(S) FAILED\n";
}

exit($errors > 0 ? 1 : 0);
