<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\MetricResult;

class CustomMetric implements MetricInterface
{
    use ResolvesJudge;

    private float $threshold = 0.8;
    private ?JudgeInterface $judge = null;
    private string $rubric = '';

    /** @var callable|null */
    private $validator = null;

    /**
     * Private constructor — use factory method `make()`.
     */
    private function __construct(private readonly string $name)
    {
    }

    /**
     * Create a new CustomMetric instance fluently.
     */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * Set the threshold.
     */
    public function withThreshold(float $threshold): self
    {
        $this->threshold = $threshold;
        return $this;
    }

    /**
     * Set the judge.
     */
    public function withJudge(JudgeInterface $judge): self
    {
        $this->judge = $judge;
        return $this;
    }

    /**
     * Set the rubric.
     */
    public function withRubric(string $rubric): self
    {
        $this->rubric = $rubric;
        return $this;
    }

    /**
     * Set the validator callback for a fast deterministic pre-check.
     */
    public function withValidator(callable $validator): self
    {
        $this->validator = $validator;
        return $this;
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
        // Run precheck validator
        if ($this->validator !== null) {
            $valid = ($this->validator)($output);
            if (!$valid) {
                return new MetricResult(
                    score: 0.0,
                    metricName: $this->getName(),
                    threshold: $this->threshold,
                    passed: false,
                    reasoning: 'Custom validator failed pre-check.'
                );
            }
        }

        $judge = $this->resolveJudge($this->judge);
        $score = $judge->score($this->name, $input, $output, $this->rubric, $context);
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
        return $this->name;
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
