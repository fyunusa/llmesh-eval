<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class ContextualPrecisionMetric implements MetricInterface
{
    use ResolvesJudge;

    /**
     * @param string[] $retrievedContexts Array of retrieved context chunks
     * @param float $threshold             Default threshold value is 0.7
     * @param JudgeInterface|null $judge  Uses LLMJudge by default
     */
    public function __construct(
        private readonly array $retrievedContexts,
        private readonly float $threshold = 0.7,
        private readonly ?JudgeInterface $judge = null
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
        $ctx = !empty($this->retrievedContexts) ? implode("\n---\n", $this->retrievedContexts) : ($context ?? '');

        $rubric = "Given the user query, score how relevant the retrieved context chunks are.\n"
            . "1.0 = all retrieved context chunks are highly relevant to answering the query.\n"
            . "0.0 = none of the retrieved chunks are relevant to the query.";

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score('contextual_precision', $input, $output, $rubric, $ctx);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning,
            details: [
                'chunks_count' => count($this->retrievedContexts),
            ]
        );
    }

    public function getName(): string
    {
        return 'ContextualPrecision';
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
