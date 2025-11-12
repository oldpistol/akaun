<?php

namespace Infrastructure\Quotation\Repositories;

use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Infrastructure\Quotation\Mappers\QuotationItemMapper;
use Infrastructure\Quotation\Mappers\QuotationMapper;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

class EloquentQuotationRepository implements QuotationRepositoryInterface
{
    private QuotationMapper $mapper;

    public function __construct()
    {
        $itemMapper = new QuotationItemMapper;
        $this->mapper = new QuotationMapper($itemMapper);
    }

    public function findById(int $id): ?Quotation
    {
        $model = QuotationModel::with('items')->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Quotation
    {
        $model = QuotationModel::with('items')
            ->where('uuid', $uuid->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByQuotationNumber(QuotationNumber $quotationNumber): ?Quotation
    {
        $model = QuotationModel::with('items')
            ->where('quotation_number', $quotationNumber->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByCustomerId(int $customerId): array
    {
        return QuotationModel::with('items')
            ->where('customer_id', $customerId)
            ->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function all(): array
    {
        return QuotationModel::with('items')
            ->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function search(array $filters): array
    {
        $query = QuotationModel::with('items');

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['quotation_number'])) {
            $query->where('quotation_number', 'like', "%{$filters['quotation_number']}%");
        }

        if (isset($filters['issued_from'])) {
            $query->where('issued_at', '>=', $filters['issued_from']);
        }

        if (isset($filters['issued_to'])) {
            $query->where('issued_at', '<=', $filters['issued_to']);
        }

        if (isset($filters['from_date'])) {
            $query->where('issued_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('issued_at', '<=', $filters['to_date']);
        }

        if (isset($filters['expired']) && $filters['expired'] === true) {
            $query->where('valid_until', '<', now())
                ->whereNotIn('status', ['accepted', 'declined', 'converted']);
        }

        return $query->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(Quotation $quotation): Quotation
    {
        $model = $this->mapper->toEloquent($quotation);
        $model->save();

        // Handle quotation items
        if (! empty($quotation->items())) {
            $itemMapper = new QuotationItemMapper;
            $itemModels = [];

            foreach ($quotation->items() as $item) {
                $itemModel = $itemMapper->toEloquent($item);
                $itemModels[] = $itemModel;
            }

            // Sync items
            $model->items()->delete();
            foreach ($itemModels as $itemModel) {
                $model->items()->save($itemModel);
            }
        }

        return $this->mapper->toDomain($model->fresh('items'));
    }

    public function delete(Quotation $quotation): bool
    {
        if (! $quotation->id()) {
            return false;
        }

        $model = QuotationModel::find($quotation->id());

        return $model ? $model->delete() : false;
    }

    public function exists(array $criteria): bool
    {
        $query = QuotationModel::query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    public function count(): int
    {
        return QuotationModel::count();
    }

    public function nextQuotationNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastQuotation = QuotationModel::where('quotation_number', 'like', "QUO-{$year}{$month}-%")
            ->orderBy('quotation_number', 'desc')
            ->first();

        if (! $lastQuotation) {
            return "QUO-{$year}{$month}-0001";
        }

        $lastNumber = (int) substr($lastQuotation->quotation_number, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return "QUO-{$year}{$month}-{$nextNumber}";
    }
}
