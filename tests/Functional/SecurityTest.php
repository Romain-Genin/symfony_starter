<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Spam\SpamChecker;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class SecurityTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAdminRequiresAdminRole(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin');
        self::assertResponseRedirects('/connexion');

        $client->loginUser(UserFactory::createOne());
        $client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessDashboard(): void
    {
        $client = self::createClient();
        $client->loginUser(UserFactory::new()->admin()->create());
        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
    }

    public function testRegistrationCreatesUnverifiedUserAndSendsConfirmation(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/inscription');
        $client->submit($crawler->selectButton('Créer mon compte')->form(), [
            'registration_form[email]' => 'Alice@Example.com',
            'registration_form[plainPassword][first]' => 'Une-phrase-de-passe-solide-42',
            'registration_form[plainPassword][second]' => 'Une-phrase-de-passe-solide-42',
            'registration_form[agreeTerms]' => '1',
            'registration_form[antispam][token]' => self::getContainer()->get(SpamChecker::class)->createToken(time() - 10),
        ]);

        self::assertResponseRedirects('/inscription/verifiez-vos-emails', 303);
        self::assertEmailCount(1);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('alice@example.com');
        self::assertNotNull($user, 'Recherche par blind index (email normalisé).');
        self::assertFalse($user->isVerified());
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $client = self::createClient();
        UserFactory::createOne(['email' => 'bob@example.com']);
        $crawler = $client->request('GET', '/inscription');
        $client->submit($crawler->selectButton('Créer mon compte')->form(), [
            'registration_form[email]' => 'BOB@example.com',
            'registration_form[plainPassword][first]' => 'Une-phrase-de-passe-solide-42',
            'registration_form[plainPassword][second]' => 'Une-phrase-de-passe-solide-42',
            'registration_form[agreeTerms]' => '1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#registration_form_email_errors', 'Un compte existe déjà');
    }

    public function testUnverifiedUserCannotLogIn(): void
    {
        $client = self::createClient();
        UserFactory::new()->unverified()->create(['email' => 'carol@example.com']);
        $crawler = $client->request('GET', '/connexion');
        $client->submit($crawler->selectButton('Se connecter')->form(), [
            '_username' => 'carol@example.com',
            '_password' => UserFactory::DEFAULT_PASSWORD,
        ]);
        $client->followRedirect();

        self::assertSelectorTextContains('[role=alert]', 'confirmer votre adresse email');
    }

    public function testVerifiedUserCanLogIn(): void
    {
        $client = self::createClient();
        UserFactory::createOne(['email' => 'dave@example.com']);
        $crawler = $client->request('GET', '/connexion');
        $client->submit($crawler->selectButton('Se connecter')->form(), [
            '_username' => 'dave@example.com',
            '_password' => UserFactory::DEFAULT_PASSWORD,
        ]);

        self::assertResponseRedirects('/mon-compte');
    }

    public function testForgotPasswordDoesNotRevealAccounts(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/mot-de-passe-oublie');
        $client->submit($crawler->selectButton('Recevoir le lien')->form(), ['email_request_form[email]' => 'inconnu@example.com']);

        self::assertResponseRedirects('/mot-de-passe-oublie/verifiez-vos-emails', 303);
        self::assertEmailCount(0);
    }
}
