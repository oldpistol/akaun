<?php

namespace Application\Quotation\UseCases;

use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;

final readonly class DeclineQuotationUseCase
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

        $quotation->decline();

        return $this->quotationRepository->save($quotation);
    }
}
