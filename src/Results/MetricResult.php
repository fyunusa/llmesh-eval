<?php

namespace LLMesh\Eval\Results;

final readonly class MetricResult
{
    /**
     * @param float $score 0.0 to 1.0
     * @param string $metricName
     * @param float $threshold
     * @param bool $passed score >= threshold
     * @param string $reasoning why this score was given
     * @param array $details extra data
     */
    public function __construct(
        public float $score,
        public string $metricName,
        public float $threshold,
        public bool $passed,
        public string $reasoning,
        public array $details = []
    ) {
    }

    /**
     * Get a human-readable representation of the metric result.
     *
     * @return string E.g. "Faithfulness: 0.92 ✓ (threshold: 0.90)"
     */
    public function formatted(): string
    {
        $symbol = $this->passed ? '✓' : '✗';
        return sprintf(
            '%s: %.2f %s (threshold: %.2f)',
            $this->metricName,
            $this->score,
            $symbol,
            $this->threshold
        );
    }
}
