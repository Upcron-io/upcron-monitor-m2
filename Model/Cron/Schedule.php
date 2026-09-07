<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\Cron;

use Magento\Cron\Model\Schedule as MagentoSchedule;

/**
 * Extends Magento Schedule to expose save() as a pluggable method.
 *
 * Magento's interceptor generator only wraps methods declared on the class itself.
 * Since Schedule::save() is inherited from AbstractModel and not overridden, plugins
 * registered on Schedule::save() are never applied. This thin subclass declares save()
 * explicitly, causing Magento's DI compiler to include it in the generated interceptor
 * and making plugins on this class functional.
 */
class Schedule extends MagentoSchedule
{
    /**
     * Explicitly declare save() to make it visible to Magento's interceptor generator.
     */
    public function save()
    {
        return parent::save();
    }
}
