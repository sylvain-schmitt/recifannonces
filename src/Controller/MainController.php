<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Form\SearchAnnonceType;
use App\Repository\AnnoncesRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(AnnoncesRepository $annoncesRepo, Request $request)
    {
        $annonces = $annoncesRepo->findBy(['active' => true], ['created_at' => 'desc'], 5);

        $form = $this->createForm(SearchAnnonceType::class);
        
        $search = $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // On recherche les annonces correspondant aux mots clés
            $annonces = $annoncesRepo->search(
                $search->get('mots')->getData(),
                $search->get('categorie')->getData()
            );
        }
        if (!$annonces) {
            throw new NotFoundHttpException('Aucunes annonces trouvée');
        }

        return $this->render('main/index.html.twig', [
            'annonces' => $annonces,
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/mentions/legales", name="mentions")
     */
    public function mentions()
    {
        return $this->render('main/mentions.html.twig');
    }

        /**
     * @Route("/contact", name="contact")
     */
    public function contact(Request $request, MailerInterface $mailer)
    {
        $form = $this->createForm(ContactType::class);

        $contact = $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $email = (new TemplatedEmail())
                ->from($contact->get('email')->getData())
                ->to('recifannonce@test.fr')
                ->subject('Contact depuis le site Récif Annonces')
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'mail' => $contact->get('email')->getData(),
                    'sujet' => $contact->get('sujet')->getData(),
                    'message' => $contact->get('message')->getData(),
                ])
            ;

            $mailer->send($email);

            $this->addFlash('message', 'Mail de contact envoyé');
            return $this->redirectToRoute('contact');
        }

        return $this->render('main/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
