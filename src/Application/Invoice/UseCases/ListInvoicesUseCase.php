<?php

namespace Application\Invoice\UseCases;

use Domain\Invoice\Repositories\InvoiceRepositoryInterface;

final readonly class ListInvoicesUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<\Domain\Invoice\Entities\Invoice>
     */
    public function execute(array $filters = []): array
    {
        if (empty($filters)) {
            return $this->invoiceRepository->all();
        }

        return $this->invoiceRepository->search($filters);
    }
}
