<?php

namespace Tests\Feature\Mail;

use App\Mail\OtpMail;
use Tests\TestCase;

/**
 * The email footer must never tell a patient to write to a no-reply mailbox.
 *
 * `config('opescare.support_email')` fell back to `MAIL_FROM_ADDRESS`, and the
 * footer rendered it inside "For help, write to :email." On production the
 * from-address is noreply@opescare.cloud and no support address is set, so
 * every email would have directed a patient in need of help to an address
 * that discards replies — while sounding helpful.
 *
 * The rule now: a configured support address renders the sentence; an
 * empty one omits it. There is no third case.
 */
class FooterNeverPointsAtTheNoReplyMailboxTest extends TestCase
{
    private function render(): array
    {
        $mail = (new OtpMail('483920', 10))->locale('en');

        return [$mail->render(), (string) $mail->textView()];
    }

    public function test_with_no_support_address_the_help_sentence_is_omitted(): void
    {
        config([
            'opescare.support_email' => '',
            'mail.from.address'      => 'noreply@opescare.cloud',
        ]);

        $html = (new OtpMail('483920', 10))->locale('en')->render();

        $this->assertStringContainsString(__('mail.chrome.no_reply_plain', [], 'en'), $html);
        $this->assertStringNotContainsString('write to', $html);
        $this->assertStringNotContainsString('noreply@', $html);
        $this->assertStringNotContainsString('no-reply@', $html);
        $this->assertStringNotContainsString('example.com', $html);
    }

    public function test_the_from_address_is_never_used_as_the_help_address(): void
    {
        // The specific regression: support unset, from-address set. The old
        // fallback rendered the from-address here.
        config([
            'opescare.support_email' => '',
            'mail.from.address'      => 'noreply@opescare.cloud',
        ]);

        $this->assertStringNotContainsString('noreply@opescare.cloud',
            (new OtpMail('483920', 10))->locale('fr')->render());
    }

    public function test_a_configured_support_address_is_shown(): void
    {
        config(['opescare.support_email' => 'aide@opescare.cloud']);

        $html = (new OtpMail('483920', 10))->locale('fr')->render();

        $this->assertStringContainsString('aide@opescare.cloud', $html);
        $this->assertStringContainsString('écrivez à', $html);
    }

    public function test_the_config_itself_carries_no_from_address_fallback(): void
    {
        // Guard the source, not just the rendered result: a future "helpful"
        // default of MAIL_FROM_ADDRESS re-creates the bug for every consumer
        // of this config value, not only the email footer.
        $source = file_get_contents(config_path('opescare.php'));

        $this->assertMatchesRegularExpression("/'support_email'\s*=>\s*env\('OPESCARE_SUPPORT_EMAIL',\s*''\)/", $source);
        $this->assertStringNotContainsString("env('MAIL_FROM_ADDRESS'", substr($source, strpos($source, "'support_email'"), 200));
    }
}
