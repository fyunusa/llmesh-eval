<?php

namespace LLMesh\Eval\Tests\Unit\Runner;

use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Results\MetricResult;
use LLMesh\Eval\Runner\EvalRunner;
use PHPUnit\Framework\TestCase;

class EvalRunnerTest extends TestCase
{
    public function testEvaluateRunsAllMetricsAndAggregates(): void
    {
        $m1Result = new MetricResult(1.0, 'Metric1', 0.8, true, 'Reason1');
        $m1 = $this->createMock(MetricInterface::class);
        $m1->method('measure')->willReturn($m1Result);

        $m2Result = new MetricResult(0.9, 'Metric2', 0.8, true, 'Reason2');
        $m2 = $this->createMock(MetricInterface::class);
        $m2->method('measure')->willReturn($m2Result);

        $runner = EvalRunner::make()->withMetrics([$m1, $m2]);
        $result = $runner->evaluate('input', 'output');

        $this->assertTrue($result->passed);
        $this->assertCount(2, $result->metricResults);
        $this->assertSame($m1Result, $result->metricResults[0]);
        $this->assertSame($m2Result, $result->metricResults[1]);
    }

    public function testEvaluateFailsIfAnyMetricFails(): void
    {
        $m1Result = new MetricResult(1.0, 'Metric1', 0.8, true, 'Reason1');
        $m1 = $this->createMock(MetricInterface::class);
        $m1->method('measure')->willReturn($m1Result);

        $m2Result = new MetricResult(0.5, 'Metric2', 0.8, false, 'Reason2');
        $m2 = $this->createMock(MetricInterface::class);
        $m2->method('measure')->willReturn($m2Result);

        $runner = EvalRunner::make()->withMetrics([$m1, $m2]);
        $result = $runner->evaluate('input', 'output');

        $this->assertFalse($result->passed);
        $this->assertCount(2, $result->metricResults);
    }
}
