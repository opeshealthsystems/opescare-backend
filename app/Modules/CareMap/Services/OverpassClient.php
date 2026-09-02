<?php

namespace App\Modules\CareMap\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The one place this codebase talks to the Overpass API.
 *
 * Overpass is a free, donation-funded, shared service run for the OpenStreetMap
 * community. It has no API key, no quota dashboard and no account to throttle
 * us individually — the only thing standing between a well-behaved client and a
 * badly-behaved one is the client. A tight retry loop from a single importer is
 * enough to degrade the endpoint for everyone using it, so every guard below is
 * about being a guest, not about our own convenience:
 *
 *  - a User-Agent that identifies the project and a way to reach us, because an
 *    operator who cannot tell who is hammering them can only block by IP;
 *  - a floor on the interval between requests, enforced across the whole
 *    process, not per call site;
 *  - 429 (rate limited) and 504 (query timed out under load) treated as "come
 *    back later" with exponential backoff, honouring Retry-After when the
 *    server sends one — never as a failure to retry immediately;
 *  - a hard cap on attempts, so a sustained outage makes us stop rather than
 *    queue up behind it.
 *
 * ## Resumability
 *
 * Every successful response is written to disk, keyed by a hash of the query.
 * A re-run inside the cache window replays from disk and issues no request at
 * all. This is what makes the importer resumable — a crash halfway through
 * writing 255 Douala facilities is recovered by re-running the command, and the
 * recovery costs Overpass nothing. It also means iterating on the import logic
 * (or running --dry-run five times while tuning it) hits the network once.
 */
class OverpassClient
{
    /**
     * Contact details in the User-Agent are an Overpass community norm, not a
     * nicety: it is how an operator asks a client to slow down instead of
     * null-routing it.
     */
    public const USER_AGENT = 'OpesCare-FacilityImporter/1.0 (+https://opescare.cloud; facility-data@opescare.cloud)';

    public const DEFAULT_ENDPOINT = 'https://overpass-api.de/api/interpreter';

    /** Minimum wall-clock gap between two requests, in milliseconds. */
    private const MIN_INTERVAL_MS = 1500;

    /** Total attempts per query, including the first. */
    private const MAX_ATTEMPTS = 5;

    /** First backoff step, in seconds; doubles each attempt. */
    private const BASE_BACKOFF_SECONDS = 5;

    /** Never wait longer than this between attempts, whatever the server says. */
    private const MAX_BACKOFF_SECONDS = 120;

    /** Overpass can be slow on a country-wide query; give it room. */
    private const HTTP_TIMEOUT_SECONDS = 180;

    private const CACHE_DIR = 'osm-import';

    private ?float $lastRequestAt = null;

    public function __construct(
        private string $endpoint = self::DEFAULT_ENDPOINT,
        private string $disk = 'local',
    ) {
    }

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Run an Overpass QL query, replaying a cached response when one is fresh.
     *
     * @param  string   $query        Overpass QL, already assembled.
     * @param  int      $maxAgeHours  Reuse a cached response younger than this.
     *                                0 forces a fresh request.
     * @param  callable|null $onWait   fn(string $message): void — progress for the CLI.
     * @return array{elements: array<int,array<string,mixed>>, from_cache: bool, cache_path: string, copyright: ?string}
     */
    public function query(string $query, int $maxAgeHours = 24, ?callable $onWait = null): array
    {
        $path = $this->cachePath($query);

        if ($maxAgeHours > 0 && $this->cacheIsFresh($path, $maxAgeHours)) {
            $decoded = json_decode((string) Storage::disk($this->disk)->get($path), true);

            if (is_array($decoded) && isset($decoded['elements'])) {
                return [
                    'elements'   => $decoded['elements'],
                    'from_cache' => true,
                    'cache_path' => $path,
                    'copyright'  => $decoded['osm3s']['copyright'] ?? null,
                ];
            }
            // A corrupt cache file is not a reason to fail — fall through and refetch.
        }

        $decoded = $this->request($query, $onWait);

        Storage::disk($this->disk)->put($path, json_encode($decoded));

        return [
            'elements'   => $decoded['elements'] ?? [],
            'from_cache' => false,
            'cache_path' => $path,
            'copyright'  => $decoded['osm3s']['copyright'] ?? null,
        ];
    }

