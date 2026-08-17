<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/CanonicalEvidence.php';
require_once __DIR__ . '/../Model/DeterministicValidity.php';

use Gemacode\Govp\Model\CanonicalEvidence;
use Gemacode\Govp\Model\DeterministicValidity;

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$lines = [
    ['line' => 2, 'sku' => 'B', 'quantity' => '2'],
    ['line' => 1, 'sku' => 'A', 'quantity' => '1'],
];
$expected = CanonicalEvidence::hash(42, '000000042', 1, $lines);
$check($expected === CanonicalEvidence::hash(42, '000000042', 1, array_reverse($lines)), 'Canonical hash must ignore input order.');
$changed = $lines; $changed[0]['quantity'] = '3';
$check($expected !== CanonicalEvidence::hash(42, '000000042', 1, $changed), 'Canonical hash must detect quantity changes.');

$validUntil = DeterministicValidity::fromCreatedAt('2026-08-17 10:30:00', 365);
$check($validUntil === '2027-08-17T10:30:00+00:00', 'Validity must be derived deterministically from shipment creation.');
$check($validUntil === DeterministicValidity::fromCreatedAt('2026-08-17T12:30:00+02:00', 365), 'Equivalent timestamps must produce identical validity.');

foreach (glob($root . '/etc/*.xml') ?: [] as $file) {
    $check(simplexml_load_file($file) !== false, basename($file) . ' must be well formed XML.');
}
foreach (glob($root . '/etc/adminhtml/*.xml') ?: [] as $file) {
    $check(simplexml_load_file($file) !== false, basename($file) . ' must be well formed XML.');
}

$files = [
    'registration.php' => ['Gemacode_Govp'],
    'etc/events.xml' => ['sales_order_shipment_save_commit_after'],
    'etc/communication.xml' => ['gemacode.govp.shipment.process'],
    'etc/queue_consumer.xml' => ['ShipmentConsumer::process'],
    'Model/ShipmentConsumer.php' => ["'platform' => 'magento'", 'magento:store:', 'DeterministicValidity::fromCreatedAt'],
    'Model/JobRepository.php' => ["status = 'processing'", 'PROCESSING_LEASE_MINUTES', 'CURRENT_TIMESTAMP'],
    'etc/adminhtml/system.xml' => ['Config\\Model\\Config\\Backend\\Encrypted'],
];
foreach ($files as $name => $fragments) {
    $content = file_get_contents($root . '/' . $name);
    $check($content !== false, $name . ' must exist.');
    foreach ($fragments as $fragment) $check(str_contains((string)$content, $fragment), $name . ' missing ' . $fragment);
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, "GOVP for Magento checks passed: canonical evidence, XML, queue, encrypted configuration, deterministic retries and lease recovery.\n");
