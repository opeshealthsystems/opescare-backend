{{--
    Facility profile JSON-LD — include from the profile page's <head> (or push
    to the layout's schema stack) with the profile's $facility in scope:

        @include('partials.finder.schema.facility', ['facility' => $facility])

    Typed by what the registry says the facility is (Pharmacy / Hospital /
    DiagnosticLab / Dentist / MedicalClinic) — the types local and answer
    engines use to answer "pharmacy in Douala". Every field is data we
    actually hold: telephone only via dialablePhone() (never the 'N/A'
    placeholder), geo only when the row is geocoded, openingHours only when
    hours exist. Nothing is invented — no ratings, no images, no sameAs.
--}}
@php
    $ffType = strtolower(str_replace(' ', '_', $facility->facility_type ?? ''));

    $ffSchemaType = match ($ffType) {
        'pharmacy'                                       => 'Pharmacy',
        'hospital'                                       => 'Hospital',
        'dental'                                         => 'Dentist',
        'laboratory', 'diagnostic_center', 'imaging_center' => 'DiagnosticLab',
        default                                          => 'MedicalClinic',
    };

    $ffUrl = route('public.care-map.profile', $facility->id);

    $ffAddress = array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => \App\Models\CareFacility::realValue($facility->address),
        'addressLocality' => \App\Models\CareFacility::realValue($facility->city),
        'addressRegion'   => \App\Models\CareFacility::realValue($facility->region),
        'addressCountry'  => 'CM',
    ], fn ($v) => $v !== null);

    // day_of_week is 0=Sunday … 6=Saturday (see care_map/directory.blade.php).
    $ffDays  = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $ffHours = [];
    foreach ($facility->hours ?? [] as $ffHour) {
        if ($ffHour->is_closed || ! isset($ffDays[$ffHour->day_of_week])) {
            continue;
        }
        $ffOpens  = $ffHour->is_24_hours ? '00:00' : substr((string) $ffHour->opens_at, 0, 5);
        $ffCloses = $ffHour->is_24_hours ? '23:59' : substr((string) $ffHour->closes_at, 0, 5);
        if ($ffOpens === '' || $ffCloses === '') {
            continue;
        }
        $ffHours[] = [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => 'https://schema.org/' . $ffDays[$ffHour->day_of_week],
            'opens'     => $ffOpens,
            'closes'    => $ffCloses,
        ];
    }

    $ffSchema = array_filter([
        '@context'  => 'https://schema.org',
        '@type'     => $ffSchemaType,
        '@id'       => $ffUrl . '#place',
        'name'      => $facility->facility_name,
        'url'       => $ffUrl,
        'address'   => count($ffAddress) > 2 ? $ffAddress : null,
        'geo'       => ($facility->latitude && $facility->longitude) ? [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $facility->latitude,
            'longitude' => (float) $facility->longitude,
        ] : null,
        'telephone' => $facility->dialablePhone(),
        'openingHoursSpecification' => $ffHours !== [] ? $ffHours : null,
        'isPartOf'  => ['@id' => \App\Support\FinderSeo::base() . '/#website'],
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($ffSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
