<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET   /api/v1/notifications
 * PATCH /api/v1/notifications/{id}/read
 * PATCH /api/v1/notifications/read-all
 */
class NotificationApiController extends Controller
{
    /** GET /api/v1/notifications */
    public function index(Request $request): JsonResponse
    {
        $recipientId = session('sso_id');

        if (! $recipientId) {
            return response()->json(['success' => false, 'error' => 'unauthenticated'], 401);
        }

        $notifications = Notification::forRecipient($recipientId)
            ->orderByDesc('created_at')
            ->limit((int) $request->integer('limit', 20))
            ->get();

        $unread = Notification::forRecipient($recipientId)->unread()->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unread,
            'data'         => $notifications->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'data'       => $n->data,
                'action_url' => $n->action_url,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /** PATCH /api/v1/notifications/{id}/read */
    public function markRead(string $id): JsonResponse
    {
        $recipientId = session('sso_id');
        $notification = Notification::where('id', $id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $notification->markRead();

        return response()->json([
            'success'      => true,
            'unread_count' => Notification::forRecipient($recipientId)->unread()->count(),
        ]);
    }

    /** PATCH /api/v1/notifications/read-all */
    public function markAllRead(): JsonResponse
    {
        $recipientId = session('sso_id');

        Notification::forRecipient($recipientId)->unread()->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }
}
