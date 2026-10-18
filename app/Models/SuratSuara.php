<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class SuratSuara extends Pivot
{
    protected $table = 'surat_suara';

    public $incrementing = true;

    /**
     * Coarsen timestamp precision to the start of the hour (YYYY-MM-DD HH:00:00)
     * to prevent exact-second timing correlation on voter records.
     */
    public function freshTimestamp(): Carbon
    {
        return Carbon::now()->startOfHour();
    }

    /**
     * Ensure any datetime serialized for surat_suara timestamps is coarsened to the hour.
     */
    public function fromDateTime($value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        return parent::fromDateTime(Carbon::parse($value)->startOfHour());
    }
}
