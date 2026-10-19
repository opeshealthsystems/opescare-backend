<?php

namespace App\Support;

use App\Enums\PainRegion;
use Carbon\CarbonInterface;

/**
 * Which anatomy figure the chart's body map draws, and where its heart is.
 *
 * The art is the Health ID's living anatomy (copied from the phone app's
 * assets/health-id/living/ into public/images/health-id/living/), so the
 * clinician and the patient see the same body.
 *
 * SELECTION, by recorded sex and age, never guessed:
 *
 *   age < CHILD_UNDER_YEARS  -> child-boy / child-girl
 *   otherwise                -> adult-male / adult-female
 *
 * When sex is not recorded as male or female (null, 'other', 'unknown') there
 * is no neutral figure to draw. The chart then uses the same stand-in the
 * patient's own Health ID uses for an unspecified profile (male art at the
 * patient's age build; LivingAnatomy.tsx maps `generic` to adult-male) and
 * says so under the figure: `sexAssumed` is true and the view prints
 * pain.figure_sex_unrecorded. An unknown date of birth draws the adult build
 * and says that too. Nothing about the patient is inferred from the drawing.
 */
final class BodyFigure
{
    public const CHILD_UNDER_YEARS = 13;

    /** meta.json, inlined: the heart box per figure, as fractions of 305 x 720. */
    private const HEART = [
        'adult-male'   => ['x' => 0.36721311475409835, 'y' => 0.16805555555555557, 'w' => 0.26885245901639343, 'h' => 0.13055555555555556],
        'adult-female' => ['x' => 0.36065573770491804, 'y' => 0.16944444444444445, 'w' => 0.26885245901639343, 'h' => 0.13055555555555556],
        'child-boy'    => ['x' => 0.3639344262295082, 'y' => 0.225, 'w' => 0.26885245901639343, 'h' => 0.13055555555555556],
        'child-girl'   => ['x' => 0.35737704918032787, 'y' => 0.20694444444444443, 'w' => 0.26885245901639343, 'h' => 0.13055555555555556],
    ];

    private function __construct(
        public readonly string $figure,
        public readonly string $build,
        public readonly bool $sexAssumed,
        public readonly bool $ageAssumed,
    ) {
    }

    public static function for(?string $sex, ?CarbonInterface $dob): self
    {
        $ageAssumed = $dob === null;
        $build = (! $ageAssumed && $dob->age < self::CHILD_UNDER_YEARS) ? 'child' : 'adult';

        $sex    = $sex !== null ? strtolower(trim($sex)) : null;
        $female = in_array($sex, ['female', 'f'], true);
        $male   = in_array($sex, ['male', 'm'], true);

        $figure = $build === 'child'
            ? ($female ? 'child-girl' : 'child-boy')
            : ($female ? 'adult-female' : 'adult-male');

        return new self($figure, $build, ! ($female || $male), $ageAssumed);
    }

    /** @return array{x: float, y: float, w: float, h: float} */
    public function heart(): array
    {
        return self::HEART[$this->figure];
    }

    public function baseUrl(): string
    {
        return asset('images/health-id/living/' . $this->figure . '-base.png');
    }

    public function heartUrl(): string
    {
        return asset('images/health-id/living/' . $this->figure . '-heart.png');
    }

    public function currentUrl(): string
    {
        return asset('images/health-id/living/' . $this->figure . '-current.webp');
    }

    /** @return array{0: float, 1: float}|null */
    public function point(PainRegion $region): ?array
    {
        return $region->frontPoint($this->build);
    }
}
