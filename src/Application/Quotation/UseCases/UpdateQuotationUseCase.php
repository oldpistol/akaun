<?php

namespace Application\Quotation\UseCases;

use Application\Quotation\DTOs\UpdateQuotationDTO;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Entities\QuotationItem;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\DiscountRate;

final readonly class UpdateQuotationUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository
    ) {}

    public function execute(int $quotationId, UpdateQuotationDTO $dto): Quotation
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if ($quotation === null) {
            throw QuotationNotFoundException::withId($quotationId);
        }

        if ($dto->validUntil !== null) {
            $quotation->updateValidUntil($dto->validUntil);
        }

        if ($dto->notes !== null) {
            $quotation->updateNotes($dto->notes);
        }

        if ($dto->termsAndConditions !== null) {
            $quotation->updateTermsAndConditions($dto->termsAndConditions);
        }

        if ($dto->discountPercentage !== null) {
            $quotation->updateDiscount(DiscountRate::fromPercentage($dto->discountPercentage));
        }

        if ($dto->items !== null) {
            $items = [];
            foreach ($dto->items as $itemDTO) {
                $items[] = QuotationItem::create(
                    quotationId: $quotation->id(),
                    description: $itemDTO->description,
                    quantity: $itemDTO->quantity,
                    unitPrice: Money::fromAmount($itemDTO->unitPrice),
                    taxRate: TaxRate::fromPercentage($itemDTO->taxRate),
                );
            }
            $quotation->setItems($items);
        }

        return $this->quotationRepository->save($quotation);
    }
}
