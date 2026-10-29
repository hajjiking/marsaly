<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $rows = SupportTicket::where('user_id', $request->user()->id)
            ->latest()->paginate(20);
        return ApiResponse::success($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'order_id' => $data['order_id'] ?? null,
            'status' => 'open',
        ]);

        app(NotificationService::class)->notifyAdmins('support_ticket', 'notifications.support_ticket', ['ticket_id' => $ticket->id]);

        return ApiResponse::success($ticket, __('api.created'), 201);
    }
}
