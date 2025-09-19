<?php

namespace BaseApi\Debug;

use BaseApi\App;

class DebugPanel
{
    private bool $enabled = false;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if debug panel should be displayed
     */
    public function shouldDisplay(): bool
    {
        return $this->enabled && App::config('app.env') === 'local';
    }

    /**
     * Render HTML debug panel
     */
    public function renderPanel(): string
    {
        if (!$this->shouldDisplay()) {
            return '';
        }

        $profiler = App::profiler();
        $summary = $profiler->getSummary();
        
        if (empty($summary)) {
            return '';
        }

        $html = $this->renderPanelStyles();
        $html .= '<div id="baseapi-debug-panel">';
        $html .= $this->renderRequestInfo($summary);
        $html .= $this->renderQueries($summary);
        $html .= $this->renderMemory($summary);
        $html .= $this->renderExceptions($summary);
        $html .= $this->renderWarnings($summary);
        $html .= '</div>';

        return $html;
    }

    /**
     * Get debug metrics as JSON
     */
    public function getMetrics(): array
    {
        if (!$this->shouldDisplay()) {
            return [];
        }

        $profiler = App::profiler();
        return $profiler->getSummary();
    }

    /**
     * Render CSS styles for the debug panel
     */
    private function renderPanelStyles(): string
    {
        return '
        <style>
        #baseapi-debug-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2d3748;
            color: #e2e8f0;
            font-family: "Monaco", "Menlo", "Ubuntu Mono", monospace;
            font-size: 12px;
            line-height: 1.4;
            z-index: 9999;
            max-height: 300px;
            overflow-y: auto;
            border-top: 3px solid #4299e1;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .debug-section {
            padding: 12px 16px;
            border-bottom: 1px solid #4a5568;
        }
        .debug-section:last-child {
            border-bottom: none;
        }
        .debug-section h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: #63b3ed;
        }
        .debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .debug-metric {
            background: #1a202c;
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #48bb78;
        }
        .debug-metric.warning {
            border-left-color: #ed8936;
        }
        .debug-metric.error {
            border-left-color: #f56565;
        }
        .debug-query {
            background: #1a202c;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            border-left: 3px solid #4299e1;
        }
        .debug-query.slow {
            border-left-color: #ed8936;
        }
        .debug-query-sql {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .debug-query-meta {
            font-size: 11px;
            color: #a0aec0;
        }
        .debug-warning {
            background: #744210;
            color: #faf089;
            padding: 6px 8px;
            border-radius: 4px;
            margin: 2px 0;
        }
        .debug-error {
            background: #742a2a;
            color: #fed7d7;
            padding: 8px;
            border-radius: 4px;
            margin: 4px 0;
        }
        </style>';
    }

    /**
     * Render request information section
     */
    private function renderRequestInfo(array $summary): string
    {
        $request = $summary['request'] ?? [];
        
        $html = '<div class="debug-section">';
        $html .= '<h3>Request Performance</h3>';
        $html .= '<div class="debug-grid">';
        
        $html .= sprintf('<div class="debug-metric">
            <div>Total Time: <strong>%.3f ms</strong></div>
        </div>', $request['total_time_ms'] ?? 0);
        
        $html .= sprintf('<div class="debug-metric">
            <div>Query Time: <strong>%.3f ms</strong></div>
        </div>', $request['query_time_ms'] ?? 0);
        
        $html .= sprintf('<div class="debug-metric %s">
            <div>Memory Peak: <strong>%.2f MB</strong></div>
        </div>', ($request['memory_peak_mb'] ?? 0) > 128 ? 'warning' : '', $request['memory_peak_mb'] ?? 0);
        
        $html .= sprintf('<div class="debug-metric %s">
            <div>Queries: <strong>%d</strong></div>
        </div>', ($request['query_count'] ?? 0) > 20 ? 'warning' : '', $request['query_count'] ?? 0);
        
        $html .= '</div></div>';
        
        return $html;
    }

    /**
     * Render queries section
     */
    private function renderQueries(array $summary): string
    {
        $queries = $summary['queries'] ?? [];
        $slowQueries = $summary['slow_queries'] ?? [];
        
        if (empty($queries)) {
            return '';
        }
        
        $html = '<div class="debug-section">';
        $html .= sprintf('<h3>SQL Queries (%d) %s</h3>', 
            count($queries), 
            !empty($slowQueries) ? sprintf('<span style="color: #ed8936;">(%d slow)</span>', count($slowQueries)) : ''
        );
        
        // Show only first 5 queries to keep panel manageable
        $displayQueries = array_slice($queries, 0, 5);
        
        foreach ($displayQueries as $query) {
            $isSlow = ($query['time_ms'] ?? 0) > 100;
            $html .= sprintf('<div class="debug-query %s">
                <div class="debug-query-sql">%s</div>
                <div class="debug-query-meta">%.3f ms | Memory: %.2f MB</div>
            </div>', 
                $isSlow ? 'slow' : '',
                htmlspecialchars($query['query'] ?? ''),
                $query['time_ms'] ?? 0,
                ($query['memory'] ?? 0) / 1024 / 1024
            );
        }
        
        if (count($queries) > 5) {
            $html .= sprintf('<div style="color: #a0aec0; text-align: center; padding: 4px;">
                ... and %d more queries
            </div>', count($queries) - 5);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render memory usage section
     */
    private function renderMemory(array $summary): string
    {
        $snapshots = $summary['memory_snapshots'] ?? [];
        
        if (empty($snapshots)) {
            return '';
        }
        
        $html = '<div class="debug-section">';
        $html .= '<h3>Memory Snapshots</h3>';
        
        foreach ($snapshots as $snapshot) {
            $html .= sprintf('<div class="debug-metric">
                <div><strong>%s:</strong> %.2f MB (Peak: %.2f MB)</div>
            </div>', 
                htmlspecialchars($snapshot['label']),
                $snapshot['memory_mb'],
                $snapshot['peak_memory_mb']
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render exceptions section
     */
    private function renderExceptions(array $summary): string
    {
        $exceptions = $summary['exceptions'] ?? [];
        
        if (empty($exceptions)) {
            return '';
        }
        
        $html = '<div class="debug-section">';
        $html .= sprintf('<h3>Exceptions (%d)</h3>', count($exceptions));
        
        foreach ($exceptions as $exception) {
            $html .= sprintf('<div class="debug-error">
                <div><strong>%s:</strong> %s</div>
                <div style="font-size: 11px; color: #fed7d7; margin-top: 4px;">
                    %s:%d
                </div>
            </div>', 
                htmlspecialchars($exception['class']),
                htmlspecialchars($exception['message']),
                htmlspecialchars($exception['file']),
                $exception['line']
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render warnings section
     */
    private function renderWarnings(array $summary): string
    {
        $warnings = $summary['warnings'] ?? [];
        
        if (empty($warnings)) {
            return '';
        }
        
        $html = '<div class="debug-section">';
        $html .= '<h3>Performance Warnings</h3>';
        
        foreach ($warnings as $warning) {
            $html .= sprintf('<div class="debug-warning">%s</div>', 
                htmlspecialchars($warning)
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
