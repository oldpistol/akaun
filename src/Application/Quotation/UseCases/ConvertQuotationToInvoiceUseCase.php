<?php

namespace Application\Quotation\UseCases;

use Application\Invoice\DTOs\CreateInvoiceDTO;
use Application\Invoice\DTOs\CreateInvoiceItemDTO;
use Application\Invoice\UseCases\CreateInvoiceUseCase;
use DateTimeImmutable;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;

final readonly class ConvertQuotationToInvoiceUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository,
        private InvoiceRepositoryInterface $invoiceRepository,
        private CreateInvoiceUseCase $createInvoiceUseCase,
    ) {}

    public function execute(int $quotationId, ?string $invoiceNumber = null, ?DateTimeImmutable $dueAt = null): Invoice
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if ($quotation === null) {
            throw QuotationNotFoundException::withId($quotationId);
        }

        // Convert quotation items to invoice items
        $invoiceItems = [];
        foreach ($quotation->items() as $item) {
            $invoiceItems[] = new CreateInvoiceItemDTO(
                description: $item->description(),
                quantity: $item->quantity(),
                unitPrice: $item->unitPrice()->amount(),
                taxRate: $item->taxRate()->value(),
            );
        }

        // Create invoice from quotation
        $invoiceDTO = new CreateInvoiceDTO(
            customerId: $quotation->customerId(),
            invoiceNumber: $invoiceNumber ?? $this->invoiceRepository->nextInvoiceNumber(),
            issuedAt: new DateTimeImmutable,
            dueAt: $dueAt ?? (new DateTimeImmutable)->modify('+30 days'),
            notes: $quotation->notes(),
            items: $invoiceItems,
        );

        $invoice = $this->createInvoiceUseCase->execute($invoiceDTO);

        // Mark quotation as converted
        $quotation->markAsConverted($invoice->id());
        $this->quotationRepository->save($quotation);

        return $invoice;
    }
}
