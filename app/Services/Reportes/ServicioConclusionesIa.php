<?php

namespace App\Services\Reportes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ServicioConclusionesIa
{
    public function configured(): bool
    {
        return filled((string) config('services.openai.key'));
    }

    /**
     * @param  array{
     *     observations?: array<int, string>,
     *     totals?: array{survey_count?: int},
     *     indicators?: array{
     *         global?: array{
     *             satisfaction_percentage?: float|int,
     *             neutral_answer_percentage?: float|int,
     *             dissatisfaction_answer_percentage?: float|int
     *         }
     *     },
     *     questions?: array<int, array{
     *         number?: int,
     *         dimension?: string,
     *         satisfaction?: array{satisfied_percentage?: float|int}
     *     }>
     * }  $report
     * @param  array{
     *      title?: string,
     *      quarter?: string,
     *      period?: string,
     *      process?: string|null,
     *      dependency?: string|null
     * }  $context
     */
    public function generate(string $type, array $report, array $context = []): string
    {
        $apiKey = trim((string) config('services.openai.key'));

        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para generar la conclusion con IA.');
        }

        $observations = array_values(array_filter(
            array_map(
                static fn (mixed $value): string => trim((string) $value),
                $report['observations'] ?? []
            ),
            static fn (string $value): bool => $value !== ''
        ));

        if ($observations === []) {
            throw new RuntimeException('No hay observaciones recientes para generar una conclusion.');
        }

        $model = (string) config('services.openai.model', 'gpt-5-mini');
        $response = Http::baseUrl(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->retry(2, 500)
            ->post('/responses', [
                'model' => $model,
                'store' => false,
                'instructions' => $this->instructions(),
                'input' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $this->buildPrompt($type, $report, $context, $observations),
                    ]],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'report_ai_conclusion',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'conclusion' => [
                                    'type' => 'string',
                                    'description' => 'Parrafo institucional breve, claro y accionable en espanol.',
                                ],
                            ],
                            'required' => ['conclusion'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            $apiMessage = trim((string) ($response->json('error.message') ?? ''));

            Log::warning('OpenAI conclusion generation failed.', [
                'body' => $response->json(),
                'message' => $apiMessage,
                'model' => $model,
                'status' => $response->status(),
            ]);

            throw new RuntimeException($this->buildFailureMessage($response->status(), $apiMessage, $model));
        }

        $outputText = $this->extractOutputText($response->json());

        if ($outputText === '') {
            throw new RuntimeException('La IA no devolvio una conclusion utilizable.');
        }

        $decoded = json_decode($outputText, true);
        $conclusion = is_array($decoded)
            ? trim((string) ($decoded['conclusion'] ?? ''))
            : trim($outputText);

        if ($conclusion === '') {
            throw new RuntimeException('La IA no devolvio una conclusion utilizable.');
        }

