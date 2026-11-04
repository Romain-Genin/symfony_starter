<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;
use App\Spam\SpamChecker;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ContactTest extends WebTestCase
{
    use SitePaths;
    use ResetDatabase;

    public function testValidMessageIsStoredEncryptedAndEmailed(): void
    {
        $client = self::createClient();
        $this->submitContactForm($client);

        self::assertResponseRedirects(self::page('/contact'), 303);
        self::assertEmailCount(2);

        $messages = self::getContainer()->get(ContactMessageRepository::class)->findAll();
        self::assertCount(1, $messages);
        self::assertInstanceOf(ContactMessage::class, $messages[0]);
        self::assertSame('jeanne@example.com', $messages[0]->getEmail());

        // En base, les colonnes sensibles sont chiffrées.
        $raw = self::getContainer()->get(Connection::class)->fetchAssociative('SELECT name, email, message FROM contact_message');
        self::assertIsArray($raw);
        foreach ($raw as $value) {
            self::assertStringStartsWith('enc:v1:', (string) $value);
        }
        self::assertStringNotContainsString('jeanne', implode('', $raw));

        $client->followRedirect();
        self::assertSelectorTextContains('[role=status]', 'votre message a bien été envoyé');
    }

    public function testHoneypotSilentlyDropsSpam(): void
    {
        $client = self::createClient();
        $this->submitContactForm($client, ['contact[antispam][website]' => 'https://spam.example']);

        self::assertResponseRedirects(self::page('/contact'), 303);
        self::assertEmailCount(0);
        self::assertSame(0, self::getContainer()->get(ContactMessageRepository::class)->count());
    }

    public function testTooFastSubmissionIsSpam(): void
    {
        $client = self::createClient();
        $token = self::getContainer()->get(SpamChecker::class)->createToken(time());
        $this->submitContactForm($client, ['contact[antispam][token]' => $token]);

        self::assertEmailCount(0);
        self::assertSame(0, self::getContainer()->get(ContactMessageRepository::class)->count());
    }

    public function testInvalidFormShowsAccessibleErrors(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', self::page('/contact'));
        $client->submit($crawler->selectButton('Envoyer le message')->form(), ['contact[email]' => 'pas-un-email']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#contact_email[aria-invalid="true"][aria-describedby~="contact_email_errors"]');
        self::assertSelectorTextContains('#contact_email_errors', 'n\'est pas valide');
    }

    /**
     * @param array<string, string> $overrides
     */
    private function submitContactForm(KernelBrowser $client, array $overrides = []): void
    {
        $crawler = $client->request('GET', self::page('/contact'));
        $form = $crawler->selectButton('Envoyer le message')->form();

        $client->submit($form, $overrides + [
            'contact[name]' => 'Jeanne Martin',
            'contact[email]' => 'jeanne@example.com',
            'contact[subject]' => 'Demande de devis',
            'contact[message]' => 'Bonjour, je souhaiterais un devis pour un site vitrine.',
            'contact[consent]' => '1',
            // Simule un humain ayant mis 10 s à remplir le formulaire.
            'contact[antispam][token]' => self::getContainer()->get(SpamChecker::class)->createToken(time() - 10),
        ]);
    }
}
