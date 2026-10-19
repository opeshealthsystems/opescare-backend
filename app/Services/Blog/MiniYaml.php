<?php

namespace App\Services\Blog;

use InvalidArgumentException;

/**
 * A strict, minimal YAML reader for blog front matter and the FAQ file.
 *
 * symfony/yaml is not installed and vendor/ is tracked in git, so a composer
 * require would drag hundreds of files into the repo for one small job. This
 * reads the subset the content contract uses and throws on anything else:
 *
 *   - block mappings and sequences (nested by indentation, spaces only)
 *   - sequences of mappings ("- q: ...\n  a: ...")
 *   - plain, 'single' and "double" quoted scalars, plain scalars folded over
 *     more-indented continuation lines
 *   - flow sequences of scalars: [a, "b, c"]
 *   - block scalars: | |- > >-
 *   - comments (# at line start, or " #" after a plain scalar)
 *   - true/false/null and integers; everything else stays a string (dates too)
 *
 * Anchors, aliases, tags, flow mappings and multi-document streams are
 * rejected rather than misread.
 */
final class MiniYaml
{
    /** @var list<array{indent:int,text:string,raw:string,no:int}> */
    private array $lines = [];

    private int $pos = 0;

    /** @var list<string> */
    private array $raw = [];

    public static function parse(string $source): array
    {
        $parser = new self();

        return $parser->run($source);
    }

    private function run(string $source): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        if (str_starts_with($source, "\u{FEFF}")) {
            $source = substr($source, 3);
        }
        $this->raw = explode("\n", $source);

        foreach ($this->raw as $no => $line) {
            if (str_contains($line, "\t") && preg_match('/^\s*\t/', $line)) {
                throw new InvalidArgumentException('Tabs are not allowed for indentation (line ' . ($no + 1) . ')');
            }
            $trimmed = ltrim($line, ' ');
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if ($trimmed === '---' || $trimmed === '...') {
                continue;
            }
            $this->lines[] = [
                'indent' => strlen($line) - strlen($trimmed),
                'text'   => rtrim($trimmed),
                'raw'    => $line,
                'no'     => $no,
            ];
        }

        if ($this->lines === []) {
            return [];
        }

        $value = $this->parseBlock($this->lines[0]['indent']);

        if ($this->pos < count($this->lines)) {
            throw new InvalidArgumentException('Unexpected content at line ' . ($this->lines[$this->pos]['no'] + 1));
        }
        if (! is_array($value)) {
            throw new InvalidArgumentException('Top level must be a mapping or sequence');
        }