        return preg_replace('/\s+/u', ' ', $conclusion) ?? $conclusion;
    }

    private function instructions(): string
    {
        return implode(' ', [
            'Eres un analista institucional de satisfaccion de usuarios.',
            'Debes redactar una conclusion ejecutiva corta en espanol neutro.',
            'Usa un tono formal, claro y administrativo.',
            'Basate principalmente en las observaciones escritas por los encuestados y usa los indicadores solo como apoyo contextual.',
            'Si las observaciones repiten un mismo patron, destacalo de forma sintetica.',
            'No inventes datos.',
            'No menciones que eres IA ni nombres modelos.',
            'No uses listas ni titulos.',
            'Entrega un solo parrafo entre 55 y 95 palabras.',
            'Incluye un cierre breve orientado a mejora o seguimiento.',
        ]);
    }

    /**
     * @param  array<int, string>  $observations
     * @param  array{
     *      title?: string,
     *      quarter?: string,
     *      period?: string,
     *      process?: string|null,
     *      dependency?: string|null
     * }  $context
     * @param  array{
     *     totals?: array{survey_count?: int},
     *     indicators?: array{
     *         global?: array{
     *             satisfaction_percentage?: float|int,
     *             neutral_answer_percentage?: float|int,
     *             dissatisfaction_answer_percentage?: float|int
     *         }
     *     },
     *     questions?: array<int, array{
     *         number?: int,
     *         dimension?: string,
     *         satisfaction?: array{satisfied_percentage?: float|int}
     *     }>
     * }  $report
     */
    private function buildPrompt(string $type, array $report, array $context, array $observations): string
    {
        $questionSummary = collect($report['questions'] ?? [])
            ->map(static function (array $question): string {
                return sprintf(
                    '%s: %s%% satisfechos',
                    (string) ($question['dimension'] ?? 'Dimension'),
                    rtrim(rtrim(number_format((float) ($question['satisfaction']['satisfied_percentage'] ?? 0), 2, '.', ''), '0'), '.')
                );
            })
            ->implode('; ');

        return implode("\n", array_filter([
            'Tipo de reporte: '.$this->typeLabel($type),
            filled($context['title'] ?? null) ? 'Titulo del modulo: '.$context['title'] : null,
            filled($context['quarter'] ?? null) ? 'Trimestre: '.$context['quarter'] : null,
            filled($context['period'] ?? null) ? 'Periodo: '.$context['period'] : null,
            filled($context['process'] ?? null) ? 'Proceso: '.$context['process'] : null,
            filled($context['dependency'] ?? null) ? 'Dependencia: '.$context['dependency'] : null,
            'Encuestas del periodo: '.(int) ($report['totals']['survey_count'] ?? 0),
            'Indicador global de satisfaccion: '.$this->formatPercentage($report['indicators']['global']['satisfaction_percentage'] ?? 0).'%',
            'Porcentaje de respuestas neutras: '.$this->formatPercentage($report['indicators']['global']['neutral_answer_percentage'] ?? 0).'%',
            'Porcentaje de respuestas insatisfactorias: '.$this->formatPercentage($report['indicators']['global']['dissatisfaction_answer_percentage'] ?? 0).'%',
            $questionSummary !== '' ? 'Satisfaccion por dimension: '.$questionSummary : null,
            'Observaciones textuales de los encuestados. La conclusion debe salir principalmente de estos comentarios:',
            collect($observations)
                ->map(static fn (string $observation, int $index): string => ($index + 1).'. '.$observation)
                ->implode("\n"),
            'Genera el texto final para reemplazar el parrafo de conclusion del reporte.',
        ]));
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'process' => 'Reporte por proceso',
            'individual' => 'Reporte por dependencia',
            default => 'Reporte general',
        };
    }

    private function formatPercentage(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractOutputText(array $payload): string
    {
        $outputText = trim((string) ($payload['output_text'] ?? ''));

        if ($outputText !== '') {
            return $outputText;
        }

        foreach (($payload['output'] ?? []) as $outputItem) {
            foreach (($outputItem['content'] ?? []) as $contentItem) {
                $text = trim((string) ($contentItem['text'] ?? ''));

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    private function buildFailureMessage(int $status, string $apiMessage, string $model): string
    {
        $normalized = trim($apiMessage);

        if ($normalized === '') {
            return 'No fue posible generar la conclusion con IA. Intenta nuevamente en unos segundos.';
        }

        if ($status === 401) {
            return 'OpenAI rechazo la autenticacion. Verifica que OPENAI_API_KEY sea valida.';
        }

        if ($status === 429) {
            return 'OpenAI rechazo la solicitud por limite o saldo insuficiente. Revisa la facturacion y el uso de la API.';
        }

        if ($status === 404 || str_contains(strtolower($normalized), 'model')) {
            return 'El modelo configurado para OpenAI no esta disponible: '.$model.'. Revisa OPENAI_MODEL en tu .env.';
        }

        return 'OpenAI respondio: '.$normalized;
    }
}
