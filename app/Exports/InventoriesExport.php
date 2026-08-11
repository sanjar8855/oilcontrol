<?php

namespace App\Exports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(private readonly Collection $inventories)
    {
    }

    public function collection(): Collection
    {
        return $this->inventories;
    }

    public function headings(): array
    {
        return trans('export.inventories.headings');
    }

    /**
     * @param  Inventory  $inventory
     */
    public function map($inventory): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $inventory->started_at?->format('d.m.Y H:i'),
            $inventory->branch?->name ?: trans('export.inventories.all_branches'),
            $inventory->user?->name ?: '-',
            $inventory->counted_items_count ?? 0,
            $inventory->items_count ?? 0,
            $inventory->status === 'completed' ? trans('export.inventories.completed') : trans('export.inventories.in_progress'),
            $inventory->completed_at?->format('d.m.Y H:i') ?: '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
