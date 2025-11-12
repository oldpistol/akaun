<?php

namespace Application\Quotation\UseCases;

use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;

final readonly class ListQuotationsUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository
    ) {}

    /**
     * @return array<Quotation>
     */
    public function execute(): array
    {
        return $this->quotationRepository->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Quotation>
     */
    public function search(array $filters): array
    {
        return $this->quotationRepository->search($filters);
    }
}
