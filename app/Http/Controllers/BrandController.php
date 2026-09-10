<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Mahsulot formasidan "tez qo'shish" orqali yangi brend yaratadi.
     * Nom normallashtirilib (trim + case-insensitive) mavjud brend
     * qidiriladi — topilsa o'shani, topilmasa yangisini qaytaradi.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);

        $brand = Brand::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();

        if (!$brand) {
            $brand = Brand::create(['name' => $name, 'is_active' => true]);
        }

        return response()->json(['id' => $brand->id, 'name' => $brand->name]);
    }
}
