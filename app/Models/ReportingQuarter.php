<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingQuarter extends Model
{
    protected $fillable = [
        'year',
        'quarter_number',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quarter_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'updated_by' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function label(): string
    {
        return self::labelFor($this->quarter_number);
    }

    public function periodLabel(string $format = 'd/m/Y'): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return '';
        }

        return $this->start_date->format($format).' a '.$this->end_date->format($format);
    }

    public static function labelFor(int $quarterNumber): string
    {
        return match ($quarterNumber) {
            1 => 'I Trimestre',
            2 => 'II Trimestre',
            3 => 'III Trimestre',
            4 => 'IV Trimestre',
            default => 'Trimestre '.$quarterNumber,
        };
    }
}
