<?php

namespace Application\Quotation\UseCases;

use Application\Quotation\DTOs\CreateQuotationDTO;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Entities\QuotationItem;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\DiscountRate;
use Domain\Quotation\ValueObjects\QuotationNumber;

final readonly class CreateQuotationUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository
    ) {}

    public function execute(CreateQuotationDTO $dto): Quotation
    {
        $quotation = Quotation::create(
            customerId: $dto->customerId,
            quotationNumber: QuotationNumber::fromString($dto->quotationNumber),
            issuedAt: $dto->issuedAt,
            validUntil: $dto->validUntil,
            notes: $dto->notes,
            termsAndConditions: $dto->termsAndConditions,
        );

        // Set discount if provided
        if ($dto->discountPercentage !== '0.00') {
            $quotation->updateDiscount(DiscountRate::fromPercentage($dto->discountPercentage));
        }

        $savedQuotation = $this->quotationRepository->save($quotation);

        // Add items if provided
        if (! empty($dto->items)) {
            $items = [];
            foreach ($dto->items as $itemDTO) {
                $items[] = QuotationItem::create(
                    quotationId: $savedQuotation->id(),
                    description: $itemDTO->description,
                    quantity: $itemDTO->quantity,
                    unitPrice: Money::fromAmount($itemDTO->unitPrice),
                    taxRate: TaxRate::fromPercentage($itemDTO->taxRate),
                );
            }

            $savedQuotation->setItems($items);
            $savedQuotation = $this->quotationRepository->save($savedQuotation);
        }

        return $savedQuotation;
    }
}
