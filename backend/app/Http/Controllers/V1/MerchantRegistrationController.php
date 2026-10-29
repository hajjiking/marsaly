<?php

namespace App\Http\Controllers\V1;

use App\Events\NewAdminEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMerchantRequest;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantRegistrationController extends Controller
{
    /** POST /api/v1/merchants/register — after OTP login; needs admin approval. */
    public function store(RegisterMerchantRequest $request)
    {
        $user = $request->user();

        if ($user->hasRole(Role::MERCHANT) && $user->merchant) {
            return ApiResponse::error(__('api.already_registered'), 409);
        }

        $uploads = fn (string $key, string $dir) => $request->hasFile($key)
            ? $request->file($key)->store($dir, 'public')
            : null;

        $merchant = DB::transaction(function () use ($request, $user, $uploads) {
            $merchant = Merchant::create([
                'user_id'          => $user->id,
                'category_id'      => $request->input('category_id'),
                'business_name'    => $request->input('business_name'),
                'description'      => $request->input('description'),
                'phone'            => $user->phone,
                'address'          => $request->input('address'),
                'neighborhood'     => $request->input('neighborhood'),
                'lat'              => $request->input('lat'),
                'lng'              => $request->input('lng'),
                'licence_number'   => $request->input('licence_number'),
                'id_document_path'        => $uploads('id_document', 'merchant-docs/' . Str::random(8)),
                'licence_document_path'   => $uploads('licence_document', 'merchant-docs/' . Str::random(8)),
                'prep_time_minutes'=> $request->input('prep_time_minutes', 20),
                'status'           => 'pending',
            ]);
            $user->forceFill(['role_id' => Role::where('name', Role::MERCHANT)->value('id')])->save();
            return $merchant;
        });

        event(new NewAdminEvent('merchant_registration', [
            'merchant_id'   => $merchant->id,
            'business_name' => $merchant->business_name,
        ]));

        return ApiResponse::success(
            new MerchantResource($merchant->load('category')),
            __('api.merchant_pending'),
            201
        );
    }

    /** GET /api/v1/merchants/me */
    public function mine(Request $request)
    {
        $merchant = $request->user()->merchant;
        if (!$merchant) return ApiResponse::error(__('api.not_found'), 404);
        return ApiResponse::success(
            new MerchantResource($merchant->load(['category', 'hours', 'zone']))
        );
    }
}
