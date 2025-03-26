<?php

namespace App\Command;

use App\Repository\IcalresRepository;
use App\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RappelReservationCommand extends Command
{
    protected static $defaultName = 'app:rappel-reservation';

    private IcalresRepository $icalresRepository;
    private EmailSender $emailSender;
    private EntityManagerInterface $em;

    public function __construct(IcalresRepository $icalresRepository, EmailSender $emailSender, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->icalresRepository = $icalresRepository;
        $this->emailSender = $emailSender;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setDescription('Envoie un email de rappel pour les réservations à venir (dans 3 jours)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vérification des réservations à venir...');

        $from = new \DateTime('today');
        $to = (new \DateTime())->modify('+3 days');

        $reservations = $this->icalresRepository->findUpcomingUnnotifiedReservations($from, $to);

        foreach ($reservations as $res) {
            $logement = $res->getLogement();
            $hote = $logement->getHote();

            if ($hote && $hote->getEmail()) {
                $this->emailSender->sendEmail(
                    $hote->getEmail(),
                    'Rappel : Réservation à venir',
                    'email/rappel_reservation.html.twig',  // ton template twig
                    [
                        'hote' => $hote,
                        'logement' => $logement,
                        'reservation' => $res,
                    ]
                );                
            }
        }

        $this->em->flush();
        $io->success('Tous les rappels ont été envoyés !');

        return Command::SUCCESS;
    }
}
