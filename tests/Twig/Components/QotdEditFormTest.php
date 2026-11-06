<?php

namespace App\Tests\Twig\Components;

use App\Entity\Qotd;
use App\Repository\QotdRepository;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class QotdEditFormTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testShowsTheRemainingLength(): void
    {
        $form = $this->createEditForm();

        $form->set('qotd.context', 'Hello');

        self::assertSame(
            \sprintf('%d characters left', Qotd::CONTEXT_MAX_LENGTH - 5),
            $form->render()->crawler()->filter('[data-test=context-remaining]')->text(),
        );
    }

    public function testValidatesWhileTyping(): void
    {
        $client = self::getContainer()->get('test.client');
        $form = $this->createEditForm($client);
        // LiveComponent renders the errors from the 422 exception
        $client->catchExceptions(true);

        // The browser adds each field the user changes
        $form->set('validatedFields', ['qotd.context']);
        $form->set('qotd.context', str_repeat('a', Qotd::CONTEXT_MAX_LENGTH + 1));

        $crawler = $form->render()->crawler();
        self::assertStringContainsString('text-danger', (string) $crawler->filter('[data-test=context-remaining]')->attr('class'));
        self::assertCount(1, $crawler->filter('.invalid-feedback'));
    }

    public function testRenderingDoesNotPersistTheContext(): void
    {
        $form = $this->createEditForm();

        $form->set('qotd.context', 'Not saved yet');

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->clear();
        self::assertSame('', self::getQotd()->context);
    }

    public function testSave(): void
    {
        $form = $this->createEditForm();

        try {
            $form->submitForm(['qotd' => ['context' => 'Saved']], 'save');

            self::assertSame('Saved', self::getQotd()->context);
            self::assertNotNull($form->getDispatchedBrowserEvent($form->render(), 'modal:visit'));
        } finally {
            $connection = self::getContainer()->get(Connection::class);
            $connection->executeStatement("UPDATE qotd SET context = '' WHERE username = 'old@example.com'");
        }
    }

    public function testSaveRefusesAnInvalidContext(): void
    {
        $form = $this->createEditForm();

        $this->expectException(UnprocessableEntityHttpException::class);

        $form->submitForm(['qotd' => ['context' => str_repeat('a', Qotd::CONTEXT_MAX_LENGTH + 1)]], 'save');
    }

    private function createEditForm(?KernelBrowser $client = null): TestLiveComponent
    {
        return $this
            ->createLiveComponent('QotdEditForm', ['initialFormData' => self::getQotd()], $client)
            ->actingAs(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']))
        ;
    }

    private static function getQotd(): Qotd
    {
        $qotd = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);
        self::assertInstanceOf(Qotd::class, $qotd);

        return $qotd;
    }
}
