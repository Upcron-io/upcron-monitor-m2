<?php

declare(strict_types=1);

namespace Upcron\Monitor\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Upcron\Monitor\Api\Data\HeartbeatInterface;

class CreateHeartbeatTable implements DataPatchInterface
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
    ) {
    }

    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(HeartbeatInterface::TABLE_NAME);

        if ($connection->isTableExists($tableName)) {
            return;
        }

        $table = $connection->newTable($tableName)
            ->addColumn(
                HeartbeatInterface::FIELD_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true,
                ],
                'ID'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_JOB_CODE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Cron Job Code'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_HEARTBEAT_UUID,
                Table::TYPE_TEXT,
                36,
                ['nullable' => true, 'default' => null],
                'Upcron Heartbeat UUID'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_CRON_SCHEDULE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Cron Schedule Expression'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_GRACE_PERIOD_SECONDS,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Grace Period in Seconds'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_PING_URL,
                Table::TYPE_TEXT,
                2048,
                ['nullable' => true, 'default' => null],
                'Upcron Ping URL'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_START_URL,
                Table::TYPE_TEXT,
                2048,
                ['nullable' => true, 'default' => null],
                'Upcron Start URL'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_FAIL_URL,
                Table::TYPE_TEXT,
                2048,
                ['nullable' => true, 'default' => null],
                'Upcron Fail URL'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_IS_EXCLUDED,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '0'],
                'Is Excluded from Monitoring'
            )
            ->addColumn(
                HeartbeatInterface::FIELD_SYNCED_AT,
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true, 'default' => null],
                'Last Synced At'
            )
            ->addIndex(
                'UNQ_UPCRON_MONITOR_HEARTBEAT_JOB_CODE',
                [HeartbeatInterface::FIELD_JOB_CODE],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Upcron Monitor Heartbeat');

        $connection->createTable($table);
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
