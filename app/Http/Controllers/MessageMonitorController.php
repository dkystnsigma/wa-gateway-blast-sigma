<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Campaign;
use App\Models\IncomingMessage;
use Illuminate\Http\Request;

class MessageMonitorController extends Controller
{
    public function index()
    {
        $devices = Device::where('status', 'Connected')->where('is_active', true)->get();
        $activeCampaigns = Campaign::where('status', 'processing')->with('device')->get();

        return view('pages.message_monitor.index', compact('devices', 'activeCampaigns'));
    }

    public function getMessages(Request $request)
    {
        $query = IncomingMessage::with('device')
            ->orderBy('timestamp', 'desc');

        if ($request->device_id) {
            $deviceIds = explode(',', $request->device_id);
            $query->whereIn('device_id', $deviceIds);
        }

        if ($request->since) {
            $query->where('timestamp', '>=', $request->since);
        }

        $messages = $query->limit(50)->get();

        return response()->json([
            'messages' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'device' => substr($msg->device->body, -4),
                    'sender' => $msg->formatted_sender,
                    'content' => $msg->message_content,
                    'type' => $msg->message_type,
                    'time' => $msg->timestamp->format('H:i:s'),
                    'timestamp' => $msg->timestamp->toISOString()
                ];
            })
        ]);
    }

    public function markAsRead(Request $request)
    {
        IncomingMessage::whereIn('id', $request->ids)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
