<?php

namespace App\Qotd;

final readonly class QotdCsvExporter
{
    private const array COLUMNS = ['date', 'username', 'vote', 'message'];

    /**
     * @param iterable<array{date: string, username: string, vote: int, message: string}> $rows
     * @param resource                                                                    $stream
     */
    public function export(iterable $rows, $stream): void
    {
        fputcsv($stream, self::COLUMNS, escape: '');

        foreach ($rows as $row) {
            fputcsv($stream, array_map($this->neutralizeFormula(...), $row), escape: '');
        }
    }

    /**
     * Spreadsheets run the cells that look like a formula, and a QOTD is what
     * anyone wrote on Slack.
     */
    private function neutralizeFormula(string|int $value): string
    {
        return preg_replace('/^[=+\-@\t\r]/', "'$0", (string) $value) ?? throw new \LogicException('The formula pattern is invalid.');
    }
}
