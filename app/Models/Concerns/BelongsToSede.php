<?php

namespace App\Models\Concerns;

use App\Models\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSede
{
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }

    public function scopeForSede($query, ?int $sedeId)
    {
        if ($sedeId === null) {
            return $query;
        }

        return $query->where($this->getTable().'.id_sede', $sedeId);
    }
}
