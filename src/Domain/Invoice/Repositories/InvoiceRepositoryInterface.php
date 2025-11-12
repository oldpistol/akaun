<?php

namespace Domain\Invoice\Repositories;

use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\ValueObjects\InvoiceNumber;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice;

    public function findByUuid(Uuid $uuid): ?Invoice;

    public function findByInvoiceNumber(InvoiceNumber $invoiceNumber): ?Invoice;

    /**
     * @return array<Invoice>
     */
    public function findByCustomerId(int $customerId): array;

    /**
     * @return array<Invoice>
     */
    public function all(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Invoice>
     */
    public function search(array $filters): array;

    public function save(Invoice $invoice): Invoice;

    public function delete(Invoice $invoice): bool;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;

    public function count(): int;

    public function nextInvoiceNumber(): string;
}
