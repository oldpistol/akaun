<?php

namespace Application\Quotation\UseCases;

use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;

final readonly class GetQuotationUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository
    ) {}

    public function execute(int $quotationId): Quotation
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if ($quotation === null) {
            throw QuotationNotFoundException::withId($quotationId);
        }

        return $quotation;
    }

    public function executeByUuid(string $uuid): Quotation
    {
        $quotation = $this->quotationRepository->findByUuid(Uuid::fromString($uuid));

        if ($quotation === null) {
            throw QuotationNotFoundException::withUuid($uuid);
        }

        return $quotation;
    }
}
