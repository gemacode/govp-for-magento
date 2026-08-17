<?php
declare(strict_types=1);

namespace Gemacode\Govp\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Store\Model\ScopeInterface;

final class ShipmentConsumer
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ScopeConfigInterface $config,
        private readonly EncryptorInterface $encryptor,
        private readonly ExchangeClient $exchange,
        private readonly JobRepository $jobs
    ) {}

    public function process(string $shipmentId): void
    {
        $id = filter_var($shipmentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $this->jobs->claimedOrComplete((int)$id)) {
            return;
        }
        try {
            $shipment = $this->shipments->get((int)$id);
            $storeId = (int)$shipment->getStoreId();
            if (!$this->config->isSetFlag('govp/general/enabled', ScopeInterface::SCOPE_STORE, $storeId)) {
                throw new \RuntimeException('GOVP está desactivado para esta tienda.');
            }
            $baseUrl = (string)$this->config->getValue('govp/general/exchange_url', ScopeInterface::SCOPE_STORE, $storeId);
            $token = $this->encryptor->decrypt((string)$this->config->getValue('govp/general/token', ScopeInterface::SCOPE_STORE, $storeId));
            $issuer = trim((string)$this->config->getValue('govp/general/issuer_name', ScopeInterface::SCOPE_STORE, $storeId));
            $days = max(1, min(3650, (int)$this->config->getValue('govp/general/validity_days', ScopeInterface::SCOPE_STORE, $storeId)));
            if ($issuer === '') {
                throw new \RuntimeException('Falta el nombre del emisor GOVP.');
            }
            $lines = [];
            foreach ($shipment->getItems() as $item) {
                $lines[] = ['line' => (int)$item->getOrderItemId(), 'sku' => (string)$item->getSku(), 'quantity' => self::decimal((float)$item->getQty())];
            }
            $incrementId = (string)$shipment->getIncrementId();
            $hash = CanonicalEvidence::hash((int)$id, $incrementId, $storeId, $lines);
            $validUntil = DeterministicValidity::fromCreatedAt((string)$shipment->getCreatedAt(), $days);
            $result = $this->exchange->issue($baseUrl, $token, sprintf('magento:store:%d:shipment:%d', $storeId, $id), [
                'issuer' => ['name' => $issuer],
                'subject' => ['type' => 'shipment', 'id' => $incrementId, 'name' => 'Magento shipment ' . $incrementId, 'description' => count($lines) . ' shipped lines'],
                'requirement' => 'Demuestra una expedición de Magento o Adobe Commerce mediante una huella de sus posiciones mínimas.',
                'evidence' => [['label' => 'Huella canónica de la expedición Magento', 'sha256' => $hash]],
                'validUntil' => $validUntil,
                'source' => ['platform' => 'magento', 'externalId' => 'shipment-' . $id],
            ]);
            $this->jobs->success((int)$id, (string)$result['govp']['code'], (string)$result['govp']['verifyUrl']);
        } catch (\Throwable $error) {
            $this->jobs->failure((int)$id, $error->getMessage());
            throw $error;
        }
    }

    private static function decimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
