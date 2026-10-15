<?php

namespace App\Tests\KeyboardShortcut;

use App\KeyboardShortcut\KeyboardShortcut;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

class KeyboardShortcutTest extends KernelTestCase
{
    /**
     * The Stimulus controller builds the URL with UX Router.
     *
     * @dataProvider provideRouteIsExposedCases
     */
    public function testRouteIsExposed(KeyboardShortcut $shortcut, string $routeName, bool $withSelectedQotd): void
    {
        $route = self::getContainer()->get(RouterInterface::class)->getRouteCollection()->get($routeName);

        self::assertNotNull($route, \sprintf('The route "%s" does not exist.', $routeName));
        self::assertTrue($route->getOption('expose'), \sprintf('The route "%s" is not exposed to UX Router.', $routeName));
        self::assertSame($withSelectedQotd ? ['id'] : [], $route->compile()->getPathVariables());
    }

    /**
     * @return iterable<string, array{KeyboardShortcut, string, bool}>
     */
    public static function provideRouteIsExposedCases(): iterable
    {
        foreach (KeyboardShortcut::cases() as $shortcut) {
            $stimulus = $shortcut->toStimulus();
            if (isset($stimulus['route'])) {
                yield $shortcut->name => [$shortcut, $stimulus['route'], $stimulus['withSelectedQotd'] ?? false];
            }
        }
    }
}
