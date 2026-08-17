<?php
declare(strict_types=1);

namespace Gemacode\Govp\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

final class ExchangeClient
{
    public function __construct(private readonly Curl $curl, private readonly Json $json) {}

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function issue(string $baseUrl, string $token, string $idempotencyKey, array $payload): array
    {
        $url = rtrim($baseUrl, '/') . '/connectors/issue';
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new \InvalidArgumentException('GOVP Exchange requiere HTTPS.');
        }
        if (!preg_match('/^gx_[A-Za-z0-9_-]{32,128}$/', $token)) {
            throw new \InvalidArgumentException('Token GOVP no válido.');
        }
        $this->curl->setTimeout(15);
        $this->curl->setHeaders([
            'Accept' => 'application/json', 'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token, 'Idempotency-Key' => $idempotencyKey,
        ]);
        $this->curl->post($url, $this->json->serialize($payload));
        $status = $this->curl->getStatus();
        $body = $this->json->unserialize($this->curl->getBody());
        if ($status < 200 || $status >= 300 || !is_array($body) || !isset($body['govp']['code'], $body['govp']['verifyUrl'])) {
            throw new \RuntimeException('GOVP Exchange HTTP ' . $status);
        }
        return $body;
    }
}
