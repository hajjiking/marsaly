<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /notifications — in-app inbox (paginated). */
    public function index(Request $request)
    {
        $rows = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return ApiResponse::success(NotificationResource::collection($rows));
    }

    /** POST /notifications/{id}/read */
    public function read(Request $request, int $id)
    {
        $n = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $n->update(['read_at' => now()]);
        return ApiResponse::success(null, __('api.updated'));
    }

    /** POST /notifications/read-all */
    public function readAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);
        return ApiResponse::success(null, __('api.updated'));
    }

    /** POST /device-tokens {token, platform} — register FCM token for push. */
    public function storeDeviceToken(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'in:android,ios,web'],
        ]);

        DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'], 'last_used_at' => now()],
        );

        return ApiResponse::success(null, __('api.ok'));
    }

    /** DELETE /device-tokens {token} */
    public function destroyDeviceToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $request->input('token'))->delete();
        return ApiResponse::success(null, __('api.ok'));
    }
}
