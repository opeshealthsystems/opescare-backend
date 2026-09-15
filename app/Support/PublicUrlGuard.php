<?php

namespace App\Support;

/**
 * Is a URL safe for the server to call on someone else's behalf?
 *
 * Partner webhooks are POSTed from our server to a URL the partner types in.
 * Without this check a partner could point one at 127.0.0.1, the private
 * network or the cloud metadata address and use our server to reach inside
 * (security scan 2026-09-15, item 5).
 *
 * A URL passes only if it is https, carries no credentials, and its host
 * resolves exclusively to public unicast addresses. resolve() returns the
 * address that was checked so the caller can pin the connection to it —
 * otherwise the host could be re-pointed between check and request.
 */
final class PublicUrlGuard
{
    /** CGNAT (RFC 6598) — not covered by PHP's private/reserved flags. */
    private const EXTRA_BLOCKED_V4 = [['100.64.0.0', 10], ['192.0.0.0', 24], ['198.18.0.0', 15]];

    /** @var (\Closure(string): list<string>)|null  Test seam: replaces DNS lookup for hostnames. */
    private static ?\Closure $resolver = null;

    /** Tests only: answer hostname lookups without DNS (IP literals are always checked for real). */
    public static function resolveUsing(?\Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function isAllowed(string $url): bool
    {
        return self::resolve($url) !== null;
    }

    /**
     * @return array{host:string, port:int, ip:string}|null  null when the URL must not be called
     */
    public static function resolve(string $url): ?array
    {
        $parts = parse_url($url);
        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = trim($parts['host'], '[]');
        $port = (int) ($parts['port'] ?? 443);

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::lookup($host);
        if ($ips === []) {
            return null;
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return null;
            }
        }

        return ['host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    public static function isPublicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            foreach (self::EXTRA_BLOCKED_V4 as [$net, $bits]) {
                $mask = -1 << (32 - $bits);
                if (($long & $mask) === (ip2long($net) & $mask)) {
                    return false;
                }
            }

            return true;
        }

        // IPv6: refuse IPv4-mapped/compatible forms and unique-local/link-local.
        $lower = strtolower($ip);
        if (str_starts_with($lower, '::ffff:') || str_starts_with($lower, 'fc') || str_starts_with($lower, 'fd')
            || str_starts_with($lower, 'fe8') || str_starts_with($lower, 'fe9')
            || str_starts_with($lower, 'fea') || str_starts_with($lower, 'feb')) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    private static function lookup(string $host): array
    {
        if (self::$resolver !== null) {
            return array_values((self::$resolver)($host));
        }

        $ips = [];
        foreach ((array) @gethostbynamel($host) as $ip) {
            if (is_string($ip)) {
                $ips[] = $ip;
            }
        }
        foreach ((array) @dns_get_record($host, DNS_AAAA) as $rec) {
            if (! empty($rec['ipv6'])) {
                $ips[] = $rec['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
