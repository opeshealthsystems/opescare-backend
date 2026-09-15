<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** DHIS2 reporting mapping (docs/PLATFORM_IMPORTS.md, group E). */
class Dhis2DataElementMapping extends Model
{
    use HasUuids;

    protected $table = 'dhis2_data_element_mappings';

    protected $fillable = ['indicator_code', 'data_element_id', 'category_option_combo_id', 'set_by'];
}
