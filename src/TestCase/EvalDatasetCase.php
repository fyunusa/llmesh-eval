<?php

namespace LLMesh\Eval\TestCase;

final readonly class EvalDatasetCase
{
    /**
     * @param string $input
     * @param string|null $expected
     * @param string|null $context
     * @param array $metadata
     */
    public function __construct(
        public string $input,
        public ?string $expected = null,
        public ?string $context = null,
        public array $metadata = []
    ) {
    }
}
