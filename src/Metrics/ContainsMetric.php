<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Judges\HeuristicJudge;
use LLMesh\Eval\Results\MetricResult;

class ContainsMetric implements MetricInterface
{
    /**
     * @param string $expected      The substring or regex pattern expected to be in the output
     * @param bool $caseSensitive   Whether comparison is case-sensitive
     * @param bool $regex           Whether to treat expected as a regex pattern
     * @param float $threshold      Default threshold is 1.0 (binary lookup)
     */
    public function __construct(
        private readonly string $expected,
        private readonly bool $caseSensitive = false,
        private readonly bool $regex = false,
        private readonly float $threshold = 1.0
    ) {
    }

    /**
     * Measure the quality of the LLM response.
     */
    public function measure(
        string $input,
        string $output,
        ?string $context = null,
        ?string $expected = null
    ): MetricResult {
        $judge = new HeuristicJudge();
        $rubric = json_encode([
            'expected' => $this->expected,
            'case_sensitive' => $this->caseSensitive,
            'regex' => $this->regex,
        ]);

        $score = $judge->score('contains', $input, $output, $rubric, $context);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning,
            details: [
                'expected' => $this->expected,
                'case_sensitive' => $this->caseSensitive,
                'regex' => $this->regex,
            ]
        );
    }

    public function getName(): string
    {
        return 'Contains';
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function passes(MetricResult $result): bool
    {
        return $result->passed;
    }
}
