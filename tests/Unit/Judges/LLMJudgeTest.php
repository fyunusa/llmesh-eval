<?php

namespace LLMesh\Eval\Tests\Unit\Judges;

use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Contracts\ResponseInterface;
use LLMesh\Core\Contracts\UsageInterface;
use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Judges\LLMJudge;
use LLMesh\Eval\Metrics\FaithfulnessMetric;
use LLMesh\Eval\Metrics\HallucinationMetric;
use PHPUnit\Framework\TestCase;

class LLMJudgeTest extends TestCase
{
    private function makeResponse(string $text): ResponseInterface
    {
        $stub = $this->createStub(ResponseInterface::class);
        $stub->method('getText')->willReturn($text);

        $usageStub = $this->createStub(UsageInterface::class);
        $usageStub->method('getInputTokens')->willReturn(10);
        $usageStub->method('getOutputTokens')->willReturn(20);
        $stub->method('getUsage')->willReturn($usageStub);

        $stub->method('getRaw')->willReturn([]);
        return $stub;
    }

    public function testLLMJudgeScoresAndExtractsReasoning(): void
    {
        $json = '{"score":0.95,"reasoning":"The output accurately reflects the source document.","issues":[]}';

        $mockProvider = $this->createMock(ProviderInterface::class);
        $mockProvider->method('supports')->willReturn(false);
        $mockProvider->expects($this->once())
            ->method('chat')
            ->willReturn($this->makeResponse($json));

        $judge = new LLMJudge($mockProvider);
        $score = $judge->score(
            criterion: 'faithfulness',
            input: 'question',
            output: 'answer',
            rubric: 'rubric text',
            context: 'context text'
        );

        $this->assertSame(0.95, $score);
        $this->assertSame('The output accurately reflects the source document.', $judge->getReasoning());
    }

    public function testFaithfulnessMetricCallsJudgeWithCorrectParameters(): void
    {
        $mockJudge = $this->createMock(JudgeInterface::class);
        $mockJudge->expects($this->once())
            ->method('score')
            ->with(
                $this->equalTo('faithfulness'),
                $this->equalTo('what is the time?'),
                $this->equalTo('it is 5pm'),
                $this->stringContains('faithfully'),
                $this->equalTo('the clock says 5pm')
            )
            ->willReturn(0.9);
        $mockJudge->method('getReasoning')->willReturn('Verified');

        $metric = new FaithfulnessMetric(
            context: 'the clock says 5pm',
            threshold: 0.85,
            judge: $mockJudge
        );

        $result = $metric->measure(
            input: 'what is the time?',
            output: 'it is 5pm'
        );

        $this->assertTrue($result->passed);
        $this->assertSame(0.9, $result->score);
    }

    public function testHallucinationMetricChecksWithAndWithoutContext(): void
    {
        $mockJudge = $this->createMock(JudgeInterface::class);

        // Expected calls:
        // 1. Without context (uses general knowledge rubric)
        // 2. With context (uses groundedness checking rubric)
        $mockJudge->expects($this->exactly(2))
            ->method('score')
            ->willReturnCallback(function (
                string $criterion,
                string $input,
                string $output,
                string $rubric,
                ?string $context = null
            ) {
                if ($context === null) {
                    $this->assertStringContainsString('general knowledge', $rubric);
                } else {
                    $this->assertStringContainsString('reference document', $rubric);
                }
                return 0.95;
            });

        $mockJudge->method('getReasoning')->willReturn('Clean');

        // Test 1: Without context
        $metricNoCtx = new HallucinationMetric(threshold: 0.9, judge: $mockJudge);
        $resultNoCtx = $metricNoCtx->measure('question', 'answer');
        $this->assertTrue($resultNoCtx->passed);

        // Test 2: With context
        $metricWithCtx = new HallucinationMetric(threshold: 0.9, context: 'some context', judge: $mockJudge);
        $resultWithCtx = $metricWithCtx->measure('question', 'answer');
        $this->assertTrue($resultWithCtx->passed);
    }
}
