<?php

namespace LLMesh\Eval\Tests\Unit\Metrics;

use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Metrics\CustomMetric;
use PHPUnit\Framework\TestCase;

class CustomMetricTest extends TestCase
{
    public function testValidatorClosureCalledBeforeJudgeAndCanShortCircuit(): void
    {
        $mockJudge = $this->createMock(JudgeInterface::class);
        // Judge should NOT be called if validator returns false
        $mockJudge->expects($this->never())->method('score');

        $metric = CustomMetric::make('test-custom')
            ->withThreshold(0.8)
            ->withJudge($mockJudge)
            ->withValidator(function (string $output) {
                return str_contains($output, '$');
            });

        // Output lacks '$', validator returns false
        $result = $metric->measure(input: '', output: 'no dollar sign here');
        $this->assertFalse($result->passed);
        $this->assertSame(0.0, $result->score);
        $this->assertSame('Custom validator failed pre-check.', $result->reasoning);
    }

    public function testJudgeCalledIfValidatorPasses(): void
    {
        $mockJudge = $this->createMock(JudgeInterface::class);
        $mockJudge->expects($this->once())
            ->method('score')
            ->willReturn(0.9);
        $mockJudge->method('getReasoning')->willReturn('Output is correct');

        $metric = CustomMetric::make('test-custom')
            ->withThreshold(0.8)
            ->withJudge($mockJudge)
            ->withValidator(function (string $output) {
                return str_contains($output, '$');
            });

        $result = $metric->measure(input: '', output: 'has $ signs');
        $this->assertTrue($result->passed);
        $this->assertSame(0.9, $result->score);
        $this->assertSame('Output is correct', $result->reasoning);
    }
}
