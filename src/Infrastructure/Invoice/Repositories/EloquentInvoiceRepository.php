<?php

namespace Infrastructure\Invoice\Repositories;

use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;
use Domain\Invoice\ValueObjects\InvoiceNumber;
use Infrastructure\Invoice\Mappers\InvoiceItemMapper;
use Infrastructure\Invoice\Mappers\InvoiceMapper;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    private InvoiceMapper $mapper;

    public function __construct()
    {
        $itemMapper = new InvoiceItemMapper;
        $this->mapper = new InvoiceMapper($itemMapper);
    }

    public function findById(int $id): ?Invoice
    {
        $model = InvoiceModel::with('items')->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Invoice
    {
        $model = InvoiceModel::with('items')
            ->where('uuid', $uuid->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByInvoiceNumber(InvoiceNumber $invoiceNumber): ?Invoice
    {
        $model = InvoiceModel::with('items')
            ->where('invoice_number', $invoiceNumber->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByCustomerId(int $customerId): array
    {
        return InvoiceModel::with('items')
            ->where('customer_id', $customerId)
            ->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function all(): array
    {
        return InvoiceModel::with('items')
            ->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function search(array $filters): array
    {
        $query = InvoiceModel::with('items');

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['invoice_number'])) {
            $query->where('invoice_number', 'like', "%{$filters['invoice_number']}%");
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

        if (isset($filters['overdue']) && $filters['overdue'] === true) {
            $query->where('due_at', '<', now())
                ->whereNotIn('status', ['paid', 'cancelled', 'void']);
        }

        return $query->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(Invoice $invoice): Invoice
    {
        $model = $this->mapper->toEloquent($invoice);
        $model->save();

        // Handle invoice items
        if (! empty($invoice->items())) {
            $itemMapper = new InvoiceItemMapper;
            $itemModels = [];

            foreach ($invoice->items() as $item) {
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

    public function delete(Invoice $invoice): bool
    {
        if (! $invoice->id()) {
            return false;
        }

        $model = InvoiceModel::find($invoice->id());

        return $model ? $model->delete() : false;
    }

    public function exists(array $criteria): bool
    {
        $query = InvoiceModel::query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    public function count(): int
    {
        return InvoiceModel::count();
    }

    public function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastInvoice = InvoiceModel::where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (! $lastInvoice) {
            return "INV-{$year}{$month}-0001";
        }

        $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return "INV-{$year}{$month}-{$nextNumber}";
    }
}
