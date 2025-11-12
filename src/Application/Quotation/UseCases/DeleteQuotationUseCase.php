<?php

namespace Application\Quotation\UseCases;

use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;

final readonly class DeleteQuotationUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository
    ) {}

    public function execute(int $quotationId): bool
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if ($quotation === null) {
            throw QuotationNotFoundException::withId($quotationId);
        }

        $quotation->delete();

        return $this->quotationRepository->save($quotation) !== null;
    }
}
