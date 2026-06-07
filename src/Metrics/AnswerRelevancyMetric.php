<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class AnswerRelevancyMetric implements MetricInterface
{
    use ResolvesJudge;

    /**
     * @param float $threshold       Default threshold value is 0.8
     * @param JudgeInterface|null $judge Uses LLMJudge by default
     */
    public function __construct(
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
        $rubric = "Score how relevant the AI response is to the original question.\n"
            . "1.0 = the response directly and completely answers the question without irrelevant info.\n"
            . "0.0 = the response is completely off-topic or doesn't address the question at all.";

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score('relevancy', $input, $output, $rubric, $context);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning
        );
    }

    public function getName(): string
    {
        return 'AnswerRelevancy';
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
