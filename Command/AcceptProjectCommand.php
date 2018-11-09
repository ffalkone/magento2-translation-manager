<?php
declare(strict_types=1);

namespace Eurotext\TranslationManager\Command;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\State\ProjectStateMachine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptProjectCommand extends Command
{
    const ARG_ID              = 'id';
    const COMMAND_NAME        = 'etm:project:accept';
    const COMMAND_DESCRIPTION = 'Accept project translations. project is in status translated';

    /**
     * @var ProjectStateMachine
     */
    private $projectStateMachine;

    public function __construct(ProjectStateMachine $projectStateMachine)
    {
        parent::__construct();

        $this->projectStateMachine = $projectStateMachine;
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESCRIPTION);
        $this->addArgument(self::ARG_ID, InputArgument::REQUIRED, 'Magento project-id');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Eurotext\TranslationManager\Exception\IllegalProjectStatusChangeException
     * @throws \Eurotext\TranslationManager\Exception\InvalidProjectStatusException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = (int) $input->getArgument(self::ARG_ID);

        $this->projectStateMachine->applyById($projectId, ProjectInterface::STATUS_ACCEPTED);

        $output->writeln("<info>project-id $projectId marked as accepted</info>");
    }
} 
