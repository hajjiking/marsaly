<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return ApiResponse::success(
            AddressResource::collection($request->user()->addresses()->orderByDesc('is_default')->get())
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $address = new Address($data);
        $address->user_id = $request->user()->id;
        if ($address->is_default || !$request->user()->addresses()->exists()) {
            $address->is_default = true;
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }
        $address->save();
        return ApiResponse::success(new AddressResource($address), __('api.created'), 201);
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $data = $this->validated($request);
        if (($data['is_default'] ?? false)) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }
        $address->update($data);
        return ApiResponse::success(new AddressResource($address), __('api.updated'));
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $address->delete();
        return ApiResponse::success(null, __('api.removed'));
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label'         => ['nullable', 'string', 'max:60'],
            'contact_name'  => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'neighborhood'  => ['required', 'string', 'max:120'],
            'address_line'  => ['required', 'string', 'max:255'],
            'landmark'      => ['nullable', 'string', 'max:255'],
            'lat'           => ['required', 'numeric', 'between:-90,90'],
            'lng'           => ['required', 'numeric', 'between:-180,180'],
            'is_default'    => ['sometimes', 'boolean'],
        ]);
    }
}
