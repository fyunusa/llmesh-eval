<?php

namespace LLMesh\Eval\Results;

final class EvalReport
{
    /** @var array<string, EvalResult> */
    private array $results = [];

    /**
     * Add an evaluation result for a test case.
     */
    public function addResult(string $testName, EvalResult $result): void
    {
        $this->results[$testName] = $result;
    }

    /**
     * Get all results.
     *
     * @return array<string, EvalResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get the total number of tests.
     */
    public function totalTests(): int
    {
        return count($this->results);
    }

    /**
     * Get the number of passed tests.
     */
    public function passedTests(): int
    {
        return count(array_filter($this->results, fn(EvalResult $r) => $r->passed));
    }

    /**
     * Get the number of failed tests.
     */
    public function failedTests(): int
    {
        return $this->totalTests() - $this->passedTests();
    }

    /**
     * Get the overall pass rate (0.0 to 1.0).
     */
    public function passRate(): float
    {
        $total = $this->totalTests();
        return $total === 0 ? 0.0 : ($this->passedTests() / $total);
    }

    /**
     * Generate a machine-readable JSON report.
     */
    public function toJson(): string
    {
        $serializedResults = [];
        foreach ($this->results as $name => $result) {
            $serializedResults[$name] = $result->toArray();
        }

        return json_encode([
            'summary' => [
                'total_tests' => $this->totalTests(),
                'passed_tests' => $this->passedTests(),
                'failed_tests' => $this->failedTests(),
                'pass_rate' => $this->passRate(),
            ],
            'results' => $serializedResults,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Generate colored terminal output for CLI.
     */
    public function toCli(): string
    {
        $out = "";
        $out .= "\033[1mLLMesh AI Evaluation Report\033[0m\n";
        $out .= str_repeat('=', 40) . "\n";
        $out .= sprintf("Total Tests: %d\n", $this->totalTests());

        $passedColor = $this->passedTests() === $this->totalTests() ? "\033[32m" : "\033[33m";
        $out .= sprintf("Passed:      %s%d\033[0m\n", $passedColor, $this->passedTests());

        $failedColor = $this->failedTests() > 0 ? "\033[31m" : "\033[32m";
        $out .= sprintf("Failed:      %s%d\033[0m\n", $failedColor, $this->failedTests());

        $out .= sprintf("Pass Rate:   %.1f%%\n\n", $this->passRate() * 100);

        foreach ($this->results as $name => $result) {
            $status = $result->passed ? "\033[32m✓ PASS\033[0m" : "\033[31m✗ FAIL\033[0m";
            $out .= sprintf("%s - \033[1m%s\033[0m (%.2fms)\n", $status, $name, $result->durationMs);
            foreach ($result->metricResults as $metricResult) {
                $mStatus = $metricResult->passed ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
                $out .= sprintf("  %s %s: score %.2f (threshold: %.2f)\n", $mStatus, $metricResult->metricName, $metricResult->score, $metricResult->threshold);
                if ($metricResult->reasoning) {
                    $out .= sprintf("    \033[90mReasoning: %s\033[0m\n", $metricResult->reasoning);
                }
            }
            $out .= "\n";
        }
        return $out;
    }

    /**
     * Generate a beautiful, rich-aesthetics HTML report.
     */
    public function toHtml(): string
    {
        $total = $this->totalTests();
        $passed = $this->passedTests();
        $failed = $this->failedTests();
        $passRatePct = round($this->passRate() * 100, 1);

        $resultsHtml = '';
        foreach ($this->results as $name => $result) {
            $statusClass = $result->passed ? 'passed' : 'failed';
            $statusLabel = $result->passed ? 'PASSED' : 'FAILED';
            $statusBadge = $result->passed ? '✓' : '✗';

            $metricsHtml = '';
            foreach ($result->metricResults as $m) {
                $mStatusClass = $m->passed ? 'm-passed' : 'm-failed';
                $mStatusBadge = $m->passed ? '✓' : '✗';
                $detailsJson = !empty($m->details) ? htmlspecialchars(json_encode($m->details, JSON_PRETTY_PRINT)) : '';

                $detailsHtml = $detailsJson ? "<div class='metric-details-container'><pre><code>{$detailsJson}</code></pre></div>" : '';

                $metricsHtml .= "
                <div class='metric-row {$mStatusClass}'>
                    <div class='metric-header'>
                        <span class='metric-badge'>{$mStatusBadge}</span>
                        <span class='metric-name'>{$m->metricName}</span>
                        <span class='metric-score'>Score: <strong>{$m->score}</strong> (threshold: {$m->threshold})</span>
                    </div>
                    <div class='metric-reasoning'>{$m->reasoning}</div>
                    {$detailsHtml}
                </div>";
            }

            $inputEsc = htmlspecialchars($result->input);
            $outputEsc = htmlspecialchars($result->output);

            $resultsHtml .= "
            <div class='test-card {$statusClass}'>
                <div class='test-header'>
                    <div class='test-title-section'>
                        <span class='status-pill'>{$statusLabel}</span>
                        <h3 class='test-title'>{$name}</h3>
                    </div>
                    <div class='test-meta'>
                        <span>Duration: <strong>" . round($result->durationMs, 2) . "ms</strong></span>
                    </div>
                </div>
                <div class='test-io'>
                    <div class='io-block'>
                        <span class='io-label'>Input</span>
                        <pre class='io-content'><code>{$inputEsc}</code></pre>
                    </div>
                    <div class='io-block'>
                        <span class='io-label'>Output</span>
                        <pre class='io-content'><code>{$outputEsc}</code></pre>
                    </div>
                </div>
                <div class='test-metrics'>
                    <h4 class='metrics-title'>Metric Evaluations</h4>
                    {$metricsHtml}
                </div>
            </div>";
        }

        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>LLMesh AI Evaluation Report</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap' rel='stylesheet'>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.15);
            --danger: #ef4444;
            --danger-glow: rgba(239, 68, 68, 0.15);
            --border-color: #334155;
            --font-family: 'Outfit', sans-serif;
            --mono-font: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-family);
            line-height: 1.5;
            padding: 2rem 1.5rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
        }

        .header-title h1 {
            font-size: 2.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .timestamp {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-family: var(--mono-font);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary);
        }

        .stat-card.passed::before {
            background-color: var(--success);
        }

        .stat-card.failed::before {
            background-color: var(--danger);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
        }

        .stat-rate {
            color: var(--success);
        }
        .stat-rate.low-rate {
            color: var(--danger);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .test-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .test-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .test-card.passed {
            border-top: 4px solid var(--success);
        }

        .test-card.failed {
            border-top: 4px solid var(--danger);
        }

        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            background-color: rgba(15, 23, 42, 0.3);
            border-bottom: 1px solid var(--border-color);
        }

        .test-title-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            letter-spacing: 0.05em;
        }

        .passed .status-pill {
            background-color: var(--success-glow);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .failed .status-pill {
            background-color: var(--danger-glow);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .test-title {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .test-meta {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .test-io {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .test-io {
                grid-template-columns: 1fr;
            }
        }

        .io-block {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .io-block:first-child {
            border-right: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .io-block:first-child {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
        }

        .io-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .io-content {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
            max-height: 250px;
            font-family: var(--mono-font);
            font-size: 0.85rem;
            color: #e2e8f0;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .test-metrics {
            padding: 1.5rem;
        }

        .metrics-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .metric-row {
            background-color: rgba(15, 23, 42, 0.25);
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
        }

        .metric-row:last-child {
            margin-bottom: 0;
        }

        .metric-row.m-passed {
            border-left: 4px solid var(--success);
        }

        .metric-row.m-failed {
            border-left: 4px solid var(--danger);
        }

        .metric-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .metric-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .m-passed .metric-badge {
            background-color: var(--success-glow);
            color: var(--success);
        }

        .m-failed .metric-badge {
            background-color: var(--danger-glow);
            color: var(--danger);
        }

        .metric-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .metric-score {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-left: auto;
        }

        @media (max-width: 576px) {
            .metric-score {
                margin-left: 0;
                width: 100%;
            }
        }

        .metric-reasoning {
            font-size: 0.9rem;
            color: var(--text-muted);
            padding-left: 1.75rem;
        }

        .metric-details-container {
            margin-top: 0.75rem;
            padding-left: 1.75rem;
        }

        .metric-details-container pre {
            background-color: rgba(0, 0, 0, 0.25);
            padding: 0.75rem;
            border-radius: 0.35rem;
            font-family: var(--mono-font);
            font-size: 0.80rem;
            overflow-x: auto;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class='container'>
        <header>
            <div class='header-title'>
                <h1>LLMesh AI Evaluation</h1>
                <p>Automated regression and quality metrics report</p>
            </div>
            <div class='timestamp'>Generated at: " . date('Y-m-d H:i:s') . "</div>
        </header>

        <div class='stats-grid'>
            <div class='stat-card'>
                <div class='stat-label'>Total Tests</div>
                <div class='stat-value'>{$total}</div>
            </div>
            <div class='stat-card passed'>
                <div class='stat-label'>Passed</div>
                <div class='stat-value' style='color: var(--success)'>{$passed}</div>
            </div>
            <div class='stat-card failed'>
                <div class='stat-label'>Failed</div>
                <div class='stat-value' style='color: var(--danger)'>{$failed}</div>
            </div>
            <div class='stat-card'>
                <div class='stat-label'>Pass Rate</div>
                <div class='stat-value stat-rate " . ($passRatePct < 80 ? 'low-rate' : '') . "'>{$passRatePct}%</div>
            </div>
        </div>

        <h2 class='section-title'>Test Case Breakdowns</h2>
        
        <div class='test-list'>
            {$resultsHtml}
        </div>
    </div>
</body>
</html>";
    }
}
