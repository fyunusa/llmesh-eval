<?php

namespace LLMesh\Eval\TestCase;

use IteratorAggregate;
use Traversable;
use ArrayIterator;

class EvalDataset implements IteratorAggregate
{
    /** @var EvalDatasetCase[] */
    private array $cases = [];

    /**
     * Constructor.
     */
    public function __construct(private readonly string $name)
    {
    }

    /**
     * Create a new dataset.
     */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * Add a case to the dataset.
     */
    public function add(
        string $input,
        ?string $expected = null,
        ?string $context = null,
        array $metadata = []
    ): self {
        $this->cases[] = new EvalDatasetCase($input, $expected, $context, $metadata);
        return $this;
    }

    /**
     * Get all cases.
     *
     * @return EvalDatasetCase[]
     */
    public function getCases(): array
    {
        return $this->cases;
    }

    /**
     * Save the dataset to a JSON file.
     */
    public function saveAs(string $path): self
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $serialized = array_map(fn(EvalDatasetCase $c) => [
            'input' => $c->input,
            'expected' => $c->expected,
            'context' => $c->context,
            'metadata' => $c->metadata,
        ], $this->cases);

        file_put_contents($path, json_encode([
            'name' => $this->name,
            'cases' => $serialized,
        ], JSON_PRETTY_PRINT));

        return $this;
    }

    /**
     * Load cases from a JSON file.
     *
     * @return EvalDatasetCase[]
     */
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Dataset file not found at: {$path}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Malformed dataset JSON at: {$path}");
        }

        $cases = [];
        $items = $data['cases'] ?? $data;

        foreach ($items as $item) {
            $cases[] = new EvalDatasetCase(
                input: $item['input'] ?? '',
                expected: $item['expected'] ?? null,
                context: $item['context'] ?? null,
                metadata: $item['metadata'] ?? []
            );
        }

        return $cases;
    }

    /**
     * Implement IteratorAggregate.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cases);
    }
}
