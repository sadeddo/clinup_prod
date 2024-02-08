<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DesignController extends AbstractController
{
    #[Route('/design', name: 'app_design')]
    public function index(): Response
    {
        return $this->render('design/index.html.twig', [
            'controller_name' => 'DesignController',
        ]);
    }
    #[Route('/designAuth', name: 'app_design_auth')]
    public function auth(): Response
    {
        return $this->render('design/auth.html.twig', [
            'controller_name' => 'DesignController',
        ]);
    }
}
