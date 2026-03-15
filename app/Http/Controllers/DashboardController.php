<?php

namespace App\Http\Controllers;

use App\Models\ReportingQuarter;
use App\Services\ReportingQuarterService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportingQuarterService $reportingQuarterService,
    ) {}

    public function index(Request $request): View
    {
        return view('dashboard', [
            'canManageQuarters' => $request->user()?->canManageReportingQuarters() ?? false,
            'quarterYear' => $this->reportingQuarterService->currentYear(),
            'quarters' => $this->reportingQuarterService->forCurrentYear(),
        ]);
    }

    public function updateQuarters(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->canManageReportingQuarters(), 403);

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
            $user?->id
        );

        return redirect()
            ->route('dashboard')
            ->with('quarter_status', 'Trimestres actualizados correctamente.');
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
}
