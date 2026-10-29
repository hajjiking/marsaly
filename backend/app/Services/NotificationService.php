<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

class NotificationService
{
    /** Persist an in-app notification and queue FCM push for the user. */
    public function notify(User $user, string $type, string $titleKey, array $data = [], array $bodyReplacements = []): void
    {
        $locale = $user->locale ?: config('marsaly.localization.default');
        $title = Lang::get($titleKey, $bodyReplacements, $locale);
        $brand = config('marsaly.brand.name');

        $row = Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => "{$brand}: {$title}",
            'body'    => $data['body'] ?? null,
            'data'    => $data,
        ]);

        SendPushNotification::dispatch($user, [
            'title' => $row->title,
            'body'  => $data['push_body'] ?? ($data['body'] ?? $title),
            'data'  => $data + ['notification_id' => $row->id, 'type' => $type],
        ]);
    }
}
