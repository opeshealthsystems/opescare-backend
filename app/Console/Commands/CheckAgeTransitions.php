<?php
namespace App\Console\Commands;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Notifications\FamilyEventNotification;
use Illuminate\Console\Command;

class CheckAgeTransitions extends Command
{
    protected $signature   = 'family:check-age-transitions';
    protected $description = 'Expire grace-period family links and send age transition warnings';

    public function handle(): int
    {
        $majorityAge = (int) config('family.majority_age', 18);
        $warningDays = (int) config('family.age_warning_days', 60);
        $graceDays   = (int) config('family.age_grace_days', 30);

        // 1. Expire links whose grace period has passed
        $expired = FamilyLink::where('status', 'active')
            ->whereNotNull('age_transition_expires_at')
            ->where('age_transition_expires_at', '<', now())
            ->get();

        foreach ($expired as $link) {
            $link->update(['status' => 'expired']);
            $this->line("Expired link {$link->id}");
        }

        // 2. Set grace period for patients who turn majority age today.
        //    date_of_birth is encrypted — we cannot use whereDate() on the DB column.
        //    Instead, load active FamilyLinks with their dependent patient and compare in PHP.
        $birthdayToday = now()->subYears($majorityAge)->startOfDay();

        FamilyLink::active()
            ->whereNull('age_transition_expires_at')
            ->with(['dependentPatient', 'guardianUser'])
            ->chunkById(200, function ($links) use ($birthdayToday, $graceDays, $majorityAge) {
                foreach ($links as $link) {
                    $patient = $link->dependentPatient;
                    if (!$patient || !$patient->date_of_birth) {
                        continue;
                    }
                    if ($patient->date_of_birth->isSameDay($birthdayToday)) {
                        $link->update(['age_transition_expires_at' => now()->addDays($graceDays)]);
                        $link->guardianUser?->notify(
                            new FamilyEventNotification(
                                $link,
                                'age_transition',
                                "{$patient->first_name} has turned {$majorityAge}. Guardian access enters a {$graceDays}-day grace period."
                            )
                        );
                        $this->line("Grace period set for link {$link->id}");
                    }
                }
            });

        // 3. Send 60-day warning for patients approaching majority age.
        //    Same reason — date_of_birth is encrypted, compare in PHP.
        $warningBirthday = now()->subYears($majorityAge)->addDays($warningDays)->startOfDay();

        FamilyLink::active()
            ->whereNull('age_transition_notified_at')
            ->with(['dependentPatient', 'guardianUser'])
            ->chunkById(200, function ($links) use ($warningBirthday, $warningDays, $majorityAge) {
                foreach ($links as $link) {
                    $patient = $link->dependentPatient;
                    if (!$patient || !$patient->date_of_birth) {
                        continue;
                    }
                    if ($patient->date_of_birth->isSameDay($warningBirthday)) {
                        $link->update(['age_transition_notified_at' => now()]);
                        $link->guardianUser?->notify(
                            new FamilyEventNotification(
                                $link,
                                'age_transition',
                                "{$patient->first_name} will turn {$majorityAge} in {$warningDays} days. Guardian access will require re-consent."
                            )
                        );
                        $this->line("60-day warning sent for link {$link->id}");
                    }
                }
            });

        $this->info('Age transition check complete.');
        return self::SUCCESS;
    }
}
