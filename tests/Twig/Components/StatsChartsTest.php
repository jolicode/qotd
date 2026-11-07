<?php

namespace App\Tests\Twig\Components;

use App\Stats\StatsPeriod;
use App\Stats\UsersChartType;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

class StatsChartsTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testUsersChartFiltersByAuthorOnlyWithAuthorLabels(): void
    {
        foreach (UsersChartType::cases() as $type) {
            $crawler = $this
                ->createLiveComponent('UsersChart', ['type' => $type])
                ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
                ->render()
                ->crawler()
            ;

            self::assertStringContainsString($type->getTitle(), $crawler->text());
            self::assertCount(1, $crawler->filter('canvas'));
            self::assertCount($type->hasAuthorLabels() ? 1 : 0, $crawler->filter('[data-controller="chart-author-filter"]'));
        }
    }

    public function testRanksOnlyUsers(): void
    {
        $view = $this
            ->createLiveComponent('UsersChart', ['type' => UsersChartType::MostQuoted])
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
            ->render()
            ->crawler()
            ->filter('canvas')
            ->attr('data-symfony--ux-chartjs--chart-view-value')
        ;

        $data = json_decode((string) $view, true, flags: JSON_THROW_ON_ERROR)['data'];

        self::assertCount(10, $data['labels']);
        self::assertNotContains('other', $data['labels']);
        self::assertStringContainsString('@', $data['datasets'][0]['usernames'][0]);
        self::assertStringNotContainsString('@', $data['labels'][0]);
    }

    public function testShowPeriod(): void
    {
        $chart = $this
            ->createLiveComponent('CountOverPeriodChart')
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;

        self::assertSame('Monthly', $chart->render()->crawler()->filter('.nav-link.active')->text());
        self::assertCount(2, $chart->render()->crawler()->filter('canvas'));
        $monthlyView = $chart->render()->crawler()->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value');

        $chart->call('showPeriod', ['period' => StatsPeriod::Year->value]);

        self::assertSame('Yearly', $chart->render()->crawler()->filter('.nav-link.active')->text());
        self::assertNotSame($monthlyView, $chart->render()->crawler()->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value'));
    }
}
