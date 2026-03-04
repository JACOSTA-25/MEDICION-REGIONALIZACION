<?php

namespace App\Services;

use App\Models\Respuesta;
use App\Support\LegacyReferenceData;

class SurveyReportService
{
    private const SATISFACTION_BUCKETS = [
        'mala',
        'intermedia',
        'buena',
    ];

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     total_responses: int,
     *     total_answers: int,
     *     overall: array{counts: array<string, int>, percentages: array<string, float>},
     *     questions: array<int, array{number: int, label: string, total_answers: int, counts: array<string, int>, percentages: array<string, float>}>,
     *     breakdown_title: string,
     *     breakdown: array<int, array{name: string, responses: int, total_answers: int, counts: array<string, int>, percentages: array<string, float>}>,
     *     observations: array<int, string>
     * }
     */
    public function generate(string $type, string $from, string $to, ?int $processId = null, ?int $dependencyId = null): array
    {
        $responses = Respuesta::query()
            ->with([
                'dependencia:id_dependencia,nombre',
                'proceso:id_proceso,nombre',
                'servicio:id_servicio,nombre',
            ])
            ->whereDate('fecha_respuesta', '>=', $from)
            ->whereDate('fecha_respuesta', '<=', $to)
            ->when($processId !== null, fn ($query) => $query->where('id_proceso', $processId))
            ->when($dependencyId !== null, fn ($query) => $query->where('id_dependencia', $dependencyId))
            ->orderBy('fecha_respuesta')
            ->get();

        $overallCounts = $this->emptyCounts();
        $totalAnswers = 0;
        $questionStats = [];
        $breakdown = [];

        foreach (LegacyReferenceData::questionLabels() as $number => $label) {
            $questionStats[$number] = [
                'number' => $number,
                'label' => $label,
                'total_answers' => 0,
                'counts' => $this->emptyCounts(),
            ];
        }

        foreach ($responses as $response) {
            $group = $this->resolveBreakdownGroup($type, $response);

            if ($group !== null && ! array_key_exists($group['key'], $breakdown)) {
                $breakdown[$group['key']] = [
                    'name' => $group['name'],
                    'responses' => 0,
                    'total_answers' => 0,
                    'counts' => $this->emptyCounts(),
                ];
            }

            if ($group !== null) {
                $breakdown[$group['key']]['responses']++;
            }

            foreach (array_keys(LegacyReferenceData::questionLabels()) as $number) {
                $value = (int) data_get($response, 'pregunta'.$number);
                $bucket = $this->bucketForValue($value);

                $questionStats[$number]['counts'][$bucket]++;
                $questionStats[$number]['total_answers']++;
                $overallCounts[$bucket]++;
                $totalAnswers++;

                if ($group !== null) {
                    $breakdown[$group['key']]['counts'][$bucket]++;
                    $breakdown[$group['key']]['total_answers']++;
                }
            }
        }

        foreach ($questionStats as $number => $stats) {
            $questionStats[$number]['percentages'] = $this->percentages($stats['counts'], $stats['total_answers']);
        }

        foreach ($breakdown as $key => $item) {
            $breakdown[$key]['percentages'] = $this->percentages($item['counts'], $item['total_answers']);
        }

        uasort($breakdown, static function (array $left, array $right): int {
            if ($left['responses'] === $right['responses']) {
                return strcmp($left['name'], $right['name']);
            }

            return $right['responses'] <=> $left['responses'];
        });

        $observations = $responses
            ->pluck('observaciones')
            ->filter(static fn (?string $value): bool => filled($value))
            ->map(static fn (string $value): string => trim($value))
            ->take(10)
            ->values()
            ->all();

        return [
            'from' => $from,
            'to' => $to,
            'total_responses' => $responses->count(),
            'total_answers' => $totalAnswers,
            'overall' => [
                'counts' => $overallCounts,
                'percentages' => $this->percentages($overallCounts, $totalAnswers),
            ],
            'questions' => array_values($questionStats),
            'breakdown_title' => $this->breakdownTitle($type),
            'breakdown' => array_values($breakdown),
            'observations' => $observations,
        ];
    }

    private function bucketForValue(int $value): string
    {
        if ($value <= 2) {
            return 'mala';
        }

        if ($value === 3) {
            return 'intermedia';
        }

        return 'buena';
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts(): array
    {
        return [
            'mala' => 0,
            'intermedia' => 0,
            'buena' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, float>
     */
    private function percentages(array $counts, int $total): array
    {
        $percentages = [];

        foreach (self::SATISFACTION_BUCKETS as $bucket) {
            $percentages[$bucket] = $total > 0
                ? round(($counts[$bucket] / $total) * 100, 2)
                : 0.0;
        }

        return $percentages;
    }

    /**
     * @return array{key: string, name: string}|null
     */
    private function resolveBreakdownGroup(string $type, Respuesta $response): ?array
    {
        return match ($type) {
            'general' => $response->proceso
                ? [
                    'key' => 'proceso-'.$response->proceso->id_proceso,
                    'name' => $response->proceso->nombre,
                ]
                : null,
            'process' => $response->dependencia
                ? [
                    'key' => 'dependencia-'.$response->dependencia->id_dependencia,
                    'name' => $response->dependencia->nombre,
                ]
                : null,
            'individual' => $response->servicio
                ? [
                    'key' => 'servicio-'.$response->servicio->id_servicio,
                    'name' => $response->servicio->nombre,
                ]
                : null,
            default => null,
        };
    }

    private function breakdownTitle(string $type): string
    {
        return match ($type) {
            'general' => 'Consolidado por proceso',
            'process' => 'Consolidado por dependencia',
            'individual' => 'Consolidado por servicio',
            default => 'Consolidado',
        };
    }
}
