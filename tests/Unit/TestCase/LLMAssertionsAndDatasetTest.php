<?php

namespace LLMesh\Eval\Tests\Unit\TestCase;

use LLMesh\Eval\Assertions\LLMAssertions;
use LLMesh\Eval\Contracts\MetricInterface;
use LLMesh\Eval\Results\MetricResult;
use LLMesh\Eval\TestCase\EvalDataset;
use PHPUnit\Framework\TestCase;

class LLMAssertionsAndDatasetTest extends TestCase
{
    use LLMAssertions;

    public function testAssertPassesEvalSuccessful(): void
    {
        $mResult = new MetricResult(1.0, 'Metric1', 0.8, true, 'Passed');
        $metric = $this->createMock(MetricInterface::class);
        $metric->method('measure')->willReturn($mResult);

        // This should not throw an exception (assertion passes)
        $this->assertPassesEval('output text', [$metric]);
    }

    public function testAssertPassesEvalFails(): void
    {
        $mResult = new MetricResult(0.5, 'Metric1', 0.8, false, 'Failed');
        $metric = $this->createMock(MetricInterface::class);
        $metric->method('measure')->willReturn($mResult);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Metric1: 0.50 ✗ (threshold: 0.80)');

        $this->assertPassesEval('output text', [$metric]);
    }

    public function testAssertMetricScoreSuccessful(): void
    {
        $mResult = new MetricResult(0.9, 'Metric1', 0.8, true, 'Passed');
        $metric = $this->createMock(MetricInterface::class);
        $metric->method('measure')->willReturn($mResult);
        $metric->method('getName')->willReturn('Metric1');

        $this->assertMetricScore('output text', $metric, 0.85);
    }

    public function testAssertMetricScoreFails(): void
    {
        $mResult = new MetricResult(0.7, 'Metric1', 0.8, false, 'Failed');
        $metric = $this->createMock(MetricInterface::class);
        $metric->method('measure')->willReturn($mResult);
        $metric->method('getName')->willReturn('Metric1');

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Expected Metric1 score >= 0.85, got 0.7');

        $this->assertMetricScore('output text', $metric, 0.85);
    }

    public function testEvalDatasetSavingAndLoading(): void
    {
        $tempFile = sys_get_temp_dir() . '/llmesh_eval_dataset_' . uniqid() . '.json';

        $dataset = EvalDataset::make('refund-dataset')
            ->add(
                input: 'Can I get a refund after 30 days?',
                expected: 'No refunds are allowed.',
                context: 'Refund policy: No refunds after 30 days.'
            );

        $dataset->saveAs($tempFile);

        $this->assertFileExists($tempFile);

        $loadedCases = EvalDataset::load($tempFile);
        $this->assertCount(1, $loadedCases);
        $this->assertSame('Can I get a refund after 30 days?', $loadedCases[0]->input);
        $this->assertSame('No refunds are allowed.', $loadedCases[0]->expected);
        $this->assertSame('Refund policy: No refunds after 30 days.', $loadedCases[0]->context);

        unlink($tempFile);
    }
}
