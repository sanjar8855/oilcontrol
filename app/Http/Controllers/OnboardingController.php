<?php
// app/Http/Controllers/OnboardingController.php
namespace App\Http\Controllers;

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

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->currentWorkshop()->advanceOnboarding(null);

        return redirect()->route('dashboard');
    }
}
