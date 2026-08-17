<?php
declare(strict_types=1);

namespace Gemacode\Govp\Model;

use Magento\Framework\App\ResourceConnection;

final class JobRepository
{
    private const PROCESSING_LEASE_MINUTES = 15;

    public function __construct(private readonly ResourceConnection $resource) {}

    public function queue(int $shipmentId): bool
    {
        $connection = $this->resource->getConnection();
        return $connection->insertOnDuplicate(
            $this->resource->getTableName('gemacode_govp_shipment'),
            ['shipment_id' => $shipmentId, 'status' => 'queued'],
            ['shipment_id']
        ) === 1;
    }

    public function claimedOrComplete(int $shipmentId): bool
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('gemacode_govp_shipment');
        $status = $connection->fetchOne($connection->select()->from($table, ['status'])->where('shipment_id = ?', $shipmentId));
        if ($status === 'issued') {
            return true;
        }
        $where = $connection->quoteInto('shipment_id = ?', $shipmentId)
            . " AND (status IN ('queued','attention') OR (status = 'processing' AND updated_at < CURRENT_TIMESTAMP - INTERVAL "
            . self::PROCESSING_LEASE_MINUTES
            . ' MINUTE))';
        return $connection->update($table, ['status' => 'processing', 'attempts' => new \Zend_Db_Expr('attempts + 1'), 'last_error' => null], $where) === 0;
    }

    public function success(int $shipmentId, string $code, string $verifyUrl): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName('gemacode_govp_shipment'),
            ['status' => 'issued', 'govp_code' => $code, 'verify_url' => $verifyUrl, 'last_error' => null],
            ['shipment_id = ?' => $shipmentId]
        );
    }

    public function failure(int $shipmentId, string $message): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName('gemacode_govp_shipment'),
            ['status' => 'attention', 'last_error' => mb_substr($message, 0, 2048)],
            ['shipment_id = ?' => $shipmentId]
        );
    }
}
