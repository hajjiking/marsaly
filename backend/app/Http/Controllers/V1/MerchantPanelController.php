<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Merchant;
use App\Models\MenuSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerchantPanelController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    private function merchant(Request $request): Merchant
    {
        $m = $request->user()->merchant;
        abort_if(!$m, 403, 'No merchant profile');
        return $m;
    }

    /** PATCH /api/v1/merchant/profile — info + hours */
    public function updateProfile(Request $request)
    {
        $m = $this->merchant($request);
        $data = $request->validate([
            'business_name'     => ['sometimes', 'string', 'max:160'],
            'description'       => ['sometimes', 'nullable', 'string', 'max:1000'],
            'category_id'       => ['sometimes', 'exists:categories,id'],
            'address'           => ['sometimes', 'string', 'max:255'],
            'neighborhood'      => ['sometimes', 'string', 'max:120'],
            'lat'               => ['sometimes', 'numeric'],
            'lng'               => ['sometimes', 'numeric'],
            'prep_time_minutes' => ['sometimes', 'integer', 'min:0', 'max:180'],
            'logo'              => ['sometimes', 'image', 'max:5120'],
            'cover'             => ['sometimes', 'image', 'max:5120'],
        ]);
        if ($request->hasFile('logo')) $data['logo_path'] = $request->file('logo')->store('merchants', 'public');
        if ($request->hasFile('cover')) $data['cover_path'] = $request->file('cover')->store('merchants', 'public');
        $m->update($data);

        return ApiResponse::success(new MerchantResource($m->load(['category', 'hours', 'zone'])), __('api.updated'));
    }

    /** PUT /api/v1/merchant/hours  [{day_of_week, opens_at, closes_at, is_closed}] */
    public function updateHours(Request $request)
    {
        $m = $this->merchant($request);
        $rows = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['sometimes', 'boolean'],
        ])['hours'];
        foreach ($rows as $row) {
            $m->hours()->updateOrCreate(
                ['day_of_week' => $row['day_of_week']],
                ['opens_at' => $row['opens_at'] ?? null, 'closes_at' => $row['closes_at'] ?? null, 'is_closed' => $row['is_closed'] ?? false]
            );
        }
        return ApiResponse::success(null, __('api.updated'));
    }

    /** POST /api/v1/merchant/status — open/close toggle without touching hours */
    public function toggleBusy(Request $request)
    {
        $m = $this->merchant($request);
        $data = $request->validate(['is_busy' => ['required', 'boolean']]);
        $m->update($data);
        return ApiResponse::success(new MerchantResource($m), __('api.updated'));
    }

    /** GET /api/v1/merchant/orders?status=active */
    public function orders(Request $request)
    {
        $q = Order::where('merchant_id', $this->merchant($request)->id)
            ->with(['items', 'client:id,name,phone', 'driver.user:id,name,phone']);
        if ($status = $request->input('status')) {
            $status === 'active'
                ? $q->whereIn('status', config('marsaly.orders.active_statuses'))
                : $q->where('status', $status);
        }
        return ApiResponse::success(OrderResource::collection($q->latest()->paginate(20)));
    }

    /** POST /api/v1/merchant/orders/{order}/status {to} — accept|reject|preparing|ready (state machine enforced). */
    public function orderStatus(Request $request, Order $order)
    {
        $m = $this->merchant($request);
        abort_unless($order->merchant_id === $m->id, 403);
        $data = $request->validate([
            'to'    => ['required', Rule::in(['accepted', 'rejected', 'preparing', 'ready', 'cancelled'])],
            'note'  => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $this->orders->changeStatus($order, $data['to'], $request->user(), $data['note'] ?? null);
        } catch (\App\Exceptions\InvalidOrderTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }
        return ApiResponse::success(new OrderResource($order->fresh(['items', 'statusHistory'])), __('api.updated'));
    }

    /** GET /api/v1/merchant/menu — sections with products */
    public function menu(Request $request)
    {
        $sections = $this->merchant($request)->menuSections()
            ->orderBy('sort_order')->with(['products.options.values'])->get();
        return ApiResponse::success($sections->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'sort_order' => $s->sort_order, 'is_active' => (bool) $s->is_active,
            'products' => ProductResource::collection($s->products)->resolve(),
        ]));
    }

    /** POST /api/v1/merchant/menu/sections {name:{ar,fr}, sort_order} */
    public function storeSection(Request $request)
    {
        $m = $this->merchant($request);
        $data = $request->validate([
            'name' => ['required'], // accepts string or {ar,fr}
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $section = $m->menuSections()->create($data);
        return ApiResponse::success($section, __('api.created'), 201);
    }

    /** PUT /api/v1/merchant/menu/sections/{section} | DELETE */
    public function updateSection(Request $request, MenuSection $section)
    {
        abort_unless($section->merchant_id === $this->merchant($request)->id, 403);
        $data = $request->validate([
            'name' => ['sometimes'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $section->update($data);
        return ApiResponse::success($section, __('api.updated'));
    }

    public function destroySection(Request $request, MenuSection $section)
    {
        abort_unless($section->merchant_id === $this->merchant($request)->id, 403);
        $section->delete();
        return ApiResponse::success(null, __('api.removed'));
    }

    /** POST /api/v1/merchant/products — create with options in one call */
    public function storeProduct(Request $request)
    {
        $m = $this->merchant($request);
        $data = $request->validate([
            'menu_section_id' => ['nullable', Rule::exists('menu_sections', 'id')->where('merchant_id', $m->id)],
            'name'        => ['required'], // string or {ar,fr}
            'description' => ['nullable', 'string', 'max:1000'],
            'price'       => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'image'       => ['nullable', 'image', 'max:5120'],
            'in_stock'    => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'tags'        => ['sometimes', 'array'],
            'options'     => ['sometimes', 'array'],
            'options.*.name' => ['required_with:options'],
            'options.*.type' => ['required_with:options', Rule::in(['single', 'multiple'])],
            'options.*.required' => ['sometimes', 'boolean'],
            'options.*.values' => ['required_with:options', 'array'],
            'options.*.values.*.name' => ['required'],
            'options.*.values.*.price_delta' => ['sometimes', 'numeric'],
            'options.*.values.*.is_default' => ['sometimes', 'boolean'],
        ]);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('products', 'public');

        $product = null;
        Illuminate\Support\Facades\DB::transaction(function () use ($data, $m, &$product) {
            $options = $data['options'] ?? [];
            unset($data['options']);
            $product = $m->products()->create($data);
            foreach ($options as $i => $opt) {
                $option = ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $opt['name'],
                    'type' => $opt['type'],
                    'required' => $opt['required'] ?? false,
                    'sort_order' => $i,
                ]);
                foreach (($opt['values'] ?? []) as $j => $val) {
                    ProductOptionValue::create([
                        'product_option_id' => $option->id,
                        'name' => $val['name'],
                        'price_delta' => $val['price_delta'] ?? 0,
                        'is_default' => $val['is_default'] ?? false,
                        'sort_order' => $j,
                    ]);
                }
            }
        });

        return ApiResponse::success(new ProductResource($product->load('options.values')), __('api.created'), 201);
    }

    /** PUT /api/v1/merchant/products/{product} */
    public function updateProduct(Request $request, Product $product)
    {
        abort_unless($product->merchant_id === $this->merchant($request)->id, 403);
        $data = $request->validate([
            'menu_section_id' => ['nullable', Rule::exists('menu_sections', 'id')->where('merchant_id', $product->merchant_id)],
            'name' => ['sometimes'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'image' => ['sometimes', 'image', 'max:5120'],
            'in_stock' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('products', 'public');
        $product->update($data);
        return ApiResponse::success(new ProductResource($product->load('options.values')), __('api.updated'));
    }

    /** DELETE /api/v1/merchant/products/{product} */
    public function destroyProduct(Request $request, Product $product)
    {
        abort_unless($product->merchant_id === $this->merchant($request)->id, 403);
        $product->delete();
        return ApiResponse::success(null, __('api.removed'));
    }

    /** GET /api/v1/merchant/reviews + POST reply */
    public function reviews(Request $request)
    {
        $reviews = $this->merchant($request)->reviews()->with('user:id,name')->latest()->paginate(20);
        return ApiResponse::success(ReviewResource::collection($reviews));
    }

    public function replyReview(Request $request, \App\Models\Review $review)
    {
        abort_unless($review->merchant_id === $this->merchant($request)->id, 403);
        $data = $request->validate(['reply' => ['required', 'string', 'max:500']]);
        $review->update(['merchant_reply' => $data['reply'], 'replied_at' => now()]);
        return ApiResponse::success(new ReviewResource($review), __('api.updated'));
    }
}
