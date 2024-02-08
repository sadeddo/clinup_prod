<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\AccountLink;
use Stripe\StripeClient;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NotifController extends AbstractController
{
    #[Route('/notif/prestataire', name: 'app_notif')]
    public function index(Security $security): Response
    {  
        $user = $security->getUser();
        $notifs = $user->getNotifs()->matching(
            Criteria::create()->orderBy(['CreatedAt' => Criteria::DESC])
        );
        return $this->render('notif/index.html.twig', [
            'notifications' => $notifs,
        ]);
    }
    #[Route('/notif/hote', name: 'app_notif_hote')]
    public function indexH(Security $security): Response
    {  
        $user = $security->getUser();
        $notifs = $user->getNotifs()->matching(
            Criteria::create()->orderBy(['CreatedAt' => Criteria::DESC])
        );
        return $this->render('notif/hote.html.twig', [
            'notifications' => $notifs,
        ]);
    }
}
