<?php

namespace App\Command;

use App\Service\LogService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-logs',
    description: 'Seeds the database with the initial dev.log file',
)]
class ImportLogsCommand extends Command
{
    public function __construct(
		private LogService $logService,
		private ParameterBagInterface $params // Inject the parameter bag
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		
		// This points to /var/www/html/public/dev.log
		$filePath = $this->params->get('kernel.project_dir') . '/public/dev.log'; 

		if (!file_exists($filePath)) {
			$io->warning("No dev.log file found at $filePath. Skipping.");
			return Command::SUCCESS;
		}

		$io->info("Parsing and importing logs...");
		$importedCount = $this->logService->importLogsFromFile($filePath);
		$io->success("Successfully imported $importedCount logs.");

		return Command::SUCCESS;
	}
}