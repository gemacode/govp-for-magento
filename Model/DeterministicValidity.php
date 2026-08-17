<?php
declare(strict_types=1);

namespace Gemacode\Govp\Model;

final class DeterministicValidity
{
    public static function fromCreatedAt(string $createdAt, int $days): string
    {
        $createdAt = trim($createdAt);
        if ($createdAt === '') {
            throw new \InvalidArgumentException('La expedición no contiene una fecha de creación válida.');
        }

        try {
            $created = new \DateTimeImmutable($createdAt, new \DateTimeZone('UTC'));
        } catch (\Exception $error) {
            throw new \InvalidArgumentException('La expedición no contiene una fecha de creación válida.', 0, $error);
        }

        $days = max(1, min(3650, $days));
        return $created
            ->setTimezone(new \DateTimeZone('UTC'))
            ->modify(sprintf('+%d days', $days))
            ->format(\DateTimeInterface::ATOM);
    }
}
