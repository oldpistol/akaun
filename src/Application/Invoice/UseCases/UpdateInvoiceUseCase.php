<?php

namespace Application\Invoice\UseCases;

use Application\Invoice\DTOs\UpdateInvoiceDTO;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Entities\InvoiceItem;
use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;
use Domain\Invoice\ValueObjects\TaxRate;

final readonly class UpdateInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $id, UpdateInvoiceDTO $dto): Invoice
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        if ($dto->dueAt !== null) {
            $invoice->updateDueDate($dto->dueAt);
        }

        if ($dto->notes !== null) {
            $invoice->updateNotes($dto->notes);
        }

        if ($dto->items !== null) {
            $items = [];
            foreach ($dto->items as $itemDTO) {
                $items[] = InvoiceItem::create(
                    invoiceId: $invoice->id(),
                    description: $itemDTO->description,
                    quantity: $itemDTO->quantity,
                    unitPrice: Money::fromAmount($itemDTO->unitPrice),
                    taxRate: TaxRate::fromPercentage($itemDTO->taxRate),
                );
            }

            $invoice->setItems($items);
        }

        return $this->invoiceRepository->save($invoice);
    }
}
