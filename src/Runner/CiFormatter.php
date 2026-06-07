<?php

namespace LLMesh\Eval\Runner;

use LLMesh\Eval\Results\EvalReport;

class CiFormatter
{
    /**
     * Format an EvalReport for CI logs (clean text format without ANSI escape codes).
     */
    public static function format(EvalReport $report): string
    {
        $out = "LLMesh AI Evaluation - CI Summary\n";
        $out .= "=================================\n";
        $out .= sprintf(
            "Total Tests: %d | Passed: %d | Failed: %d | Pass Rate: %.1f%%\n\n",
            $report->totalTests(),
            $report->passedTests(),
            $report->failedTests(),
            $report->passRate() * 100
        );

        foreach ($report->getResults() as $name => $result) {
            $status = $result->passed ? 'PASS' : 'FAIL';
            $out .= sprintf("[%s] %s (%.2fms)\n", $status, $name, $result->durationMs);
            foreach ($result->metricResults as $m) {
                $mStatus = $m->passed ? 'PASS' : 'FAIL';
                $out .= sprintf(
                    "  - %s: %s (Score: %.2f, Threshold: %.2f)\n",
                    $m->metricName,
                    $mStatus,
                    $m->score,
                    $m->threshold
                );
                if ($m->reasoning) {
                    $out .= sprintf("    Reasoning: %s\n", $m->reasoning);
                }
            }
            $out .= "\n";
        }
        return rtrim($out) . "\n";
    }
}
