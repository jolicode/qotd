<?php

namespace App\Tests\Slack\Blockkit;

use App\Slack\BlockKit\MessageRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;

class MessageRendererTest extends KernelTestCase
{
    /**
     * @dataProvider providerTest
     */
    public function test(string $basename): void
    {
        self::bootKernel(['debug' => false]);

        $renderer = self::getContainer()->get(MessageRenderer::class);

        $message = json_decode(file_get_contents(__DIR__ . '/fixtures/' . $basename . '.json'), true);

        $output = $renderer->render($message);

        $expectedFile = __DIR__ . '/fixtures/' . $basename . '.txt';
        if ($_SERVER['UPDATE_FIXTURES'] ?? false) {
            file_put_contents($expectedFile, $output);
        }
        if (!file_exists($expectedFile)) {
            throw new \RuntimeException(\sprintf('The fixture file "%s" does not exist.', $expectedFile));
        }

        $this->assertStringEqualsFile($expectedFile, $output);
    }

    public static function providerTest(): iterable
    {
        $finder = (new Finder())
            ->files()
            ->in(__DIR__ . '/fixtures')
            ->name('*.json')
        ;

        foreach ($finder as $file) {
            yield $file->getBasename('.json') => [$file->getBasename('.json')];
        }
    }
}
