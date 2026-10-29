<?php

namespace App\Http\Controllers\V1;

use App\Events\NewAdminEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterDriverRequest;
use App\Models\Driver;
use App\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DriverRegistrationController extends Controller
{
    /** POST /api/v1/drivers/register — after OTP login; needs admin approval. */
    public function store(RegisterDriverRequest $request)
    {
        $user = $request->user();

        if ($user->hasRole(Role::DRIVER) && $user->driver) {
            return ApiResponse::error(__('api.already_registered'), 409);
        }

        $driver = DB::transaction(function () use ($request, $user) {
            $driver = Driver::create([
                'user_id'               => $user->id,
                'status'                => 'pending',
                'availability'          => 'offline',
                'vehicle_type'          => $request->input('vehicle_type'),
                'vehicle_model'         => $request->input('vehicle_model'),
                'plate_number'          => $request->input('plate_number'),
                'id_document_path'      => $request->file('id_document')->store('driver-docs/' . Str::random(8), 'public'),
                'vehicle_document_path' => $request->hasFile('vehicle_document')
                    ? $request->file('vehicle_document')->store('driver-docs/' . Str::random(8), 'public')
                    : null,
            ]);
            $user->forceFill(['role_id' => Role::where('name', Role::DRIVER)->value('id')])->save();
            return $driver;
        });

        event(new NewAdminEvent('driver_registration', ['driver_id' => $driver->id]));

        return ApiResponse::success([
            'id'        => $driver->id,
            'status'    => $driver->status,
            'vehicle_type' => $driver->vehicle_type,
        ], __('api.driver_pending'), 201);
    }

    /** GET /api/v1/drivers/me */
    public function mine(Request $request)
    {
        $driver = $request->user()->driver;
        if (!$driver) return ApiResponse::error(__('api.not_found'), 404);
        return ApiResponse::success([
            'id'          => $driver->id,
            'status'      => $driver->status,
            'availability'=> $driver->availability,
            'vehicle_type'=> $driver->vehicle_type,
            'vehicle_model' => $driver->vehicle_model,
            'plate_number'=> $driver->plate_number,
            'rating_avg'  => round((float) $driver->rating_avg, 2),
            'cash_collected_total'  => (float) $driver->cash_collected_total,
            'cash_owed_to_platform' => (float) $driver->cash_owed_to_platform,
        ]);
    }
}
