<?php

namespace App\Services\Reportes;

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
    public function forCurrentYear(): Collection
    {
        return $this->forYear($this->currentYear());
    }

    /**
     * @return Collection<int, ReportingQuarter>
     */
    public function forYear(int $year): Collection
    {
        $storedQuarters = ReportingQuarter::query()
            ->where('year', $year)
            ->orderBy('quarter_number')
            ->get()
            ->keyBy('quarter_number');

        return collect(range(1, 4))
            ->map(fn (int $quarterNumber): ReportingQuarter => $this->resolveQuarter(
                $year,
                $quarterNumber,
                $storedQuarters->get($quarterNumber)
            ));
    }

    public function findForCurrentYear(int $quarterNumber): ReportingQuarter
    {
        return $this->findForYear($this->currentYear(), $quarterNumber);
    }

    public function findForYear(int $year, int $quarterNumber): ReportingQuarter
    {
        $quarter = ReportingQuarter::query()
            ->where('year', $year)
            ->where('quarter_number', $quarterNumber)
            ->first();

        return $this->resolveQuarter($year, $quarterNumber, $quarter);
    }

    /**
     * @param  array<int, array{start_date: string, end_date: string}>  $quarters
     */
    public function saveForYear(int $year, array $quarters, ?int $updatedBy = null): void
    {
        $timestamp = now();
        $payload = collect(range(1, 4))
            ->map(fn (int $quarterNumber): array => [
                'year' => $year,
                'quarter_number' => $quarterNumber,
                'start_date' => $quarters[$quarterNumber]['start_date'],
                'end_date' => $quarters[$quarterNumber]['end_date'],
                'updated_by' => $updatedBy,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        ReportingQuarter::query()->upsert(
            $payload,
            ['year', 'quarter_number'],
            ['start_date', 'end_date', 'updated_by', 'updated_at']
        );
    }

    private function resolveQuarter(int $year, int $quarterNumber, ?ReportingQuarter $quarter): ReportingQuarter
    {
        if ($quarter) {
            return $quarter;
        }

        return new ReportingQuarter($this->defaultQuarterPayload($year, $quarterNumber));
    }

    /**
     * @return array{year: int, quarter_number: int, start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    private function defaultQuarterPayload(int $year, int $quarterNumber): array
    {
        return match ($quarterNumber) {
            1 => [
                'year' => $year,
                'quarter_number' => 1,
                'start_date' => CarbonImmutable::create($year, 1, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 3, 31, 0, 0, 0, config('app.timezone')),
            ],
            2 => [
                'year' => $year,
                'quarter_number' => 2,
                'start_date' => CarbonImmutable::create($year, 4, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 6, 30, 0, 0, 0, config('app.timezone')),
            ],
            3 => [
                'year' => $year,
                'quarter_number' => 3,
                'start_date' => CarbonImmutable::create($year, 7, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 9, 30, 0, 0, 0, config('app.timezone')),
            ],
            default => [
                'year' => $year,
                'quarter_number' => 4,
                'start_date' => CarbonImmutable::create($year, 10, 1, 0, 0, 0, config('app.timezone')),
                'end_date' => CarbonImmutable::create($year, 12, 31, 0, 0, 0, config('app.timezone')),
            ],
        };
    }
}
