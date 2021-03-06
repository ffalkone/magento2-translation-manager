<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManager\Service;

use Eurotext\RestApiClient\Enum\ProjectStatusEnum;
use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Eurotext\TranslationManager\Api\ProjectRepositoryInterface;
use Eurotext\TranslationManager\Exception\IllegalProjectStatusChangeException;
use Eurotext\TranslationManager\Exception\InvalidProjectStatusException;
use Eurotext\TranslationManager\Service\Project\FetchProjectEntitiesServiceInterface;
use Eurotext\TranslationManager\Service\Project\TransitionProjectServiceInterface;
use Eurotext\TranslationManager\State\ProjectStateMachine;

class RetrieveProjectService implements RetrieveProjectServiceInterface
{
    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var FetchProjectEntitiesServiceInterface
     */
    private $fetchProjectEntities;

    /**
     * @var ProjectStateMachine
     */
    private $projectStateMachine;

    /**
     * @var TransitionProjectServiceInterface
     */
    private $transitionProject;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        FetchProjectEntitiesServiceInterface $fetchProjectEntities,
        TransitionProjectServiceInterface $transitionProject,
        ProjectStateMachine $projectStateMachine
    ) {
        $this->projectRepository    = $projectRepository;
        $this->fetchProjectEntities = $fetchProjectEntities;
        $this->projectStateMachine  = $projectStateMachine;
        $this->transitionProject    = $transitionProject;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws IllegalProjectStatusChangeException
     * @throws InvalidProjectStatusException
     */
    public function executeById(int $id) // return-types not supported by magento code-generator
    {
        $project = $this->projectRepository->getById($id);

        return $this->execute($project);
    }

    /**
     * @param ProjectInterface $project
     *
     * @return bool
     * @throws IllegalProjectStatusChangeException
     * @throws InvalidProjectStatusException
     */
    public function execute(ProjectInterface $project) // return-types not supported by magento code-generator
    {
        // Projects need to be in status accepted otherwise they will not be retrieved
        if ($project->getStatus() !== ProjectInterface::STATUS_ACCEPTED) {
            throw new InvalidProjectStatusException(
                sprintf(
                    'project needs to be in status "%s", current status is "%s"',
                    ProjectInterface::STATUS_ACCEPTED, $project->getStatus()
                )
            );
        }

        $resultEntities = $this->fetchProjectEntities->execute($project);

        // Check results for error messages
        $status = ProjectInterface::STATUS_IMPORTED;
        if ($this->validateResultEntities($resultEntities) === true) {
            $status = ProjectInterface::STATUS_ERROR;
        }

        // Transfer finished, set Status
        $this->projectStateMachine->apply($project, $status);

        // Stop process when there was an error
        if ($project->getStatus() === ProjectInterface::STATUS_ERROR) {
            return false;
        }

        // set Eurotext project status = imported
        // ETM2-33: the final transition has been disabled since it is not supported right now
        // $resultTransition = $this->transitionProject->execute($project, ProjectStatusEnum::IMPORTED());
        $resultTransition = true;

        // save project to store possible errors
        $this->projectRepository->save($project);

        return $resultTransition;
    }

    /**
     * @param $resultEntities
     *
     * @return bool
     */
    private function validateResultEntities($resultEntities): bool
    {
        $hasErrors = false;
        foreach ($resultEntities as $entityKey => $entityResult) {
            if ($entityResult !== true) {
                $hasErrors = true;
            }
        }

        return $hasErrors;
    }
}
