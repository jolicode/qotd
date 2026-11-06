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

    public function testShowPeriod(): void
    {
        $chart = $this
            ->createLiveComponent('CountOverPeriodChart')
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;

        self::assertSame('Monthly', $chart->render()->crawler()->filter('.nav-link.active')->text());
        $monthlyView = $chart->render()->crawler()->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value');

        $chart->call('showPeriod', ['period' => StatsPeriod::Year->value]);

        self::assertSame('Yearly', $chart->render()->crawler()->filter('.nav-link.active')->text());
        self::assertNotSame($monthlyView, $chart->render()->crawler()->filter('canvas')->attr('data-symfony--ux-chartjs--chart-view-value'));
    }
}
