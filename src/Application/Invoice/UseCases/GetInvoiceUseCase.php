<?php

namespace Application\Invoice\UseCases;

use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;

final readonly class GetInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        return $invoice;
    }
}
