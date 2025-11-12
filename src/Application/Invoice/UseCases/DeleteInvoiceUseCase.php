<?php

namespace Application\Invoice\UseCases;

use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;

final readonly class DeleteInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        return $this->invoiceRepository->delete($invoice);
    }
}
