<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(private readonly Collection $products)
    {
    }

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return trans('export.products.headings');
    }

    /**
     * @param  Product  $product
     */
    public function map($product): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $product->name,
            $product->sku ?: '-',
            $product->category?->name ?: '-',
            $product->unit,
            $product->stock_quantity,
            $product->min_stock_level,
            number_format($product->getPurchasePrice(), 2, '.', ' '),
            number_format($product->getSellingPrice(), 2, '.', ' '),
            $product->currency,
            $product->supplier ?: '-',
            $product->is_active ? trans('export.active') : trans('export.inactive'),
            $product->created_at?->format('d.m.Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
