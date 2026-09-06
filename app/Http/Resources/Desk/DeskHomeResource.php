<?php

namespace App\Http\Resources\Desk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The front desk's landing screen: `{facility:{id,name}, counts:{…}}`.
 *
 * Wraps the array FrontDeskService assembles (facility() + counts()) so the
 * wire contract has one owner; every figure is a real count of a real set.
 *
 * @property array{facility: array{id:string,name:?string}, counts: array<string,int>} $resource
 */
class DeskHomeResource extends ApiResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'facility' => [
                'id'   => $this->resource['facility']['id'],
                'name' => $this->resource['facility']['name'],
            ],
            'counts'   => [
                'today_total'           => (int) ($this->resource['counts']['today_total'] ?? 0),
                'awaiting_confirmation' => (int) ($this->resource['counts']['awaiting_confirmation'] ?? 0),
                'confirmed'             => (int) ($this->resource['counts']['confirmed'] ?? 0),
                'checked_in'            => (int) ($this->resource['counts']['checked_in'] ?? 0),
                'completed'             => (int) ($this->resource['counts']['completed'] ?? 0),
                'no_show'               => (int) ($this->resource['counts']['no_show'] ?? 0),
                'cancelled'             => (int) ($this->resource['counts']['cancelled'] ?? 0),
                'unassigned'            => (int) ($this->resource['counts']['unassigned'] ?? 0),
            ],
        ];
    }
}
