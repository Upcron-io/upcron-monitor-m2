<?php

declare(strict_types=1);

namespace Upcron\Monitor\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Upcron\Monitor\Api\Data\HeartbeatInterface;

class AddPingModeColumn implements SchemaPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable(HeartbeatInterface::TABLE_NAME);

        if ($connection->isTableExists($tableName)
            && !$connection->tableColumnExists($tableName, HeartbeatInterface::FIELD_PING_MODE)
        ) {
            $connection->addColumn(
                $tableName,
                HeartbeatInterface::FIELD_PING_MODE,
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 20,
                    'nullable' => false,
                    'default'  => HeartbeatInterface::PING_MODE_PING,
                    'comment'  => 'Ping Mode (ping|start_stop)',
                ]
            );
        }

        $this->moduleDataSetup->endSetup();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
