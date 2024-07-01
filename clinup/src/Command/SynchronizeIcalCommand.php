<?php
namespace App\Command;

use App\Service\IcalSynchronizationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SynchronizeIcalCommand extends Command
{
    protected static $defaultName = 'app:synchronize-ical';
    private $icalSynchronizationService;

    public function __construct(IcalSynchronizationService $icalSynchronizationService)
    {
        parent::__construct();
        $this->icalSynchronizationService = $icalSynchronizationService;
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName) 
        ->setDescription('Synchronize iCal links');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Synchronizing iCal links...');

        // Call the service to synchronize iCal links
        $this->icalSynchronizationService->synchronize();

        $io->success('iCal links synchronized successfully.');

        return Command::SUCCESS;
    }
}
