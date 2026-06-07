<?php

namespace LLMesh\Eval\Metrics;

use LLMesh\Eval\Contracts\JudgeInterface;
use LLMesh\Eval\Runner\EvalRunner;

trait ResolvesJudge
{
    /**
     * Resolve the judge instance to use.
     *
     * @param JudgeInterface|null $injectedJudge
     * @return JudgeInterface
     * @throws \RuntimeException
     */
    protected function resolveJudge(?JudgeInterface $injectedJudge = null): JudgeInterface
    {
        $judge = $injectedJudge ?? EvalRunner::getDefaultJudge();
        if (!$judge) {
            throw new \RuntimeException(
                'No judge provided for metric evaluation. You must either pass a JudgeInterface to the metric constructor '
                . 'or register a global default judge via EvalRunner::setDefaultJudge().'
            );
        }
        return $judge;
    }
}
