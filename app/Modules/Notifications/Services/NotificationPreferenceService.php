<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\NotificationPreference;

class NotificationPreferenceService
{
    public function getPreferences(string $userId, string $category): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $userId, 'category' => $category],
            [
                'email_enabled' => true,
                'whatsapp_enabled' => true,
                'sms_enabled' => true,
                'push_enabled' => true,
                'voice_enabled' => false,
                'dashboard_enabled' => true,
                'quiet_hours_json' => json_encode(['start' => '22:00', 'end' => '07:00']),
                'language' => 'en'
            ]
        );
    }

    public function updatePreferences(string $userId, string $category, array $settings): NotificationPreference
    {
        $prefs = $this->getPreferences($userId, $category);
        $prefs->update($settings);
        return $prefs;
    }

    public function isChannelEnabled(string $userId, string $category, string $channel, string $priority = 'normal'): bool
    {
        // Mandatory notifications override quiet hours and preferences
        if ($category === 'account_and_security' || $priority === 'critical') {
            return true;
        }

        $prefs = $this->getPreferences($userId, $category);
        
        // Check quiet hours
        if ($this->isInQuietHours($prefs->quiet_hours_json)) {
            if ($priority !== 'urgent' && $priority !== 'high') {
                return false;
            }
        }

        $enabledField = "{$channel}_enabled";
        return (bool)($prefs->$enabledField ?? false);
    }

    private function isInQuietHours(?string $quietHoursJson): bool
    {
        if (!$quietHoursJson) {
            return false;
        }

        $config = json_decode($quietHoursJson, true);
        if (!isset($config['start']) || !isset($config['end'])) {
            return false;
        }

        $now = now();
        $start = now()->setTimeFromTimeString($config['start']);
        $end = now()->setTimeFromTimeString($config['end']);
        if (preg_match('/^\d{2}:\d{2}$/', $config['end'])) {
            $end = $end->addSeconds(59);
        }

        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }
}
