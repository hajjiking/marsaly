<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\ProductResource;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Support\ApiResponse;
use App\Support\FeeCalculator;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** GET /api/v1/categories */
    public function categories()
    {
        $cats = Category::query()->where('is_active', true)->withCount(['merchants' => fn ($q) => $q->where('status', 'approved')])
            ->orderBy('sort_order')->get();
        return ApiResponse::success(CategoryResource::collection($cats));
    }

    /**
     * GET /api/v1/merchants — browse with filters:
     *  category_id, search, open_now, min_rating, max_delivery_fee, sort(rating|delivery_fee|distance), lat, lng
     */
    public function merchants(Request $request)
    {
        $q = Merchant::query()
            ->where('status', 'approved')
            ->with(['category', 'zone']);

        if ($cat = $request->integer('category_id')) {
            $q->where('category_id', $cat);
        }
        if ($search = trim((string) $request->input('search'))) {
            $q->where(fn ($w) => $w
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('neighborhood', 'like', "%{$search}%")
                ->orWhereHas('products', fn ($p) => $p->whereJsonContains('name->fr', $search)
                    ->orWhereJsonContains('name->ar', $search)));
        }
        if ($request->boolean('open_now')) {
            // cheap pre-filter by day; exact check done in the collection map
            $day = now()->dayOfWeek;
            $q->whereHas('hours', fn ($h) => $h->where('day_of_week', $day)->where('is_closed', false));
        }
        if ($min = $request->float('min_rating')) {
            $q->where('rating_avg', '>=', $min);
        }
        if ($maxFee = $request->float('max_delivery_fee')) {
            $q->whereHas('zone', fn ($z) => $z->where('base_fee', '<=', $maxFee));
        }

        $sort = $request->input('sort', 'rating');
        match ($sort) {
            'rating' => $q->orderByDesc('rating_avg'),
            default  => $q->orderByDesc('rating_avg'),
        };

        $merchants = $q->paginate(min(50, $request->integer('per_page', 20)));

        // post-filter open_now & compute distance when coordinates provided
        $items = $merchants->getCollection()->map(function (Merchant $m) use ($request) {
            if ($request->boolean('open_now') && !$m->isOpenNow()) {
                return null;
            }
            $r = new MerchantResource($m);
            if ($request->filled('lat') && $request->filled('lng')) {
                $dist = FeeCalculator::distanceKm($m, (float) $request->input('lat'), (float) $request->input('lng'));
                $r['distance_km'] = $dist;
                $r['estimated_delivery_fee'] = FeeCalculator::deliveryFee($m, $dist);
            }
            return $r;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'page'          => $merchants->currentPage(),
                'per_page'      => $merchants->perPage(),
                'total'         => $merchants->total(),
                'last_page'     => $merchants->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/merchants/{merchant} — public page with full menu */
    public function merchant(Merchant $merchant)
    {
        abort_unless($merchant->status === 'approved', 404);
        $merchant->load(['category', 'zone', 'hours', 'menuSections' => fn ($q) => $q->where('is_active', true)->with(['products' => fn ($p) => $p->whereNull('deleted_at')->with('options.values')])]);
        $resource = new MerchantResource($merchant);
        $resource['menu'] = $merchant->menuSections->map(fn ($s) => [
            'id'   => $s->id,
            'name' => $s->name,
            'products' => ProductResource::collection($s->products)->resolve(),
        ]);
        $resource['minimum_order'] = FeeCalculator::minimumOrder($merchant);
        return ApiResponse::success($resource);
    }

    /** GET /api/v1/products/{product} */
    public function product(Product $product)
    {
        abort_unless($product->merchant?->status === 'approved', 404);
        return ApiResponse::success(new ProductResource($product->load(['options.values'])));
    }

    /** GET /api/v1/banners */
    public function banners()
    {
        $banners = Banner::active()->get();
        return ApiResponse::success(BannerResource::collection($banners));
    }

    /** GET /api/v1/announcements */
    public function announcements(Request $request)
    {
        $audience = $request->user()?->role?->name ?? 'client';
        $rows = Announcement::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where('audience', 'all')
            ->orWhere('audience', $audience)
            ->latest()->limit(10)->get();
        return ApiResponse::success(AnnouncementResource::collection($rows));
    }
}
