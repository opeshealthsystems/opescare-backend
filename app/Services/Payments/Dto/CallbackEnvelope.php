<?php

namespace App\Services\Payments\Dto;

/**
 * A raw inbound provider callback, before anybody believes a word of it.
 *
 * Deliberately dumb: headers, body, raw body string, source IP. It exists so
 * `MobileMoneyProvider::verifyCallback()` has a single argument, and so a
 * controller can never hand a provider a half-parsed request that already lost
 * the exact bytes a signature is computed over.
 *
 * `rawBody` is kept verbatim for that reason — re-encoding `$body` to JSON and
 * hashing that gives a different digest from what the provider signed (key
 * order, unicode escaping, float formatting), which is the classic reason a
 * correct HMAC check fails in production.
 */
final class CallbackEnvelope
{
    public function __construct(
        /** @var array<string, string> Lower-cased header name => first value. */
        public readonly array $headers,

        /** @var array<string, mixed> Decoded JSON body, or [] when it was not JSON. */
        public readonly array $body,

        /** The exact bytes received. Signatures are computed over THIS. */
        public readonly string $rawBody,

        /** Peer address, for the IP allow-list some providers publish. */
        public readonly ?string $sourceIp = null,
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** Dot-notation read of the decoded body. */
    public function input(string $key, mixed $default = null): mixed
    {
        return data_get($this->body, $key, $default);
    }
}
