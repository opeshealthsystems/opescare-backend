<?php

namespace Tests\Feature\Notifications;

use App\Support\WebNotificationLink;
use Tests\TestCase;

/**
 * The bell may only offer a link the web can open.
 *
 * Every case below is a real `url` this codebase writes into a notification
 * payload today — see LabDeskService::notifyResultReleased() for the clinician
 * one, which pointed at an acknowledge screen that exists only in the app.
 */
class WebNotificationLinkTest extends TestCase
{
    public function test_an_app_only_path_is_not_offered_as_a_web_link(): void
    {
        // The ordering clinician's released-result bell. `/clinician/*` is an
        // expo-router path; the doctor's web portal has no results screen.
        $this->assertNull(WebNotificationLink::resolve('/clinician/results/lab-0198f2b1-0000-7000-8000-000000000000'));

        // The patient's own result bell — same story, `/labs/<id>` is in-app.
        $this->assertNull(WebNotificationLink::resolve('/labs/0198f2b1-0000-7000-8000-000000000000'));
    }

    public function test_a_real_web_path_survives(): void
    {
        $this->assertSame('/notifications', WebNotificationLink::resolve('/notifications'));

        // A query string is kept — only the PATH is matched against the router.
        $this->assertSame('/notifications?from=bell', WebNotificationLink::resolve('/notifications?from=bell'));
    }

    public function test_nothing_off_site_is_ever_linked(): void
    {
        foreach ([null, '', '   ', 'https://example.test/phish', '//example.test/phish', 'javascript:alert(1)'] as $hostile) {
            $this->assertNull(WebNotificationLink::resolve($hostile), var_export($hostile, true));
        }
    }
}
