<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ReportingQuarter;
use App\Services\Reportes\ServicioTrimestresReporte;
use App\Services\Sedes\ServicioSedes;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class PanelController extends Controller
{
    public function __construct(
        private readonly ServicioTrimestresReporte $reportingQuarterService,
        private readonly ServicioSedes $sedeService,
    ) {}

    public function index(Request $request): View
    {
        $selectedSedeId = $this->selectedSedeId($request);
        $quarterYear = $this->reportingQuarterService->currentYear();

        return view('panel', [
            'puedeGestionarTrimestres' => $request->user()?->puedeGestionarTrimestresReporte() ?? false,
            'quarterYear' => $quarterYear,
            'quarters' => $this->reportingQuarterService->forCurrentYear($selectedSedeId),
            'quarterLimits' => collect(range(1, 4))
                ->mapWithKeys(function (int $quarterNumber) use ($quarterYear): array {
                    $range = $this->reportingQuarterService->calendarRange($quarterYear, $quarterNumber);

                    return [
                        $quarterNumber => [
                            'start_date' => $range['start_date']->toDateString(),
                            'end_date' => $range['end_date']->toDateString(),
                        ],
                    ];
                })
                ->all(),
            'quarterScopeLabel' => $this->sedeService->selectionLabel($selectedSedeId),
            'selectedSedeId' => $selectedSedeId,
        ]);
    }

    public function updateQuarters(Request $request): RedirectResponse
    {
        $user = $request->user();
        $selectedSedeId = $this->selectedSedeId($request);

        abort_unless($user?->puedeGestionarTrimestresReporte(), 403);

        if ($user?->isAdminSede()) {
            abort_unless((int) $user->id_sede === (int) $selectedSedeId, 403);
        }

        $year = $this->reportingQuarterService->currentYear();
        $validator = $this->validator($request, $year);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard')
                ->withErrors($validator, 'updateQuarters')
                ->withInput();
        }

        $this->reportingQuarterService->saveForYear(
            $year,
            $this->payload($request),
            $user?->id,
            $selectedSedeId
        );

        return redirect()
            ->route('dashboard')
            ->with('quarter_status', 'Trimestres actualizados correctamente para '.$this->sedeService->selectionLabel($selectedSedeId).'.');
    }

    private function validator(Request $request, int $year): ValidationValidator
    {
        $validator = Validator::make($request->all(), [
            'quarters' => ['required', 'array'],
            'quarters.1.start_date' => ['required', 'date'],
            'quarters.1.end_date' => ['required', 'date'],
            'quarters.2.start_date' => ['required', 'date'],
            'quarters.2.end_date' => ['required', 'date'],
            'quarters.3.start_date' => ['required', 'date'],
            'quarters.3.end_date' => ['required', 'date'],
            'quarters.4.start_date' => ['required', 'date'],
            'quarters.4.end_date' => ['required', 'date'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $year): void {
            $lastEndDate = null;
            $timezone = config('app.timezone');

            foreach (range(1, 4) as $quarterNumber) {
                $startInput = data_get($request->all(), 'quarters.'.$quarterNumber.'.start_date');
                $endInput = data_get($request->all(), 'quarters.'.$quarterNumber.'.end_date');

                if (! $startInput || ! $endInput) {
                    continue;
                }

                try {
                    $startDate = CarbonImmutable::parse($startInput, $timezone);
                    $endDate = CarbonImmutable::parse($endInput, $timezone);
                } catch (\Throwable) {
                    continue;
                }

                $allowedRange = $this->reportingQuarterService->calendarRange($year, $quarterNumber);
                $allowedStart = $allowedRange['start_date'];
                $allowedEnd = $allowedRange['end_date'];
                $rangeMessage = ReportingQuarter::labelFor($quarterNumber)
                    .' no puede superar los 3 meses permitidos. '
                    .'Solo se permite entre '.$allowedStart->toDateString().' y '.$allowedEnd->toDateString().'.';

                if ($startDate->year !== $year) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.start_date',
                        ReportingQuarter::labelFor($quarterNumber).' debe iniciar dentro del anio '.$year.'.'
                    );
                }

                if ($endDate->year !== $year) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.end_date',
                        ReportingQuarter::labelFor($quarterNumber).' debe finalizar dentro del anio '.$year.'.'
                    );
                }

                if ($endDate->lt($startDate)) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.end_date',
                        ReportingQuarter::labelFor($quarterNumber).' debe terminar despues de la fecha inicial.'
                    );
                }

                if ($startDate->lt($allowedStart) || $startDate->gt($allowedEnd)) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.start_date',
                        $rangeMessage
                    );
                }

                if ($endDate->lt($allowedStart) || $endDate->gt($allowedEnd)) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.end_date',
                        $rangeMessage
                    );
                }

                if ($lastEndDate !== null && $startDate->lte($lastEndDate)) {
                    $validator->errors()->add(
                        'quarters.'.$quarterNumber.'.start_date',
                        ReportingQuarter::labelFor($quarterNumber).' debe iniciar despues de que finalice el trimestre anterior.'
                    );
                }

                $lastEndDate = $endDate;
            }
        });

        return $validator;
    }

    /**
     * @return array<int, array{start_date: string, end_date: string}>
     */
    private function payload(Request $request): array
    {
        $quarters = [];

        foreach (range(1, 4) as $quarterNumber) {
            $quarters[$quarterNumber] = [
                'start_date' => (string) data_get($request->all(), 'quarters.'.$quarterNumber.'.start_date'),
                'end_date' => (string) data_get($request->all(), 'quarters.'.$quarterNumber.'.end_date'),
            ];
        }

        return $quarters;
    }

    private function selectedSedeId(Request $request): ?int
    {
        return $this->sedeService->resolveForRequest(
            $request->user(),
            $request
        );
    }
}
