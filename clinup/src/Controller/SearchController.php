<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Disponibilite;
use App\Form\SearchPrestaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/recherche', name: 'presta_search')]
    public function search(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SearchPrestaType::class);
        $form->handleRequest($request);

        $prestataires = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $adresseClient = $data['adresse'];
            $dateRecherche = $data['date'];

            $coordsClient = $this->geocodeAdresse($adresseClient);
            if (!$coordsClient) {
                $this->addFlash('error', 'Adresse invalide');
                return $this->redirectToRoute('presta_search');
            }

            // Prestataires dispos à cette date
            $dispos = $em->getRepository(Disponibilite::class)->findBy(['date' => $dateRecherche]);

            foreach ($dispos as $dispo) {
                $presta = $dispo->getPrestataireId(); // Attention : adapter si c'est un objet ou un ID

                if (!$presta instanceof User) {
                    $presta = $em->getRepository(User::class)->find($presta);
                }

                $coordsPresta = $this->geocodeAdresse($presta->getAdresse());
                if (!$coordsPresta) continue;

                $distance = $this->distanceKm(
                    $coordsClient['lat'], $coordsClient['lon'],
                    $coordsPresta['lat'], $coordsPresta['lon']
                );

                if ($distance <= 20) {
                    $prestataires[] = $presta;
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'prestataires' => $prestataires,
        ]);
    }

    private function geocodeAdresse(string $adresse): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($adresse);
        $opts = [
            "http" => [
                "header" => "User-Agent: Clinup/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        $response = @file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        if (!empty($data[0])) {
            return [
                'lat' => (float) $data[0]['lat'],
                'lon' => (float) $data[0]['lon']
            ];
        }

        return null;
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
