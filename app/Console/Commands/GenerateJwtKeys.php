<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generate the RS256 key pair used to sign and verify bearer tokens.
 *
 * The keys used to be committed to git (security scan 2026-09-15, item 4).
 * They are now per-environment: run this once on a new machine or server.
 * It refuses to replace an existing pair unless --force is given, because
 * replacing the keys invalidates every token already issued.
 */
class GenerateJwtKeys extends Command
{
    protected $signature = 'opescare:jwt-keys {--force : Replace an existing key pair (invalidates all issued tokens)}';

    protected $description = 'Generate the RS256 JWT key pair for this environment';

    public function handle(): int
    {
        $private = (string) config('jwt.private_key_path');
        $public  = (string) config('jwt.public_key_path');

        if ((file_exists($private) || file_exists($public)) && ! $this->option('force')) {
            $this->error('A JWT key pair already exists. Use --force to replace it (this invalidates all issued tokens).');

            return self::FAILURE;
        }

        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        // Windows PHP builds cannot find openssl.cnf unless OPENSSL_CONF is set;
        // use the one shipped beside the PHP binary. Linux servers need nothing.
        $bundled = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf';
        if (! getenv('OPENSSL_CONF') && is_file($bundled)) {
            $options['config'] = $bundled;
        }

        $key = openssl_pkey_new($options);
        if ($key === false || ! openssl_pkey_export($key, $privatePem, null, $options)) {
            $this->error('Could not generate the key pair: ' . openssl_error_string());

            return self::FAILURE;
        }
        $publicPem = openssl_pkey_get_details($key)['key'];

        foreach ([$private, $public] as $path) {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }
        }

        file_put_contents($private, $privatePem);
        file_put_contents($public, $publicPem);
        @chmod($private, 0600);
        @chmod($public, 0644);

        $this->info('JWT key pair written. Keep the private key out of version control.');

        return self::SUCCESS;
    }
}
