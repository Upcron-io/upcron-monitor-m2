<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class HttpsUrl extends Value
{
    /**
     * @throws LocalizedException
     */
    public function beforeSave(): self
    {
        $value = (string) $this->getValue();

        if ($value !== '' && !str_starts_with($value, 'https://')) {
            throw new LocalizedException(__('API Base URL must use HTTPS'));
        }

        return parent::beforeSave();
    }
}
