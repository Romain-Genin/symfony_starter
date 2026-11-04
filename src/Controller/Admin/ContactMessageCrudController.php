<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Pdf\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<ContactMessage>
 */
final class ContactMessageCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message')
            ->setEntityLabelInPlural('Messages de contact')
            ->setDefaultSort(['createdAt' => 'DESC'])
            // Colonnes chiffrées : ni recherche plein texte, ni tri SQL possibles.
            ->setSearchFields(null)
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (ContactMessage $m): string => (string) $m->getSubject());
    }

    public function configureActions(Actions $actions): Actions
    {
        $pdf = Action::new('pdf', 'PDF', 'fa fa-file-pdf')
            ->linkToCrudAction('exportPdf')
            ->setHtmlAttributes(['target' => '_blank', 'data-turbo' => 'false']);

        $reply = Action::new('reply', 'Répondre', 'fa fa-reply')
            ->linkToUrl(static fn (ContactMessage $m): string => \sprintf('mailto:%s?subject=%s', $m->getEmail(), rawurlencode('Re: '.$m->getSubject())));

        $toggleRead = Action::new('toggleRead', 'Marquer comme non lu', 'fa fa-envelope')
            ->linkToCrudAction('toggleRead')
            ->displayIf(static fn (ContactMessage $m): bool => $m->isRead());

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $reply)
            ->add(Crud::PAGE_DETAIL, $pdf)
            ->add(Crud::PAGE_DETAIL, $toggleRead);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(DateTimeFilter::new('createdAt', 'Reçu le'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield BooleanField::new('read', 'Lu')->renderAsSwitch(false)->onlyOnIndex();
        yield TextField::new('name', 'Nom')->setSortable(false);
        yield EmailField::new('email', 'Email')->setSortable(false);
        yield TelephoneField::new('phone', 'Téléphone')->hideOnIndex();
        yield TextField::new('subject', 'Objet')->setSortable(false);
        yield TextareaField::new('message', 'Message')->onlyOnDetail()->renderAsHtml(false);
        yield DateTimeField::new('createdAt', 'Reçu le');
        yield DateTimeField::new('readAt', 'Lu le')->onlyOnDetail();
    }

    /**
     * Consulter un message le marque comme lu.
     */
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $message = $context->getEntity()->getInstance();
        if ($message instanceof ContactMessage && !$message->isRead()) {
            $message->markAsRead();
            $this->em->flush();
        }

        return parent::detail($context);
    }

    #[AdminRoute('/{id}/toggle-read', name: 'toggle_read')]
    public function toggleRead(#[MapEntity] ContactMessage $message): RedirectResponse
    {
        $message->isRead() ? $message->markAsUnread() : $message->markAsRead();
        $this->em->flush();

        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->unset('entityId')->generateUrl());
    }

    #[AdminRoute('/{id}/pdf', name: 'pdf')]
    public function exportPdf(#[MapEntity] ContactMessage $message, PdfGenerator $pdf): Response
    {
        return $pdf->response('pdf/contact_message.html.twig', ['message' => $message], \sprintf('message-%d.pdf', $message->getId()));
    }
}
