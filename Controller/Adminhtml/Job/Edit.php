<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Job;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::jobs';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $jobCode = (string) $this->getRequest()->getParam('job_code', '');

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Edit Job: %1', $jobCode));

        return $page;
    }
}
