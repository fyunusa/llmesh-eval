<?php

namespace LLMesh\Eval\Tests\Unit\Metrics;

use LLMesh\Eval\Metrics\ContainsMetric;
use PHPUnit\Framework\TestCase;

class ContainsMetricTest extends TestCase
{
    public function testExactMatchPassesAndScoresCorrectly(): void
    {
        $metric = new ContainsMetric(expected: 'hello', caseSensitive: true);
        $result = $metric->measure(input: '', output: 'hello world');

        $this->assertTrue($result->passed);
        $this->assertSame(1.0, $result->score);
        $this->assertSame('Contains', $result->metricName);

        $resultFailed = $metric->measure(input: '', output: 'world');
        $this->assertFalse($resultFailed->passed);
        $this->assertSame(0.0, $resultFailed->score);
    }

    public function testCaseInsensitiveMatches(): void
    {
        $metricSensitive = new ContainsMetric(expected: 'HELLO', caseSensitive: true);
        $resultSensitive = $metricSensitive->measure(input: '', output: 'hello world');
        $this->assertFalse($resultSensitive->passed);
        $this->assertSame(0.0, $resultSensitive->score);

        $metricInsensitive = new ContainsMetric(expected: 'HELLO', caseSensitive: false);
        $resultInsensitive = $metricInsensitive->measure(input: '', output: 'hello world');
        $this->assertTrue($resultInsensitive->passed);
        $this->assertSame(1.0, $resultInsensitive->score);
    }

    public function testRegexMatches(): void
    {
        $metric = new ContainsMetric(expected: '/^hello/i', caseSensitive: false, regex: true);
        $result = $metric->measure(input: '', output: 'Hello World');
        $this->assertTrue($result->passed);
        $this->assertSame(1.0, $result->score);

        $resultFailed = $metric->measure(input: '', output: 'say Hello');
        $this->assertFalse($resultFailed->passed);
        $this->assertSame(0.0, $resultFailed->score);
    }
}
