<?php

namespace Application\Invoice\UseCases;

use Application\Invoice\DTOs\CreateInvoiceDTO;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Entities\InvoiceItem;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;
use Domain\Invoice\ValueObjects\InvoiceNumber;
use Domain\Invoice\ValueObjects\TaxRate;

final readonly class CreateInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $invoice = Invoice::create(
            customerId: $dto->customerId,
            invoiceNumber: InvoiceNumber::fromString($dto->invoiceNumber),
            issuedAt: $dto->issuedAt,
            dueAt: $dto->dueAt,
            notes: $dto->notes,
        );

        $savedInvoice = $this->invoiceRepository->save($invoice);

        // Add items if provided
        if (! empty($dto->items)) {
            $items = [];
            foreach ($dto->items as $itemDTO) {
                $items[] = InvoiceItem::create(
                    invoiceId: $savedInvoice->id(),
                    description: $itemDTO->description,
                    quantity: $itemDTO->quantity,
                    unitPrice: Money::fromAmount($itemDTO->unitPrice),
                    taxRate: TaxRate::fromPercentage($itemDTO->taxRate),
                );
            }

            $savedInvoice->setItems($items);
            $savedInvoice = $this->invoiceRepository->save($savedInvoice);
        }

        return $savedInvoice;
    }
}
