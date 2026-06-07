<?php

namespace LLMesh\Eval\Judges;

use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\LLMesh;
use LLMesh\Core\Generators\GenerateObjectOptions;
use LLMesh\Core\Schema\Schema;
use LLMesh\Eval\Contracts\JudgeInterface;

class LLMJudge implements JudgeInterface
{
    private string $lastReasoning = '';

    /**
     * @param ProviderInterface $judgeProvider
     * @param string $judgeModel
     */
    public function __construct(
        private readonly ProviderInterface $judgeProvider,
        private readonly string $judgeModel = 'gpt-4o'
    ) {
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        return $this->judgeProvider;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->judgeModel;
    }

    /**
     * Score the output quality using the LLM-as-judge pattern.
     *
     * @param string $criterion  What to evaluate (e.g. 'faithfulness', 'relevancy')
     * @param string $input      Original prompt
     * @param string $output     LLM response
     * @param string $rubric     Scoring rubric instructions for the judge
     * @param string|null $context Reference document if needed
     *
     * @return float Score between 0.0 and 1.0
     */
    public function score(
        string $criterion,
        string $input,
        string $output,
        string $rubric,
        ?string $context = null
    ): float {
        $prompt = "You are an impartial AI evaluator. Score the following AI response on the criterion: {$criterion}.\n\n"
            . "Scoring rubric:\n{$rubric}\n\n"
            . "Original question: {$input}\n";

        if ($context !== null && $context !== '') {
            $prompt .= "Reference document: {$context}\n";
        }

        $prompt .= "AI response to evaluate: {$output}\n\n"
            . "Return a JSON object with:\n"
            . "- \"score\": float between 0.0 and 1.0\n"
            . "- \"reasoning\": one paragraph explanation of the score\n"
            . "- \"issues\": array of specific problems found (empty if none)\n";

        $schema = Schema::object([
            'score' => Schema::number()->required()->description('float between 0.0 and 1.0'),
            'reasoning' => Schema::string()->required()->description('one paragraph explanation of the score'),
            'issues' => Schema::array(Schema::string())->required()->description('array of specific problems found (empty if none)'),
        ])->required(['score', 'reasoning', 'issues']);

        $options = GenerateObjectOptions::make()
            ->withPrompt($prompt)
            ->withSchema($schema);

        $response = LLMesh::make()->generateObject($this->judgeProvider, $options);

        $data = $response->object;
        $this->lastReasoning = $data['reasoning'] ?? '';

        return (float) ($data['score'] ?? 0.0);
    }

    /**
     * Get the reasoning of the last evaluation.
     */
    public function getReasoning(): string
    {
        return $this->lastReasoning;
    }
}
