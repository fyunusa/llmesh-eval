<?php

namespace LLMesh\Eval\Judges;

use LLMesh\Eval\Contracts\JudgeInterface;

class HeuristicJudge implements JudgeInterface
{
    private string $reasoning = '';

    /**
     * Score the output using fast, deterministic logic (no LLM).
     *
     * @param string $criterion  What to evaluate (e.g. 'contains')
     * @param string $input      Original prompt
     * @param string $output     LLM response
     * @param string $rubric     Scoring rubric instructions or options (JSON/string)
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
    ): float {
        $this->reasoning = '';

        if ($criterion === 'contains') {
            $options = [];
            if (str_starts_with(trim($rubric), '{')) {
                $options = json_decode($rubric, true) ?? [];
            } else {
                $options['expected'] = $rubric;
            }

            $expected = $options['expected'] ?? '';
            $caseSensitive = $options['case_sensitive'] ?? false;
            $regex = $options['regex'] ?? false;

            if ($expected === '') {
                $this->reasoning = 'No expected string provided for contains check.';
                return 1.0;
            }

            if ($regex) {
                $matched = @preg_match($expected, $output);
                if ($matched === false) {
                    $this->reasoning = "Invalid regex pattern: '{$expected}'";
                    return 0.0;
                }
                if ($matched > 0) {
                    $this->reasoning = "Output matches regex pattern '{$expected}'.";
                    return 1.0;
                }
                $this->reasoning = "Output does not match regex pattern '{$expected}'.";
                return 0.0;
            }

            $pos = $caseSensitive ? strpos($output, $expected) : stripos($output, $expected);

            if ($pos !== false) {
                $this->reasoning = "Output contains expected string '{$expected}'.";
                return 1.0;
            }

            $this->reasoning = "Output does not contain expected string '{$expected}'.";
            return 0.0;
        }

        // Generic fallback heuristic
        $this->reasoning = "Criterion '{$criterion}' evaluated via default heuristic.";
        return 1.0;
    }

    /**
     * Get the reasoning of the last evaluation.
     */
    public function getReasoning(): string
    {
        return $this->reasoning;
    }
}
