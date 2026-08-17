# Changelog

## 0.1.1 — 2026-08-17

- Make `validUntil` deterministic across idempotent retries by deriving it from
  the shipment creation timestamp.
- Reclaim `processing` jobs after a bounded 15-minute lease so a terminated
  consumer cannot strand a shipment forever.
- Extend deterministic checks for retry payloads and lease recovery.

## 0.1.0 — 2026-08-17

- First Magento Open Source / Adobe Commerce module candidate.
- Shipment observer, asynchronous queue, atomic job state and idempotent issue.
- Encrypted configuration, canonical minimal evidence and native acceptance plan.
