<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(private readonly Collection $categories)
    {
    }

    public function collection(): Collection
    {
        return $this->categories;
    }

    public function headings(): array
    {
        return trans('export.categories.headings');
    }

    /**
     * @param  Category  $category
     */
    public function map($category): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $category->name,
            $category->description ?: '-',
            $category->products_count ?? $category->products()->count(),
            $category->is_active ? trans('export.active') : trans('export.inactive'),
            $category->created_at?->format('d.m.Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
