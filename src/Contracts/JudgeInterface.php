<?php

namespace LLMesh\Eval\Contracts;

interface JudgeInterface
{
    /**
     * Use an LLM or heuristic to score output quality on a given criterion.
     *
     * @param string $criterion  What to evaluate (e.g. 'faithfulness', 'relevancy')
     * @param string $input      Original prompt
     * @param string $output     LLM response
     * @param string $rubric     Scoring rubric instructions for the judge
     * @param string|null $context Reference document if needed
     *
     * @return float Score between 0.0 and 1.0
     */
    public function score(
        string $criterion,
        string $input,
        string $output,
        string $rubric,
        ?string $context = null
    ): float;

    public function getReasoning(): string;
}
