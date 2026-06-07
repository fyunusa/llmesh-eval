<?php

namespace LLMesh\Eval\Tests\Unit\Results;

use LLMesh\Eval\Results\MetricResult;
use LLMesh\Eval\Results\EvalResult;
use LLMesh\Eval\Results\EvalReport;
use PHPUnit\Framework\TestCase;

class EvalReportTest extends TestCase
{
    public function testMetricResultFormattedString(): void
    {
        $passed = new MetricResult(
            score: 0.92,
            metricName: 'Faithfulness',
            threshold: 0.90,
            passed: true,
            reasoning: 'Grounded'
        );

        $failed = new MetricResult(
            score: 0.50,
            metricName: 'Faithfulness',
            threshold: 0.90,
            passed: false,
            reasoning: 'Hallucinated'
        );

        $this->assertSame('Faithfulness: 0.92 ✓ (threshold: 0.90)', $passed->formatted());
        $this->assertSame('Faithfulness: 0.50 ✗ (threshold: 0.90)', $failed->formatted());
    }

    public function testEvalResultAggregations(): void
    {
        $m1 = new MetricResult(1.0, 'Metric1', 0.8, true, '');
        $m2 = new MetricResult(0.5, 'Metric2', 0.8, false, '');

        $resultFailed = new EvalResult(
            input: 'in',
            output: 'out',
            metricResults: [$m1, $m2],
            passed: false,
            durationMs: 10.0
        );

        $resultPassed = new EvalResult(
            input: 'in',
            output: 'out',
            metricResults: [$m1],
            passed: true,
            durationMs: 5.0
        );

        $this->assertFalse($resultFailed->passed);
        $this->assertCount(1, $resultFailed->failedMetrics());
        $this->assertSame($m2, $resultFailed->failedMetrics()[0]);

        $this->assertTrue($resultPassed->passed);
        $this->assertEmpty($resultPassed->failedMetrics());
    }

    public function testEvalReportStatisticsAndRates(): void
    {
        $report = new EvalReport();

        $r1 = new EvalResult('in', 'out', [], true, 1.0);
        $r2 = new EvalResult('in2', 'out2', [], false, 2.0);

        $report->addResult('test_1', $r1);
        $report->addResult('test_2', $r2);

        $this->assertSame(2, $report->totalTests());
        $this->assertSame(1, $report->passedTests());
        $this->assertSame(1, $report->failedTests());
        $this->assertSame(0.5, $report->passRate());
    }
}
