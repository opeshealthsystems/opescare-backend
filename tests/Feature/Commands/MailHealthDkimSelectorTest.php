<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\MailHealthCheck;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The DKIM check must not guess.
 *
 * It used to try seven conventional selector names and give up. This
 * deployment signs with the selector `opescare`, whose key was already
 * published and working — so `mail:health` reported "no DKIM record found" on
 * a domain whose DKIM was fine, and pointed at a problem that did not exist.
 *
 * A selector cannot be enumerated from DNS. It can be read off the signer that
 * is doing the signing, and that is what these tests pin. No DNS is touched
 * here: this is about where the list of candidates comes from.
 */
class MailHealthDkimSelectorTest extends TestCase
{
    private function selectors(string $domain): array
    {
        $method = new ReflectionMethod(MailHealthCheck::class, 'dkimSelectors');

        return $method->invoke(app(MailHealthCheck::class), $domain);
    }

    private function writeKeyTable(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'keytable');
        file_put_contents($path, $contents);
        config(['mail_health.opendkim_key_table' => $path]);

        return $path;
    }

    public function test_the_selector_the_local_signer_uses_is_discovered(): void
    {
        // The exact shape opendkim-genkey produces, and the exact line this
        // deployment carries.
        $path = $this->writeKeyTable(
            "# a comment\n"
            . "opescare._domainkey.opescare.cloud opescare.cloud:opescare:/etc/opendkim/keys/opescare.cloud/opescare.private\n"
        );

        $selectors = $this->selectors('opescare.cloud');

        $this->assertArrayHasKey('opescare', $selectors);
        $this->assertSame('named by the local opendkim key table', $selectors['opescare']);

        // It is asked about BEFORE the conventional guesses, so the OK line
        // names the selector actually in use.
        $this->assertSame('opescare', array_key_first($selectors));

        @unlink($path);
    }

    public function test_a_selector_for_another_domain_is_not_borrowed(): void
    {
        $path = $this->writeKeyTable(
            "other._domainkey.example.test example.test:other:/etc/opendkim/keys/example.test/other.private\n"
        );

        $this->assertArrayNotHasKey('other', $this->selectors('opescare.cloud'));

        @unlink($path);
    }

    public function test_configuration_wins_over_everything(): void
    {
        $path = $this->writeKeyTable(
            "opescare._domainkey.opescare.cloud opescare.cloud:opescare:/etc/opendkim/keys/opescare.cloud/opescare.private\n"
        );
        config(['mail_health.dkim_selectors' => ['ops-chose-this']]);

        $selectors = $this->selectors('opescare.cloud');

        $this->assertSame('ops-chose-this', array_key_first($selectors));
        $this->assertSame('configured', $selectors['ops-chose-this']);

        @unlink($path);
    }

    public function test_no_local_signer_is_not_an_error(): void
    {
        // A deployment that relays through a provider has no key table at all.
        config(['mail_health.opendkim_key_table' => '/no/such/path/key.table']);
        config(['mail_health.dkim_selectors' => []]);

        $selectors = $this->selectors('opescare.cloud');

        $this->assertNotEmpty($selectors, 'the conventional selectors must still be tried');
        $this->assertContainsOnly('string', array_keys($selectors));
    }
}
