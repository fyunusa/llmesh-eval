# LLMesh Evals — AI Output Testing Framework

LLMesh Evals is a Pest/PHPUnit-native AI output testing framework. It allows PHP developers to write test cases for LLM responses with the same confidence they write regular PHP unit and integration tests.

Designed as a standalone component with zero runtime framework dependencies, `llmesh/eval` integrates directly into your testing workflows.

---

## Features

- **Pest & PHPUnit Native**: Write tests using Pest expectations or PHPUnit assertions.
- **8 Built-in Metrics**:
  - `FaithfulnessMetric`: Measures grounding of LLM response against a reference document.
  - `AnswerRelevancyMetric`: Measures how well the output addresses the original user prompt.
  - `HallucinationMetric`: Detects fabricated claims against context or general knowledge.
  - `ToxicityMetric`: Checks for offensive, toxic, or unsafe content.
  - `ContainsMetric`: Fast, deterministic substring/regex keyword lookup.
  - `ContextualPrecisionMetric`: Evaluates the relevance of retrieved RAG contexts.
  - `ContextualRecallMetric`: Checks if retrieved contexts contain the ground-truth answers.
  - `CustomMetric`: A fluent builder to create your own domain-specific evaluation criteria.
- **LLM-as-Judge & Heuristics**: Combines deterministic logic with structured LLM scoring via `LLMesh::generateObject()`.
- **Rich Reports**: Generates detailed evaluation reports in HTML, colored CLI, or JSON formats.

---

## Installation

Install the package via Composer:

```bash
composer require llmesh/eval --dev
```

---

## Usage

### 1. Register a Default Judge

In your test bootstrap file (e.g. `tests/Pest.php` or your PHPUnit base `TestCase::setUp()`), register the default judge for evaluation metrics to use:

```php
use LLMesh\Eval\Runner\EvalRunner;
use LLMesh\Eval\Judges\LLMJudge;
use LLMesh\OpenAI\OpenAIProvider;

$provider = new OpenAIProvider(getenv('OPENAI_API_KEY'));
EvalRunner::setDefaultJudge(new LLMJudge($provider, 'gpt-4o'));
```

### 2. Pest Syntax

Use the `toPassEval` expectation in your Pest test cases:

```php
use LLMesh\Eval\Metrics\FaithfulnessMetric;
use LLMesh\Eval\Metrics\AnswerRelevancyMetric;
use LLMesh\Eval\Metrics\ToxicityMetric;

it('answers refund questions faithfully', function () use ($provider) {
    $result = LLMesh::make()->generateText($provider,
        GenerateTextOptions::make()->withPrompt('What is the refund policy?')
    );

    expect($result)->toPassEval([
        new FaithfulnessMetric(context: 'Our refund window is 30 days.', threshold: 0.9),
        new AnswerRelevancyMetric(threshold: 0.8),
        new ToxicityMetric(threshold: 0.95),
    ]);
});
```

### 3. PHPUnit Syntax

Use the `LLMAssertions` trait in your PHPUnit tests:

```php
use PHPUnit\Framework\TestCase;
use LLMesh\Eval\Assertions\LLMAssertions;
use LLMesh\Eval\Metrics\FaithfulnessMetric;

class CustomerSupportTest extends TestCase
{
    use LLMAssertions;

    public function test_refund_answer_is_faithful(): void
    {
        $result = LLMesh::make()->generateText($provider, $options);

        $this->assertPassesEval($result, [
            new FaithfulnessMetric(context: 'Our refund window is 30 days.', threshold: 0.9),
        ]);
        
        // Or assert minimum score on a single metric
        $this->assertMetricScore($result, new FaithfulnessMetric(context: $doc), 0.95);
    }
}
```

### 4. Custom Domain-Specific Metrics

Create custom evaluation logic using `CustomMetric`:

```php
use LLMesh\Eval\Metrics\CustomMetric;

$pricingMetric = CustomMetric::make('pricing-accuracy')
    ->withThreshold(0.9)
    ->withRubric('Score 1.0 if the AI quotes the correct price ($29.99), 0.0 otherwise.')
    ->withValidator(fn (string $output) => str_contains($output, '$'));
```

---

## Evaluation Datasets

For running evaluations across structured testing datasets:

```php
use LLMesh\Eval\TestCase\EvalDataset;

EvalDataset::make('refund-dataset')
    ->add(
        input: 'Can I return after 15 days?',
        expected: 'Yes, refund is valid for 30 days.',
        context: 'Refund policy: 30-day return window.'
    )
    ->saveAs('tests/datasets/refund.json');

// In test:
it('handles all refund dataset cases')
    ->with(EvalDataset::load('tests/datasets/refund.json'))
    ->each(function ($case) {
        $result = LLMesh::make()->generateText($provider,
            GenerateTextOptions::make()->withPrompt($case->input)
        );
        expect($result)->toPassEval([
            new FaithfulnessMetric(context: $case->context),
        ]);
    });
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
