<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** DHIS2 reporting mapping (docs/PLATFORM_IMPORTS.md, group E). */
class Dhis2OrgUnitMapping extends Model
{
    use HasUuids;

    protected $table = 'dhis2_org_unit_mappings';

    protected $fillable = ['facility_id', 'org_unit_id', 'set_by'];
}
