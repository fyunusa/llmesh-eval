<?php

namespace LLMesh\Eval\Results;

final readonly class EvalResult
{
    /**
     * @param string $input
     * @param string $output
     * @param MetricResult[] $metricResults
     * @param bool $passed
     * @param float $durationMs
     */
    public function __construct(
        public string $input,
        public string $output,
        public array $metricResults,
        public bool $passed,
        public float $durationMs
    ) {
    }

    /**
     * @return MetricResult[]
     */
    public function failedMetrics(): array
    {
        return array_values(array_filter($this->metricResults, fn($m) => !$m->passed));
    }

    /**
     * Get a one-line summary.
     */
    public function summary(): string
    {
        $total = count($this->metricResults);
        $passedCount = count(array_filter($this->metricResults, fn($m) => $m->passed));
        $status = $this->passed ? 'PASSED' : 'FAILED';
        return sprintf(
            '[%s] %d/%d metrics passed (%.2fms)',
            $status,
            $passedCount,
            $total,
            $this->durationMs
        );
    }

    /**
     * Convert the evaluation result to a serializable array.
     */
    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'output' => $this->output,
            'passed' => $this->passed,
            'duration_ms' => $this->durationMs,
            'metrics' => array_map(fn(MetricResult $m) => [
                'metric_name' => $m->metricName,
                'score' => $m->score,
                'threshold' => $m->threshold,
                'passed' => $m->passed,
                'reasoning' => $m->reasoning,
                'details' => $m->details,
            ], $this->metricResults),
        ];
    }
}
