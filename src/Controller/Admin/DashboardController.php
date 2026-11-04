<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ContactMessageRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ContactMessageRepository $messages,
        /** @var array{name: string} */
        private readonly array $site,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'usersCount' => $this->users->count(),
            'messagesCount' => $this->messages->count(),
            'unreadCount' => $this->messages->countUnread(),
            'latestMessages' => $this->messages->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->site['name'])
            ->setFaviconPath('favicon.svg')
            ->setLocales(['fr']);
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateTimeFormat('dd/MM/yyyy HH:mm')
            ->setTimezone('Europe/Paris')
            ->setPaginatorPageSize(30);
    }

    public function configureMenuItems(): iterable
    {
        $unread = $this->messages->countUnread();

        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkTo(ContactMessageCrudController::class, 'Messages de contact', 'fa fa-envelope')
            ->setBadge($unread ?: false, 'danger');

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-arrow-up-right-from-square', 'app_home');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-right-from-bracket');
    }
}
