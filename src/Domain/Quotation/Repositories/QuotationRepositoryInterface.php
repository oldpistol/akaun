<?php

namespace Domain\Quotation\Repositories;

use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\ValueObjects\QuotationNumber;

interface QuotationRepositoryInterface
{
    public function findById(int $id): ?Quotation;

    public function findByUuid(Uuid $uuid): ?Quotation;

    public function findByQuotationNumber(QuotationNumber $quotationNumber): ?Quotation;

    /**
     * @return array<Quotation>
     */
    public function findByCustomerId(int $customerId): array;

    /**
     * @return array<Quotation>
     */
    public function all(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Quotation>
     */
    public function search(array $filters): array;

    public function save(Quotation $quotation): Quotation;

    public function delete(Quotation $quotation): bool;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;

    public function count(): int;

    public function nextQuotationNumber(): string;
}
