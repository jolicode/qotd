<?php

namespace App\Tests\Qotd;

use App\Qotd\QotdCsvExporter;
use PHPUnit\Framework\TestCase;

class QotdCsvExporterTest extends TestCase
{
    public function testExport(): void
    {
        $csv = self::export([
            ['date' => '2023-05-12', 'username' => 'foo@example.com', 'vote' => 3, 'message' => "Hello\n\"world\""],
        ]);

        self::assertSame("date,username,vote,message\n2023-05-12,foo@example.com,3,\"Hello\n\"\"world\"\"\"\n", $csv);
    }

    public function testExportNeutralizesFormulas(): void
    {
        $csv = self::export([
            ['date' => '2023-05-12', 'username' => 'foo@example.com', 'vote' => -1, 'message' => '=HYPERLINK("https://example.com")'],
        ]);

        self::assertStringContainsString("'-1", $csv);
        self::assertStringContainsString("\"'=HYPERLINK", $csv);
    }

    /**
     * @param list<array{date: string, username: string, vote: int, message: string}> $rows
     */
    private static function export(array $rows): string
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        (new QotdCsvExporter())->export($rows, $stream);
        rewind($stream);

        return (string) stream_get_contents($stream);
    }
}
