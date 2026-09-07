<?php

declare(strict_types=1);

namespace Upcron\Monitor\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Upcron\Monitor\Service\SyncService;

class SyncCommand extends Command
{
    private const OPTION_FORCE = 'force';

    public function __construct(
        private readonly SyncService $syncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('upcron:sync')
            ->setDescription('Sync all non-excluded Magento cron jobs to Upcron as Heartbeat monitors')
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Force re-sync of all jobs, including those already synced'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = (bool) $input->getOption(self::OPTION_FORCE);

        try {
            $result = $this->syncService->sync($force);

            if (!empty($result['errors'])) {
                $output->writeln('<error>Sync failed: ' . implode(', ', $result['errors']) . '. No changes were saved.</error>');
                return Command::FAILURE;
            }

            $output->writeln('<info>Synced ' . $result['synced'] . ' heartbeats successfully.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Sync failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
