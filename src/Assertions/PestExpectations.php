<?php

use LLMesh\Eval\Runner\EvalRunner;

if (class_exists(\Pest\Expectation::class) && function_exists('expect')) {
    expect()->extend('toPassEval', function (array $metrics) {
        $input = '';
        if (is_object($this->value)) {
            if (method_exists($this->value, 'getOptions')) {
                $input = $this->value->getOptions()->prompt ?? '';
            }
        }

        $output = '';
        if (is_string($this->value)) {
            $output = $this->value;
        } elseif (is_object($this->value) && method_exists($this->value, 'getText')) {
            $output = $this->value->getText();
        } else {
            $output = (string) $this->value;
        }

        $result = EvalRunner::make()
            ->withMetrics($metrics)
            ->evaluate(
                input: $input,
                output: $output
            );

        if (!$result->passed) {
            $failedSummary = implode("\n", array_map(
                fn($m) => "  - {$m->formatted()}",
                $result->failedMetrics()
            ));

            throw new \Exception(
                "AI eval failed:\n$failedSummary"
            );
        }

        return $this;
    });
}
