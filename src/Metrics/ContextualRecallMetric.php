<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class ContextualRecallMetric implements MetricInterface
{
    use ResolvesJudge;

    /**
     * @param string[] $retrievedContexts Array of retrieved context chunks
     * @param string $expectedAnswer      Expected ground truth answer
     * @param float $threshold            Default threshold value is 0.7
     * @param JudgeInterface|null $judge  Uses LLMJudge by default
     */
    public function __construct(
        private readonly array $retrievedContexts,
        private readonly string $expectedAnswer,
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
        $expectedAnswerVal = $expected ?? $this->expectedAnswer;

        $rubric = "Expected ground truth answer: {$expectedAnswerVal}\n\n"
            . "Score how much of the expected ground truth answer is present in/recalled by the retrieved context.\n"
            . "1.0 = the retrieved context contains all the necessary information to construct the expected answer.\n"
            . "0.0 = the retrieved context does not contain any of the information needed for the expected answer.";

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score('contextual_recall', $input, $output, $rubric, $ctx);
        $reasoning = $judge->getReasoning();
        $passed = $score >= $this->threshold;

        return new MetricResult(
            score: $score,
            metricName: $this->getName(),
            threshold: $this->threshold,
            passed: $passed,
            reasoning: $reasoning,
            details: [
                'expected_answer' => $expectedAnswerVal,
                'chunks_count' => count($this->retrievedContexts),
            ]
        );
    }

    public function getName(): string
    {
        return 'ContextualRecall';
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
