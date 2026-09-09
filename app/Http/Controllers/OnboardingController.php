<?php
// app/Http/Controllers/OnboardingController.php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\GlobalProductMatcher;
use App\Services\ProductCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function products(Request $request, GlobalProductMatcher $matcher): Response
    {
        $search = $request->filled('search') ? $request->string('search')->toString() : null;

        return Inertia::render('Onboarding/Products', [
            'products' => $matcher->catalogQuery($search)->get(['id', 'name', 'unit']),
        ]);
    }

    public function storeProducts(Request $request, ProductCreationService $productCreationService): RedirectResponse
    {
        $validated = $request->validate([
            'global_product_ids' => 'required|array|min:1',
            'global_product_ids.*' => 'integer|exists:global_products,id',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        abort_unless($workshop, 403);

        $items = collect($validated['global_product_ids'])->map(fn ($id) => [
            'global_product_id' => $id,
            'purchase_price' => 0,
            'selling_price' => 0,
            'stock_quantity' => 0,
            'min_stock_level' => 0,
        ])->all();

        DB::beginTransaction();
        try {
            $productCreationService->copySelectionToWorkshop($workshop, $user, $items, $user->branch_id);
            $workshop->advanceOnboarding('vehicle');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
        }

        return redirect()->route('onboarding.vehicle');
    }

    public function vehicle(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop, 403);

        return Inertia::render('Onboarding/Vehicle');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'make' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $workshop = $user->currentWorkshop();

        abort_unless($workshop, 403);

        $client = $workshop->clients()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'branch_id' => $user->branch_id,
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'plate_number' => $validated['plate_number'],
            'make' => $validated['make'],
        ]);

        $workshop->advanceOnboarding('sale');

        return redirect()->route('vehicles.show', $vehicle);
    }

    public function skip(Request $request): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop, 403);

        $workshop->advanceOnboarding(null);

        return redirect()->route('dashboard');
    }
}
