<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class HallucinationMetric implements MetricInterface
{
    use ResolvesJudge;

    /**
     * @param float $threshold       Default threshold value is 0.9 (max 10% hallucination)
     * @param string|null $context   Optional reference document for hallucination check
     * @param JudgeInterface|null $judge Uses LLMJudge by default
     */
    public function __construct(
        private readonly float $threshold = 0.9,
        private readonly ?string $context = null,
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

        if ($ctx !== null && $ctx !== '') {
            $rubric = "Score the response for any hallucinated or fabricated claims not supported by the provided reference document.\n"
                . "1.0 = no hallucinated content detected; every claim is grounded.\n"
                . "0.0 = response is heavily hallucinated with claims not supported by the document.";
        } else {
            $rubric = "Score the response for any hallucinated or fabricated claims, facts, dates, names, or statistics based on general knowledge.\n"
                . "1.0 = no hallucinated or fabricated content detected.\n"
                . "0.0 = contains major fabricated facts or hallucinatory details.";
        }

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score('hallucination', $input, $output, $rubric, $ctx);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning,
            details: [
                'context_provided' => ($ctx !== null && $ctx !== ''),
            ]
        );
    }

    public function getName(): string
    {
        return 'Hallucination';
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
