<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarMakeController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isDirector()) {
            abort(403, 'Sizda bu sahifani ko\'rish uchun ruxsat yo\'q');
        }
    }

    /**
     * Display a listing of car makes with their models.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        $workshop = $request->user()->workshop;

        $carMakes = CarMake::with(['carModels' => function ($query) use ($workshop) {
            $query->orderBy('name')->with(['products' => function ($query) use ($workshop) {
                $query->when($workshop, fn ($q) => $q->where('products.workshop_id', $workshop->id));
            }]);
        }])
            ->orderBy('name')
            ->get();

        $products = $workshop
            ? $workshop->products()->orderBy('name')->get(['id', 'name', 'unit', 'selling_price'])
            : [];

        return Inertia::render('CarMakes/Index', [
            'carMakes' => $carMakes,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created car make.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_makes,name',
        ]);

        CarMake::create($validated);

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil markasi qo\'shildi!');
    }

    /**
     * Update the specified car make.
     */
    public function update(Request $request, CarMake $carMake): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_makes,name,' . $carMake->id,
        ]);

        $carMake->update($validated);

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil markasi yangilandi!');
    }

    /**
     * Remove the specified car make (and its models).
     */
    public function destroy(Request $request, CarMake $carMake): RedirectResponse
    {
        $this->authorizeAccess($request);

        $carMake->delete();

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil markasi o\'chirildi!');
    }

    /**
     * Store a newly created car model under a make.
     */
    public function storeModel(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'car_make_id' => 'required|exists:car_makes,id',
            'name' => 'required|string|max:255',
            'oil_capacity_liters' => 'nullable|numeric|min:0|max:99.99',
            'antifreeze_capacity_min_liters' => 'nullable|numeric|min:0|max:99.99',
            'antifreeze_capacity_max_liters' => 'nullable|numeric|min:0|max:99.99',
        ]);

        $exists = CarModel::where('car_make_id', $validated['car_make_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return redirect()->route('car-makes.index')
                ->with('error', 'Bu marka uchun bunday nom allaqachon mavjud!');
        }

        CarModel::create($validated);

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil turi qo\'shildi!');
    }

    /**
     * Update the specified car model.
     */
    public function updateModel(Request $request, CarModel $carModel): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'oil_capacity_liters' => 'nullable|numeric|min:0|max:99.99',
            'antifreeze_capacity_min_liters' => 'nullable|numeric|min:0|max:99.99',
            'antifreeze_capacity_max_liters' => 'nullable|numeric|min:0|max:99.99',
        ]);

        $exists = CarModel::where('car_make_id', $carModel->car_make_id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $carModel->id)
            ->exists();

        if ($exists) {
            return redirect()->route('car-makes.index')
                ->with('error', 'Bu marka uchun bunday nom allaqachon mavjud!');
        }

        $carModel->update($validated);

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil turi yangilandi!');
    }

    /**
     * Remove the specified car model.
     */
    public function destroyModel(Request $request, CarModel $carModel): RedirectResponse
    {
        $this->authorizeAccess($request);

        $carModel->delete();

        return redirect()->route('car-makes.index')
            ->with('success', 'Avtomobil turi o\'chirildi!');
    }

    /**
     * Link a workshop product to a car model with a default quantity.
     */
    public function attachProduct(Request $request, CarModel $carModel): RedirectResponse
    {
        $this->authorizeAccess($request);

        $workshop = $request->user()->workshop;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01|max:9999.99',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        if (!$workshop || $product->workshop_id !== $workshop->id) {
            abort(403);
        }

        $carModel->products()->syncWithoutDetaching([
            $product->id => ['quantity' => $validated['quantity']],
        ]);

        return redirect()->route('car-makes.index')
            ->with('success', 'Mahsulot avtomobil turiga bog\'landi!');
    }

    /**
     * Unlink a workshop product from a car model.
     */
    public function detachProduct(Request $request, CarModel $carModel, Product $product): RedirectResponse
    {
        $this->authorizeAccess($request);

        $workshop = $request->user()->workshop;
        if (!$workshop || $product->workshop_id !== $workshop->id) {
            abort(403);
        }

        $carModel->products()->detach($product->id);

        return redirect()->route('car-makes.index')
            ->with('success', 'Mahsulot bog\'lanishi o\'chirildi!');
    }
}
