<?php

declare(strict_types=1);

namespace Upcron\Monitor\Test\Unit\Controller\Adminhtml\Job;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Controller\Adminhtml\Job\MassToggleExclusion;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class MassToggleExclusionTest extends TestCase
{
    private Context|MockObject $context;
    private RequestInterface|MockObject $request;
    private HeartbeatRepository|MockObject $heartbeatRepository;
    private JobDiscoveryService|MockObject $jobDiscoveryService;
    private ManagerInterface|MockObject $messageManager;
    private RedirectFactory|MockObject $redirectFactory;
    private Redirect|MockObject $redirect;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->heartbeatRepository = $this->createMock(HeartbeatRepository::class);
        $this->jobDiscoveryService = $this->createMock(JobDiscoveryService::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->redirectFactory = $this->createMock(RedirectFactory::class);
        $this->redirect = $this->createMock(Redirect::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultRedirectFactory')->willReturn($this->redirectFactory);
        $this->redirectFactory->method('create')->willReturn($this->redirect);
        $this->redirect->method('setPath')->willReturnSelf();
    }

    public function testExcludesEverySelectedJob(): void
    {
        $firstHeartbeat = $this->createMock(HeartbeatInterface::class);
        $secondHeartbeat = $this->createMock(HeartbeatInterface::class);

        $this->request->method('getParam')->willReturnMap([
            ['selected', [], ['cron_a', 'cron_b']],
            ['excluded', null, '1'],
        ]);
        $this->jobDiscoveryService->expects(self::once())->method('discover')->willReturn([
            'cron_a' => ['cron_schedule' => '* * * * *', 'grace_period_seconds' => 60],
            'cron_b' => ['cron_schedule' => '*/5 * * * *', 'grace_period_seconds' => 600],
        ]);
        $this->heartbeatRepository->expects(self::exactly(2))->method('getOrCreate')
            ->willReturnOnConsecutiveCalls($firstHeartbeat, $secondHeartbeat);
        $this->heartbeatRepository->expects(self::exactly(2))->method('save');
        $this->messageManager->expects(self::once())->method('addSuccessMessage');

        $this->createController()->execute();
    }

    public function testRejectsAnEmptySelection(): void
    {
        $this->request->method('getParam')->willReturnMap([
            ['selected', [], []],
            ['excluded', null, '1'],
        ]);
        $this->heartbeatRepository->expects(self::never())->method('getOrCreate');
        $this->messageManager->expects(self::once())->method('addErrorMessage');

        $this->createController()->execute();
    }

    private function createController(): MassToggleExclusion
    {
        return new MassToggleExclusion(
            $this->context,
            $this->heartbeatRepository,
            $this->jobDiscoveryService,
        );
    }
}