        return $value;
    }

    private function peek(): ?array
    {
        return $this->lines[$this->pos] ?? null;
    }

    private static function isSeqItem(string $text): bool
    {
        return $text === '-' || str_starts_with($text, '- ');
    }

    private function parseBlock(int $indent): mixed
    {
        $line = $this->peek();
        if ($line === null) {
            return null;
        }

        return self::isSeqItem($line['text'])
            ? $this->parseSequence($line['indent'])
            : $this->parseMapping($line['indent']);
    }

    private function parseMapping(int $indent): array
    {
        $out = [];

        while (($line = $this->peek()) !== null && $line['indent'] === $indent && ! self::isSeqItem($line['text'])) {
            [$key, $rest] = $this->splitKey($line);
            $this->pos++;

            if (array_key_exists($key, $out)) {
                throw new InvalidArgumentException("Duplicate key '{$key}' at line " . ($line['no'] + 1));
            }

            $out[$key] = $this->parseValue($rest, $indent, $line, true);
        }

        if (($line = $this->peek()) !== null && $line['indent'] > $indent) {
            throw new InvalidArgumentException('Bad indentation at line ' . ($line['no'] + 1));
        }

        return $out;
    }

    private function parseSequence(int $indent): array
    {
        $out = [];

        while (($line = $this->peek()) !== null && $line['indent'] === $indent && self::isSeqItem($line['text'])) {
            $content = $line['text'] === '-' ? '' : substr($line['text'], 2);
            $contentTrim = ltrim($content, ' ');
            $offset = $indent + 2 + (strlen($content) - strlen($contentTrim));

            if ($contentTrim === '') {
                $this->pos++;
                $next = $this->peek();
                $out[] = ($next !== null && $next['indent'] > $indent) ? $this->parseBlock($next['indent']) : null;
                continue;
            }

            if ($this->looksLikeKey($contentTrim)) {
                // "- key: value" opens a mapping whose keys sit at $offset.
                $this->lines[$this->pos] = ['indent' => $offset, 'text' => $contentTrim, 'raw' => $line['raw'], 'no' => $line['no']];
                $out[] = $this->parseMapping($offset);
                continue;
            }

            if (self::isSeqItem($contentTrim)) {
                throw new InvalidArgumentException('Nested inline sequences are not supported (line ' . ($line['no'] + 1) . ')');
            }

            $this->pos++;
            $out[] = $this->parseValue($contentTrim, $indent, $line, false);
        }

        if (($line = $this->peek()) !== null && $line['indent'] > $indent) {
            throw new InvalidArgumentException('Bad indentation at line ' . ($line['no'] + 1));
        }

        return $out;
    }

    /** Value after "key:" (or a bare sequence scalar). $indent is the owning line's indent. */
    private function parseValue(string $rest, int $indent, array $line, bool $inMapping): mixed
    {
        $rest = trim($rest);

        if ($rest === '' ) {
            $next = $this->peek();
            if ($next === null) {
                return null;
            }
            if ($next['indent'] > $indent) {
                return $this->parseBlock($next['indent']);
            }
            // YAML lets a mapping's sequence sit at the key's own indent.
            if ($inMapping && $next['indent'] === $indent && self::isSeqItem($next['text'])) {
                return $this->parseSequence($indent);
            }

            return null;
        }

        if (preg_match('/^([|>])([+-]?)\s*(#.*)?$/', $rest, $m)) {
            return $this->blockScalar($m[1], $m[2], $indent, $line['no']);
        }

        if ($rest[0] === '&' || $rest[0] === '*' || $rest[0] === '!' || $rest[0] === '{') {
            throw new InvalidArgumentException('Unsupported YAML construct at line ' . ($line['no'] + 1));
        }

        if ($rest[0] === '[') {
            return $this->flowSequence($rest, $line['no']);
        }

        // Continuation lines: a scalar folded over more-indented lines.
        while (($next = $this->peek()) !== null && $next['indent'] > $indent && ! $this->looksLikeKey($next['text']) && ! self::isSeqItem($next['text'])) {
            $rest .= ' ' . $next['text'];
            $this->pos++;
        }

        return $this->scalar($rest, $line['no']);
    }

    private function blockScalar(string $style, string $chomp, int $indent, int $no): string
    {
        // Work from raw lines so blank lines and deeper indentation survive.
        $start = $no + 1;
        $collected = [];
        $blockIndent = null;

        for ($i = $start; $i < count($this->raw); $i++) {
            $raw = $this->raw[$i];
            if (trim($raw) === '') {
                $collected[] = '';
                continue;
            }
            $ind = strlen($raw) - strlen(ltrim($raw, ' '));
            if ($ind <= $indent) {
                break;
            }
            $blockIndent ??= $ind;
            if ($ind < $blockIndent) {
                break;
            }
            $collected[] = substr($raw, $blockIndent);
        }

        // Advance the structural cursor past every line we consumed.
        $lastRaw = $start + count($collected) - 1;
        while (($next = $this->peek()) !== null && $next['no'] <= $lastRaw) {
            $this->pos++;
        }

        while ($collected !== [] && end($collected) === '') {
            array_pop($collected);
        }

        if ($style === '|') {
            $text = implode("\n", $collected);
        } else {
            $text = '';
            foreach ($collected as $i => $l) {
                if ($l === '') {
                    $text .= "\n";
                } elseif ($i > 0 && $collected[$i - 1] !== '' && $text !== '' && ! str_ends_with($text, "\n")) {
                    $text .= ' ' . $l;
                } else {
                    $text .= $l;
                }
            }
        }

        return $chomp === '-' ? $text : ($text === '' ? '' : $text . "\n");
    }

    private function flowSequence(string $rest, int $no): array
    {
        $rest = preg_replace('/\s+#.*$/', '', $rest);
        if (! str_ends_with($rest, ']')) {
            throw new InvalidArgumentException('Unterminated flow sequence at line ' . ($no + 1));
        }
        $inner = trim(substr($rest, 1, -1));
        if ($inner === '') {
            return [];
        }

        $items = [];
        $buf = '';
        $quote = null;
        $len = strlen($inner);
        for ($i = 0; $i < $len; $i++) {
            $c = $inner[$i];
            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote === '"' && $i + 1 < $len) {
                    $buf .= $inner[++$i];
                } elseif ($c === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($c === '"' || $c === "'") {
                $quote = $c;
                $buf .= $c;
            } elseif ($c === ',') {
                $items[] = $this->scalar(trim($buf), $no);
                $buf = '';
            } elseif ($c === '[' || $c === '{') {
                throw new InvalidArgumentException('Nested flow collections are not supported (line ' . ($no + 1) . ')');
            } else {
                $buf .= $c;
            }
        }
        if (trim($buf) !== '') {
            $items[] = $this->scalar(trim($buf), $no);
        }

        return $items;
    }

    private function scalar(string $text, int $no): mixed
    {
        $text = trim($text);

        if ($text !== '' && $text[0] === '"') {
            if (! preg_match('/^"((?:[^"\\\\]|\\\\.)*)"\s*(#.*)?$/s', $text, $m)) {
                throw new InvalidArgumentException('Bad double-quoted string at line ' . ($no + 1));
            }
            $decoded = json_decode('"' . str_replace(["\n", "\t"], ['\n', '\t'], $m[1]) . '"');
            if (! is_string($decoded)) {
                // YAML escapes JSON does not know (\', \/ ...) — fall back to stripcslashes.
                $decoded = stripcslashes($m[1]);
            }

            return $decoded;
        }

        if ($text !== '' && $text[0] === "'") {
            if (! preg_match("/^'((?:[^']|'')*)'\s*(#.*)?$/s", $text, $m)) {
                throw new InvalidArgumentException('Bad single-quoted string at line ' . ($no + 1));
            }

            return str_replace("''", "'", $m[1]);
        }

        $text = preg_replace('/\s+#.*$/', '', $text);

        return match (strtolower($text)) {
            'true'        => true,
            'false'       => false,
            'null', '~', '' => null,
            default       => preg_match('/^-?\d{1,15}$/', $text) ? (int) $text : $text,
        };
    }

    private function looksLikeKey(string $text): bool
    {
        return (bool) preg_match('/^(?:"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[A-Za-z0-9_][A-Za-z0-9_ .\-]*?)\s*:(?:\s|$)/', $text);
    }

    /** @return array{0:string,1:string} */
    private function splitKey(array $line): array
    {
        if (! preg_match('/^("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[A-Za-z0-9_][A-Za-z0-9_ .\-]*?)\s*:(?:\s+(.*))?$/', $line['text'], $m)) {
            throw new InvalidArgumentException('Expected "key: value" at line ' . ($line['no'] + 1));
        }
        $key = $m[1];
        if ($key[0] === '"' || $key[0] === "'") {
            $key = (string) $this->scalar($key, $line['no']);
        }

        return [$key, $m[2] ?? ''];
    }
}
