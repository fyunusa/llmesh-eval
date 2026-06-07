<?php

namespace LLMesh\Eval\Contracts;

use LLMesh\Eval\Results\MetricResult;

interface MetricInterface
{
    /**
     * Measure the quality of an LLM response.
     *
     * @param string      $input    The original prompt/question sent to the LLM
     * @param string      $output   The LLM's response text
     * @param string|null $context  Optional reference document for grounding checks
     * @param string|null $expected Optional expected/ground-truth answer
     *
     * @return MetricResult Score between 0.0 and 1.0 with reasoning
     */
    public function measure(
        string $input,
        string $output,
        ?string $context = null,
        ?string $expected = null
    ): MetricResult;

    public function getName(): string;

    public function getThreshold(): float;

    public function passes(MetricResult $result): bool;
}
