<?php

namespace LLMesh\Eval\Assertions;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Runner\EvalRunner;

trait LLMAssertions
{
    /**
     * Assert that the LLM response passes all provided evaluation metrics.
     *
     * @param mixed $response       Response string or object (e.g. TextResponse)
     * @param MetricInterface[] $metrics
     * @param string $message
     */
    protected function assertPassesEval(
        mixed $response,
        array $metrics,
        string $message = ''
    ): void {
        $outputText = $this->resolveOutputText($response);

        $result = EvalRunner::make()
            ->withMetrics($metrics)
            ->evaluate(
                input: '',
                output: $outputText
            );

        if (!$result->passed) {
            $summary = implode("\n", array_map(
                fn($m) => "  - {$m->formatted()}",
                $result->failedMetrics()
            ));

            $failMessage = ($message !== '' ? "{$message}\n" : '') . "AI eval failed:\n{$summary}";
            static::fail($failMessage);
        }

        // Keep PHPUnit happy by executing a successful assertion
        static::assertTrue(true);
    }

    /**
     * Assert that the LLM response meets a minimum score on a specific metric.
     *
     * @param mixed $response
     * @param MetricInterface $metric
     * @param float $minimumScore
     * @param string $message
     */
    protected function assertMetricScore(
        mixed $response,
        MetricInterface $metric,
        float $minimumScore,
        string $message = ''
    ): void {
        $outputText = $this->resolveOutputText($response);

        $result = EvalRunner::make()
            ->withMetrics([$metric])
            ->evaluate(
                input: '',
                output: $outputText
            );

        $metricResult = $result->metricResults[0];

        $failMessage = $message !== ''
            ? $message
            : "Expected {$metric->getName()} score >= {$minimumScore}, got {$metricResult->score}. Reasoning: {$metricResult->reasoning}";

        static::assertGreaterThanOrEqual(
            $minimumScore,
            $metricResult->score,
            $failMessage
        );
    }

    /**
     * Resolve output text from a string or response object.
     */
    private function resolveOutputText(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        if (is_object($response)) {
            if (method_exists($response, 'getText')) {
                return $response->getText();
            }
            if (method_exists($response, 'toString')) {
                return $response->toString();
            }
            if ($response instanceof \Stringable || method_exists($response, '__toString')) {
                return (string) $response;
            }
            // Check if there is an 'object' or similar property
            if (isset($response->object)) {
                return is_string($response->object) ? $response->object : json_encode($response->object);
            }
        }

        return (string) $response;
    }
}
