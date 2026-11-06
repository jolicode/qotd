<?php

namespace App\Tests\Controller;

use App\Entity\Qotd;
use App\Repository\QotdRepository;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QotdImagesControllerTest extends WebTestCase
{
    private const string ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function testAddImage(): void
    {
        $client = self::createClientAndLogin();
        $qotd = self::getQotd();

        try {
            self::submit($client, $qotd, [self::createUploadedFile('Hello World.png', base64_decode(self::ONE_PIXEL_PNG, true))]);

            self::assertResponseRedirects("/qotd/{$qotd->id}");

            $images = self::getContainer()->get(Connection::class)->fetchOne('SELECT images FROM qotd WHERE id = :id', ['id' => $qotd->id]);
            self::assertMatchesRegularExpression('/---hello-world\.png"\]$/i', $images);
        } finally {
            self::getContainer()->get(Connection::class)->executeStatement("UPDATE qotd SET images = '[]' WHERE id = :id", ['id' => $qotd->id]);
            (new Filesystem())->remove(self::getContainer()->getParameter('upload_dir'));
        }
    }

    public function testRefusesAFileThatIsNotAnImage(): void
    {
        $client = self::createClientAndLogin();

        self::submit($client, self::getQotd(), [self::createUploadedFile('notes.txt', 'Not an image.')]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testRefusesTooManyImages(): void
    {
        $client = self::createClientAndLogin();
        $images = array_map(
            static fn (int $i): UploadedFile => self::createUploadedFile("{$i}.png", base64_decode(self::ONE_PIXEL_PNG, true)),
            range(1, 6),
        );

        self::submit($client, self::getQotd(), $images);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @param list<UploadedFile> $files
     */
    private static function submit(KernelBrowser $client, Qotd $qotd, array $files): void
    {
        $crawler = $client->request('GET', "/qotd/{$qotd->id}/images");
        $form = $crawler->filter('#qotd-images')->form();

        $client->request(
            'POST',
            (string) $form->getUri(),
            $form->getPhpValues(),
            ['qotd_images' => ['images' => $files]],
            ['HTTP_ORIGIN' => 'http://localhost', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        );
    }

    private static function createUploadedFile(string $name, string $content): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'qotd');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, test: true);
    }

    private static function getQotd(): Qotd
    {
        $qotd = self::getContainer()->get(QotdRepository::class)->findOneBy(['username' => 'old@example.com']);
        self::assertInstanceOf(Qotd::class, $qotd);

        return $qotd;
    }

    private function createClientAndLogin(): KernelBrowser
    {
        $client = self::createClient();

        $client->loginUser(new OAuthUser('test-user', ['ROLE_USER', 'ROLE_OAUTH_USER']));

        return $client;
    }
}
