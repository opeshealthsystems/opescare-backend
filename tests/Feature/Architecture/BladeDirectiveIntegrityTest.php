<?php

namespace Tests\Feature\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Blade directives that silently do not compile.
 *
 * Laravel's BladeCompiler matches directives with a `\B@` prefix -- a directive
 * preceded by a WORD character is not a directive at all, and is emitted as
 * literal text. That failure is silent and asymmetric, which is what makes it
 * dangerous:
 *
 *     Maceration Present@if($grade) - {{ $grade }}@endif
 *
 * The opening `@if` is glued to the `t` of "Present", so it is left as text.
 * The closing `@endif` is preceded by `}}`, so it IS compiled. The result is a
 * stray `<?php endif; ?>` with nothing to close, and the compiled template is a
 * PHP parse error. `php artisan view:cache` reports success -- it compiles the
 * file, it does not execute it -- so the only symptom is a 500 the first time a
 * human asks for that page.
 *
 * Found 2026-09-02 in documents/stillbirth_certificate.blade.php, which had
 * never rendered. A stillbirth certificate is not a page anyone exercises
 * casually, which is exactly why it sat broken.
 *
 * The sibling failure is the inline `@php(...)` form, which in this application
 * compiles to a literal `<?php(` that is never closed -- so everything after it
 * in the template stops compiling. Use `@php ... @endphp` instead.
 *
 * This test reads source, not compiled output: it is a fast grep, where
 * compiling and linting all ~680 views takes minutes.
 */
class BladeDirectiveIntegrityTest extends TestCase
{
    /** Directives that open a block, or that emit PHP and must be recognised. */
    private const DIRECTIVES = [
        'if', 'elseif', 'else', 'endif',
        'unless', 'endunless',
        'foreach', 'endforeach', 'forelse', 'empty', 'endforelse',
        'for', 'endfor', 'while', 'endwhile',
        'switch', 'case', 'break', 'default', 'endswitch',
        'isset', 'endisset', 'endempty',
        'php', 'endphp',
        'auth', 'endauth', 'guest', 'endguest',
        'section', 'endsection', 'yield', 'include', 'extends',
    ];

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $root  = resource_path('views');
        $files = [];

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    public function test_the_view_tree_is_not_empty(): void
    {
        // Guards the two tests below: a scan that finds no files trivially passes.
        $this->assertGreaterThan(300, count($this->bladeFiles()));
    }

    public function test_no_directive_is_glued_to_a_word_character(): void
    {
        $pattern  = '/[A-Za-z0-9_]@(' . implode('|', self::DIRECTIVES) . ')\b/';
        $offences = [];

        foreach ($this->bladeFiles() as $path) {
            $lines = preg_split('/\R/', (string) file_get_contents($path)) ?: [];

            foreach ($lines as $n => $line) {
                if (preg_match($pattern, $line, $m)) {
                    $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path);
                    $offences[] = sprintf('%s:%d  %s', $rel, $n + 1, trim($m[0]));
                }
            }
        }

        $this->assertSame(
            [],
            $offences,
            "A Blade directive preceded by a word character is emitted as literal text and never compiles.\n"
            . "Put a space or a newline before the '@'. Offenders:\n  " . implode("\n  ", $offences)
        );
    }

    public function test_the_inline_php_directive_form_is_not_used(): void
    {
        $offences = [];

        foreach ($this->bladeFiles() as $path) {
            $lines = preg_split('/\R/', (string) file_get_contents($path)) ?: [];

            foreach ($lines as $n => $line) {
                if (preg_match('/@php\s*\(/', $line)) {
                    $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path);
                    $offences[] = sprintf('%s:%d', $rel, $n + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offences,
            "@php(...) compiles to an unterminated '<?php(' here, so every directive after it stops "
            . "compiling and the template becomes a PHP parse error.\nUse @php ... @endphp. Offenders:\n  "
            . implode("\n  ", $offences)
        );
    }
}
