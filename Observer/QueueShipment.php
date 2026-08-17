<?php
declare(strict_types=1);

namespace Gemacode\Govp\Observer;

use Gemacode\Govp\Model\JobRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

final class QueueShipment implements ObserverInterface
{
    public function __construct(
        private readonly JobRepository $jobs,
        private readonly PublisherInterface $publisher,
        private readonly ScopeConfigInterface $config,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(Observer $observer): void
    {
        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment instanceof Shipment || !$shipment->getEntityId()
            || !$this->config->isSetFlag('govp/general/enabled', ScopeInterface::SCOPE_STORE, (int)$shipment->getStoreId())) {
            return;
        }
        try {
            if ($this->jobs->queue((int)$shipment->getEntityId())) {
                $this->publisher->publish('gemacode.govp.shipment.process', (string)$shipment->getEntityId());
            }
        } catch (\Throwable $error) {
            $this->logger->error('GOVP shipment queue failed', ['shipment_id' => $shipment->getEntityId(), 'exception' => $error]);
        }
    }
}