    /**
     * @param  callable|null $onWait
     * @return array<string,mixed>
     */
    private function request(string $query, ?callable $onWait = null): array
    {
        $attempt = 0;
        $lastError = 'no attempt was made';

        while ($attempt < self::MAX_ATTEMPTS) {
            $attempt++;
            $this->respectMinimumInterval();

            try {
                $response = Http::withHeaders([
                        'User-Agent' => self::USER_AGENT,
                        'Accept'     => 'application/json',
                    ])
                    ->timeout(self::HTTP_TIMEOUT_SECONDS)
                    ->asForm()
                    ->post($this->endpoint, ['data' => $query]);
            } catch (ConnectionException $e) {
                $lastError = 'connection failed: ' . $e->getMessage();
                $this->backoff($attempt, null, $lastError, $onWait);
                continue;
            }

            $status = $response->status();

            if ($response->successful()) {
                $decoded = $response->json();

                if (! is_array($decoded) || ! array_key_exists('elements', $decoded)) {
                    // Overpass reports some server-side failures as a 200 with an
                    // HTML error body. Treat that as retryable, not as zero results
                    // — silently importing nothing is the worst outcome here.
                    $lastError = 'response was not Overpass JSON (likely a server-side error page)';
                    $this->backoff($attempt, null, $lastError, $onWait);
                    continue;
                }

                return $decoded;
            }

            // 429 = rate limited, 504 = query timed out server-side, 5xx = transient.
            // Anything else (400 malformed QL, 404 bad endpoint) is our bug: fail loudly.
            if (! in_array($status, [429, 502, 503, 504], true)) {
                throw new RuntimeException(
                    "Overpass returned HTTP {$status} — this is not a retryable status. "
                    . 'Body: ' . mb_substr((string) $response->body(), 0, 500)
                );
            }

            $lastError = "HTTP {$status}";
            $this->backoff($attempt, $this->retryAfterSeconds($response->header('Retry-After')), $lastError, $onWait);
        }

        throw new RuntimeException(
            'Overpass did not answer after ' . self::MAX_ATTEMPTS . " attempts (last: {$lastError}). "
            . 'The importer is resumable — re-run it later and it will pick up from the cache.'
        );
    }

    /**
     * Sleep so that consecutive requests are never closer than MIN_INTERVAL_MS.
     */
    private function respectMinimumInterval(): void
    {
        if ($this->lastRequestAt !== null) {
            $elapsedMs = (microtime(true) - $this->lastRequestAt) * 1000;

            if ($elapsedMs < self::MIN_INTERVAL_MS) {
                usleep((int) ((self::MIN_INTERVAL_MS - $elapsedMs) * 1000));
            }
        }

        $this->lastRequestAt = microtime(true);
    }

    private function backoff(int $attempt, ?int $retryAfter, string $reason, ?callable $onWait): void
    {
        if ($attempt >= self::MAX_ATTEMPTS) {
            return; // caller is about to throw; do not sleep on the way out
        }

        // The server's own Retry-After always wins when it sends one.
        $seconds = $retryAfter ?? (self::BASE_BACKOFF_SECONDS * (2 ** ($attempt - 1)));
        $seconds = (int) min($seconds, self::MAX_BACKOFF_SECONDS);

        Log::info('Overpass backoff', [
            'attempt' => $attempt,
            'reason'  => $reason,
            'sleep_s' => $seconds,
        ]);

        if ($onWait !== null) {
            $onWait("Overpass says {$reason}; waiting {$seconds}s before attempt " . ($attempt + 1) . '.');
        }

        sleep($seconds);
    }

    private function retryAfterSeconds(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return max(1, (int) $header);
        }

        $timestamp = strtotime($header);

        return $timestamp === false ? null : max(1, $timestamp - time());
    }

    public function cachePath(string $query): string
    {
        return self::CACHE_DIR . '/' . hash('sha256', $this->endpoint . "\n" . $query) . '.json';
    }

    private function cacheIsFresh(string $path, int $maxAgeHours): bool
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($path)) {
            return false;
        }

        return $storage->lastModified($path) >= (time() - ($maxAgeHours * 3600));
    }
}
