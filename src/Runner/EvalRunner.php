<?php

namespace LLMesh\Eval\Runner;

use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Results\EvalResult;

class EvalRunner
{
    /** @var array */
    private array $metrics = [];

    /** @var JudgeInterface|null */
    private ?JudgeInterface $defaultJudge = null;

    /** @var JudgeInterface|null */
    private static ?JudgeInterface $globalDefaultJudge = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Create a new instance of EvalRunner.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set metrics for the runner.
     */
    public function withMetrics(array $metrics): self
    {
        $this->metrics = $metrics;
        return $this;
    }

    /**
     * Set instance-level default judge.
     */
    public function withDefaultJudge(JudgeInterface $judge): self
    {
        $this->defaultJudge = $judge;
        return $this;
    }

    /**
     * Set the global default judge.
     */
    public static function setDefaultJudge(JudgeInterface $judge): void
    {
        self::$globalDefaultJudge = $judge;
    }

    /**
     * Get the active global default judge.
     */
    public static function getDefaultJudge(): ?JudgeInterface
    {
        return self::$globalDefaultJudge;
    }

    /**
     * Run evaluation of metrics.
     */
    public function evaluate(
        string $input,
        string $output,
        ?string $context = null,
        ?string $expected = null
    ): EvalResult {
        $metricResults = [];
        $allPassed = true;

        $start = hrtime(true);

        // Backup current global default judge and temporarily override it with instance default judge if present
        $previousGlobalDefault = self::$globalDefaultJudge;
        if ($this->defaultJudge !== null) {
            self::$globalDefaultJudge = $this->defaultJudge;
        }

        try {
            foreach ($this->metrics as $metric) {
                $result = $metric->measure($input, $output, $context, $expected);
                $metricResults[] = $result;

                if (!$result->passed) {
                    $allPassed = false;
                }
            }
        } finally {
            // Restore original global default judge
            self::$globalDefaultJudge = $previousGlobalDefault;
        }

        $durationMs = (hrtime(true) - $start) / 1000000;

        return new EvalResult(
            input: $input,
            output: $output,
            metricResults: $metricResults,
            passed: $allPassed,
            durationMs: $durationMs
        );
    }
}
