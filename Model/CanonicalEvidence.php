<?php
declare(strict_types=1);

namespace Gemacode\Govp\Model;

final class CanonicalEvidence
{
    /** @param array<int,array{line:int,sku:string,quantity:string}> $lines */
    public static function hash(int $shipmentId, string $incrementId, int $storeId, array $lines): string
    {
        usort($lines, static fn (array $left, array $right): int => $left['line'] <=> $right['line']);
        $payload = ['shipmentId' => $shipmentId, 'incrementId' => $incrementId, 'storeId' => $storeId, 'lines' => $lines];
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
