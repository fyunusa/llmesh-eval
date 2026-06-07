<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class FaithfulnessMetric implements MetricInterface
{
    use ResolvesJudge;

    /**
     * @param string|null $context   Optional reference document for grounding checks
     * @param float $threshold       Default threshold value is 0.8
     * @param JudgeInterface|null $judge Uses LLMJudge by default
     */
    public function __construct(
        private readonly ?string $context = null,
        private readonly float $threshold = 0.8,
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
        $ctx = $context ?? $this->context;
        if ($ctx === null || $ctx === '') {
            throw new \InvalidArgumentException('FaithfulnessMetric requires a context document.');
        }

        $rubric = "Score how faithfully the AI response is grounded in the reference document.\n"
            . "1.0 = every claim in the response is directly supported by the document.\n"
            . "0.0 = the response contains significant claims not found in the document (hallucinations).\n"
            . "Penalize any specific claims, numbers, names, or facts not in the document.";

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score('faithfulness', $input, $output, $rubric, $ctx);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning,
            details: [
                'context' => $ctx,
            ]
        );
    }

    public function getName(): string
    {
        return 'Faithfulness';
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
