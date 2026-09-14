<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;

/**
 * Guardrail for audit finding S9.
 *
 * scripts/i18n-audit.php checks that lang/en and lang/fr agree — it cannot see
 * a string that never entered the lang tree. The document assemblers and the
 * survey templates shipped English prose to patients for exactly that reason,
 * passing every gate. This test scans those classes token by token and fails
 * on any string literal that looks like prose (20+ characters with a space)
 * unless it is the argument of __() / trans() / trans_choice().
 *
 * What it deliberately ignores, and why:
 *   - comments and docblocks (not strings);
 *   - strings under 20 characters or without a space — column names, enum
 *     values, 'H:i' formats, units like ' mmHg' are not prose;
 *   - the first argument of ->isoFormat() / ->format() (date patterns);
 *   - anything on a line that logs (Log::) — developer-facing, PHI-free.
 *
 * It is a tripwire, not a proof: a short literal like 'Pending' slips past the
 * length rule. The render tests in tests/Feature/Documents and
 * tests/Feature/Patient cover those by asserting the French output.
 */
class DocumentAssemblersHaveNoHardcodedProseTest extends TestCase
{
    private const FILES = [
        'app/Services/Documents/DocumentDataAssembler.php',
        'app/Services/Documents/DocumentOnDemandAssembler.php',
        'app/Services/Patient/SurveyService.php',
    ];

    private const MIN_LENGTH = 20;

    private const TRANSLATORS = ['__', 'trans', 'trans_choice', 'isoFormat', 'format'];

    public function test_no_prose_literal_bypasses_the_translator(): void
    {
        $offenders = [];

        foreach (self::FILES as $relative) {
            $path = base_path($relative);
            $this->assertFileExists($path);

            $tokens = token_get_all((string) file_get_contents($path));
            $lines  = explode("\n", (string) file_get_contents($path));

            foreach ($tokens as $i => $token) {
                if (! is_array($token)) {
                    continue;
                }

                [$id, $text, $line] = $token;

                if ($id === T_CONSTANT_ENCAPSED_STRING) {
                    $value = substr($text, 1, -1);
                } elseif ($id === T_ENCAPSED_AND_WHITESPACE) {
                    // A fragment of an interpolated "..." string.
                    $value = $text;
                } else {
                    continue;
                }

                if (mb_strlen($value) < self::MIN_LENGTH || ! str_contains($value, ' ')) {
                    continue;
                }

                if ($this->isTranslatorArgument($tokens, $i)) {
                    continue;
                }

                $source = $lines[$line - 1] ?? '';
                if (str_contains($source, 'Log::') || str_contains($source, '__(')) {
                    continue;
                }

                $offenders[] = sprintf('%s:%d  %s', $relative, $line, trim($text));
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Prose string literals outside __() — move them to lang/{en,fr} (documents: document.php, surveys: surveys.php):\n"
            . implode("\n", $offenders)
        );
    }

    /**
     * True when the string at $index is the first argument of __(), trans(),
     * trans_choice(), ->isoFormat() or ->format(): walk back over whitespace
     * to an opening parenthesis, then to the function name before it.
     */
    private function isTranslatorArgument(array $tokens, int $index): bool
    {
        $j = $index - 1;
        while ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j--;
        }
        if ($j < 0 || $tokens[$j] !== '(') {
            return false;
        }

        $j--;
        while ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j--;
        }

        return $j >= 0
            && is_array($tokens[$j])
            && $tokens[$j][0] === T_STRING
            && in_array($tokens[$j][1], self::TRANSLATORS, true);
    }
}
