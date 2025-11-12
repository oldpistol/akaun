<?php

namespace Application\Invoice\UseCases;

use DateTimeImmutable;
use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;

final readonly class MarkInvoiceAsPaidUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $id, ?DateTimeImmutable $paidAt = null): bool
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        $invoice->markAsPaid($paidAt);

        return $this->invoiceRepository->save($invoice) !== null;
    }
}
