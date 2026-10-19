<?php

namespace App\Enums;

/**
 * The closed vocabulary of body regions a pain site can be reported against.
 *
 * Closed so a marker can be placed on the body map; anything finer goes in the
 * site's free-text note. Left and right are the PATIENT'S left and right — on
 * a front-facing figure the patient's left arm is on the viewer's right.
 *
 * Coordinates are fractions of the figure box (305 x 720, the Health ID
 * living-anatomy art in public/images/health-id/living/). Only FRONT views
 * exist; the two back regions have no coordinates and are listed without a
 * marker until back-view art is drawn — a pin on the wrong side of a body is
 * worse than no pin.
 */
enum PainRegion: string
{
    case Head         = 'head';
    case Forehead     = 'forehead';
    case Face         = 'face';
    case Neck         = 'neck';
    case Chest        = 'chest';
    case UpperAbdomen = 'upper_abdomen';
    case LowerAbdomen = 'lower_abdomen';
    case Pelvis       = 'pelvis';
    case RightArm     = 'right_arm';
    case LeftArm      = 'left_arm';
    case RightLeg     = 'right_leg';
    case LeftLeg      = 'left_leg';
    case UpperBack    = 'upper_back';
    case LowerBack    = 'lower_back';

    /** Is this region on the back of the body? */
    public function isBack(): bool
    {
        return match ($this) {
            self::UpperBack, self::LowerBack => true,
            default => false,
        };
    }

    /**
     * [x, y] as fractions of the figure, on the FRONT view, or null when the
     * region is not visible from the front.
     *
     * @param  'adult'|'child'  $build
     * @return array{0: float, 1: float}|null
     */
    public function frontPoint(string $build): ?array
    {
        $child = $build === 'child';

        return match ($this) {
            self::Head         => $child ? [0.50, 0.05] : [0.50, 0.025],
            self::Forehead     => $child ? [0.50, 0.09] : [0.50, 0.045],
            self::Face         => $child ? [0.50, 0.135] : [0.50, 0.09],
            self::Neck         => $child ? [0.50, 0.215] : [0.50, 0.15],
            self::Chest        => $child ? [0.43, 0.29] : [0.42, 0.23],
            self::UpperAbdomen => $child ? [0.50, 0.385] : [0.50, 0.33],
            self::LowerAbdomen => $child ? [0.50, 0.46] : [0.50, 0.415],
            self::Pelvis       => $child ? [0.50, 0.53] : [0.50, 0.48],
            self::RightArm     => $child ? [0.19, 0.42] : [0.17, 0.36],
            self::LeftArm      => $child ? [0.81, 0.42] : [0.83, 0.36],
            self::RightLeg     => $child ? [0.42, 0.73] : [0.41, 0.70],
            self::LeftLeg      => $child ? [0.58, 0.73] : [0.59, 0.70],
            self::UpperBack, self::LowerBack => null,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }

    public function label(): string
    {
        return __('pain.region.' . $this->value);
    }
}
