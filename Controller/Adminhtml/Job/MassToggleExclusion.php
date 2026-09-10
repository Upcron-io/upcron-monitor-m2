<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Job;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class MassToggleExclusion extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::jobs';

    public function __construct(
        Context $context,
        private readonly HeartbeatRepository $heartbeatRepository,
        private readonly JobDiscoveryService $jobDiscoveryService,
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $selected = $this->getRequest()->getParam('selected', []);
        $excluded = $this->getRequest()->getParam('excluded', null);

        if (!is_array($selected) || $selected === []) {
            $this->messageManager->addErrorMessage(__('No jobs selected.'));
            return $this->redirectToListing();
        }

        if (!in_array($excluded, [0, 1, '0', '1'], true)) {
            $this->messageManager->addErrorMessage(__('Invalid exclusion state.'));
            return $this->redirectToListing();
        }

        $jobCodes = array_values(array_unique(array_filter(
            $selected,
            static fn (mixed $jobCode): bool => is_string($jobCode) && $jobCode !== '',
        )));

        if ($jobCodes === []) {
            $this->messageManager->addErrorMessage(__('No valid jobs selected.'));
            return $this->redirectToListing();
        }

        $discovered = $this->jobDiscoveryService->discover();
        $updated = 0;

        try {
            foreach ($jobCodes as $jobCode) {
                $heartbeat = $this->heartbeatRepository->getOrCreate($jobCode);

                if (!$heartbeat->getId()) {
                    $jobData = $discovered[$jobCode] ?? null;
                    $heartbeat->setCronSchedule($jobData['cron_schedule'] ?? '');
                    $heartbeat->setGracePeriodSeconds($jobData['grace_period_seconds'] ?? 3600);
                }

                $heartbeat->setIsExcluded((bool) (int) $excluded);
                $this->heartbeatRepository->save($heartbeat);
                $updated++;
            }

            $state = (int) $excluded === 1 ? __('excluded') : __('included');
            $this->messageManager->addSuccessMessage(
                __('%1 job(s) marked as %2.', $updated, $state)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Unable to update jobs after %1 successful update(s): %2', $updated, $e->getMessage())
            );
        }

        return $this->redirectToListing();
    }

    private function redirectToListing(): Redirect
    {
        return $this->resultRedirectFactory->create()->setPath('upcron_monitor/job/index');
    }
}
