<?php

declare(strict_types=1);

namespace Upcron\Monitor\Service;

/**
 * Parses simple cron expressions to approximate interval in seconds.
 *
 * Handles the most common real-world patterns. Complex or non-uniform
 * expressions (e.g. "5 4 * * sun,mon") return null and the caller
 * should fall back to the default grace period.
 */
class CronExpressionParser
{
    /**
     * Returns the approximate interval in seconds between executions,
     * or null when the expression is too complex to parse reliably.
     */
    public function toIntervalSeconds(string $expression): ?int
    {
        $expression = trim($expression);

        if ($expression === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $expression);
        if (count($parts) !== 5) {
            return null;
        }

        [$minute, $hour, $dom, $month, $dow] = $parts;

        // Every N minutes: */N * * * *
        if (preg_match('/^\*\/(\d+)$/', $minute, $m) && $hour === '*' && $dom === '*' && $month === '*' && $dow === '*') {
            return (int) $m[1] * 60;
        }

        // Exact minute, every N hours: M */N * * *  or  M * * * * (hourly)
        if (preg_match('/^\d+$/', $minute)) {
            if (preg_match('/^\*\/(\d+)$/', $hour, $h) && $dom === '*' && $month === '*' && $dow === '*') {
                return (int) $h[1] * 3600;
            }
            // Hourly: M * * * *
            if ($hour === '*' && $dom === '*' && $month === '*' && $dow === '*') {
                return 3600;
            }
        }

        // Daily: M H * * *  (specific minute and hour, every day)
        if (preg_match('/^\d+$/', $minute) && preg_match('/^\d+$/', $hour) && $dom === '*' && $month === '*' && $dow === '*') {
            return 86400;
        }

        // Daily at midnight variants: 0 0 * * *
        if ($minute === '0' && $hour === '0' && $dom === '*' && $month === '*' && $dow === '*') {
            return 86400;
        }

        // Weekly: M H * * D  (specific day of week)
        if (preg_match('/^\d+$/', $minute) && preg_match('/^\d+$/', $hour) && $dom === '*' && $month === '*' && preg_match('/^\d+$/', $dow)) {
            return 604800;
        }

        // Monthly: M H D * *  (specific day of month)
        if (preg_match('/^\d+$/', $minute) && preg_match('/^\d+$/', $hour) && preg_match('/^\d+$/', $dom) && $month === '*' && $dow === '*') {
            // ~30 days
            return 2592000;
        }

        return null;
    }
}
