<?php

namespace App\Services\Reportes;

use App\Models\Sede;
use App\Models\ReportingQuarter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ServicioTrimestresReporte
{
    /**
     * @return array{start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    public function calendarRange(int $year, int $quarterNumber): array
    {
        $payload = $this->defaultQuarterPayload($year, $quarterNumber);

        return [
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
        ];
    }

    public function currentYear(): int
    {
        return (int) now(config('app.timezone'))->year;
    }

    /**
     * @return Collection<int, ReportingQuarter>
     */
    public function forCurrentYear(?int $sedeId = null): Collection
    {
        return $this->forYear($this->currentYear(), $sedeId);
    }

    /**
     * @return Collection<int, ReportingQuarter>
     */
    public function forYear(int $year, ?int $sedeId = null): Collection
    {
        $storedQuarters = $this->storedQuarterMap($year, $sedeId);

        return collect(range(1, 4))
            ->map(fn (int $quarterNumber): ReportingQuarter => $this->resolveQuarter(
                $year,
                $quarterNumber,
                $storedQuarters->get($quarterNumber),
                $sedeId
            ));
    }

    public function findForCurrentYear(int $quarterNumber, ?int $sedeId = null): ReportingQuarter
    {
        return $this->findForYear($this->currentYear(), $quarterNumber, $sedeId);
    }

    public function findForYear(int $year, int $quarterNumber, ?int $sedeId = null): ReportingQuarter
    {
        $quarter = $this->storedQuarterMap($year, $sedeId)->get($quarterNumber);

        return $this->resolveQuarter($year, $quarterNumber, $quarter, $sedeId);
    }

    /**
     * @param  array<int, array{start_date: string, end_date: string}>  $quarters
     */
    public function saveForYear(int $year, array $quarters, ?int $updatedBy = null, ?int $sedeId = null): void
    {
        foreach (range(1, 4) as $quarterNumber) {
            ReportingQuarter::query()->updateOrCreate(
                [
                    'id_sede' => $sedeId,
                    'year' => $year,
                    'quarter_number' => $quarterNumber,
                ],
                [
                    'start_date' => $quarters[$quarterNumber]['start_date'],
                    'end_date' => $quarters[$quarterNumber]['end_date'],
                    'updated_by' => $updatedBy,
                ]
            );
        }
    }

    private function resolveQuarter(int $year, int $quarterNumber, ?ReportingQuarter $quarter, ?int $sedeId = null): ReportingQuarter
    {
        if ($quarter) {
            return $quarter;
        }

        return new ReportingQuarter($this->defaultQuarterPayload($year, $quarterNumber, $sedeId));
    }

    /**
     * @return Collection<int, ReportingQuarter>
     */
    private function storedQuarterMap(int $year, ?int $sedeId): Collection
    {
        $quarters = ReportingQuarter::query()
            ->where('year', $year)
            ->when(
                $sedeId !== null,
                fn ($query) => $query->where(function ($builder) use ($sedeId): void {
                    $builder
                        ->where('id_sede', $sedeId)
                        ->orWhereNull('id_sede');
                }),
                fn ($query) => $query->whereNull('id_sede')
            )
            ->orderByRaw('CASE WHEN id_sede IS NULL THEN 1 ELSE 0 END')
            ->orderBy('quarter_number')
            ->get();

        return collect(range(1, 4))
            ->mapWithKeys(fn (int $quarterNumber): array => [
                $quarterNumber => $quarters->first(fn (ReportingQuarter $quarter): bool => (int) $quarter->quarter_number === $quarterNumber),
            ]);
    }

    /**
     * @return array{id_sede: int|null, year: int, quarter_number: int, start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    private function defaultQuarterPayload(int $year, int $quarterNumber, ?int $sedeId = null): array
    {
        return match ($quarterNumber) {
            1 => [
                'id_sede' => $sedeId,
                'year' => $year,
                'quarter_number' => 1,
                'start_date' => CarbonImmutable::create($year, 1, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 3, 31, 0, 0, 0, config('app.timezone')),
            ],
            2 => [
                'id_sede' => $sedeId,
                'year' => $year,
                'quarter_number' => 2,
                'start_date' => CarbonImmutable::create($year, 4, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 6, 30, 0, 0, 0, config('app.timezone')),
            ],
            3 => [
                'id_sede' => $sedeId,
                'year' => $year,
                'quarter_number' => 3,
                'start_date' => CarbonImmutable::create($year, 7, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 9, 30, 0, 0, 0, config('app.timezone')),
            ],
            default => [
                'id_sede' => $sedeId,
                'year' => $year,
                'quarter_number' => 4,
                'start_date' => CarbonImmutable::create($year, 10, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 12, 31, 0, 0, 0, config('app.timezone')),
            ],
        };
    }
}
