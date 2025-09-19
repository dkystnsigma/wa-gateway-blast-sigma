<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\IncomingMessage;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListenMessages extends Command
{
    protected $signature = 'listen:messages {--device=all}';
    protected $description = 'Listen incoming WhatsApp messages - Simple monitoring like WhatsApp Web';
    protected $wa;

    public function __construct(WhatsappService $wa)
    {
        parent::__construct();
        $this->wa = $wa;
    }

    public function handle()
    {
        $this->info("🎧 Starting WhatsApp Message Monitor...");
        $this->info("📱 Monitoring all connected devices for incoming messages");
        $this->info("💬 Real-time message display - like WhatsApp Web");
        $this->line("");
        
        while (true) {
            try {
                $devices = Device::where('status', 'Connected')->get();
                
                if ($devices->isEmpty()) {
                    $this->warn("No connected devices found. Waiting...");
                    sleep(30);
                    continue;
                }
                
                foreach ($devices as $device) {
                    $this->checkMessages($device);
                }
                
                sleep(5); // Check setiap 5 detik
                
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                sleep(30);
            }
        }
    }
    
    private function checkMessages($device)
    {
        try {
            $url = env('WA_URL_SERVER') . '/get-messages';
            $response = Http::timeout(20)
                ->withOptions(['verify' => false])
                ->post($url, [
                    'token' => $device->body,
                    'limit' => 10
                ]);
            
            if (!$response->successful()) {
                return;
            }
            
            $data = $response->json();
            
            if (!isset($data['messages']) || empty($data['messages'])) {
                return;
            }
            
            foreach ($data['messages'] as $messageData) {
                $this->processMessage($device, $messageData);
            }
            
        } catch (\Exception $e) {
            // Silent fail untuk tidak spam console
        }
    }
    
    private function processMessage($device, $messageData)
    {
        // Skip pesan keluar dari device kita sendiri
        if (($messageData['fromMe'] ?? false) === true) {
            return;
        }
        
        // Check apakah pesan sudah ada
        $existing = IncomingMessage::where('device_id', $device->id)
            ->where('sender', $messageData['from'] ?? '')
            ->where('message_content', $messageData['body'] ?? '')
            ->where('timestamp', '>=', now()->subMinutes(5))
            ->first();
            
        if ($existing) {
            return;
        }
        
        // Simpan pesan baru
        $message = IncomingMessage::create([
            'device_id' => $device->id,
            'sender' => $messageData['from'] ?? '',
            'message_content' => $messageData['body'] ?? $messageData['caption'] ?? '[Media]',
            'message_type' => $messageData['type'] ?? 'text',
            'timestamp' => isset($messageData['timestamp']) ? 
                \Carbon\Carbon::createFromTimestamp($messageData['timestamp']) : now(),
            'raw_data' => $messageData
        ]);
        
        // Display real-time di console
        $this->displayMessage($device, $message);
    }
    
    private function displayMessage($device, $message)
    {
        $time = $message->timestamp->format('H:i:s');
        $sender = $message->formatted_sender;
        $content = \Illuminate\Support\Str::limit($message->message_content, 50);
        $deviceNum = substr($device->body, -4);
        
        $this->line(""); 
        $this->info("📩 [{$time}] Device: ***{$deviceNum}");
        $this->comment("   From: {$sender}");
        $this->line("   💬 {$content}");
        $this->line("   " . str_repeat("-", 50));
        
        // Log juga ke file
        Log::info("New message", [
            'device' => $device->body,
            'sender' => $sender,
            'message' => $content,
            'time' => $time
        ]);
    }
}
