<?php

declare(strict_types=1);

namespace Upcron\Monitor\Block\Adminhtml\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class TestConnectionButton extends Field
{
    protected $_template = 'Upcron_Monitor::config/test_connection_button.phtml';

    public function __construct(
        Context $context,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label — button is not a config value.
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getTestConnectionUrl(): string
    {
        return $this->getUrl('upcron_monitor/config/testConnection');
    }

    public function getButtonHtmlId(): string
    {
        return 'upcron_monitor_test_connection_button';
    }
}
