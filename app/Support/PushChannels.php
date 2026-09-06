<?php

namespace App\Support;

/**
 * The one place that knows which Android channel a push belongs on and which
 * sound that channel plays. Backed by config/push_channels.php.
 *
 * Both lookups are total: an unknown type lands on the 'default' channel and
 * an unknown channel plays the OS default tone, so a caller can never build an
 * unsendable message by naming a type the config has not heard of.
 */
final class PushChannels
{
    public const DEFAULT_CHANNEL = 'default';

    public const DEFAULT_SOUND = 'default';

    /** The Android channel id for a data.type value. */
    public static function channelFor(?string $type): string
    {
        if ($type === null || $type === '') {
            return self::DEFAULT_CHANNEL;
        }

        $channel = config("push_channels.types.{$type}");

        if (! is_string($channel) || $channel === '' || ! array_key_exists($channel, self::channels())) {
            return self::DEFAULT_CHANNEL;
        }

        return $channel;
    }

    /** The bundled sound filename a channel plays ('default' for the OS tone). */
    public static function soundFor(?string $channel): string
    {
        if ($channel === null || $channel === '') {
            return self::DEFAULT_SOUND;
        }

        $sound = config("push_channels.channels.{$channel}");

        return is_string($sound) && $sound !== '' ? $sound : self::DEFAULT_SOUND;
    }

    /** Sound for a type in one hop — what a caller with only a data.type wants. */
    public static function soundForType(?string $type): string
    {
        return self::soundFor(self::channelFor($type));
    }

    /** @return array<string, string> channel id => sound filename */
    public static function channels(): array
    {
        $channels = config('push_channels.channels', []);

        return is_array($channels) ? $channels : [];
    }

    /** @return array<string, string> data.type => channel id */
    public static function types(): array
    {
        $types = config('push_channels.types', []);

        return is_array($types) ? $types : [];
    }
}
