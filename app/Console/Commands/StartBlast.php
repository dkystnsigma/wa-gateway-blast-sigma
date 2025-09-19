<?php

namespace App\Console\Commands;

use App\Models\Blast;
use App\Models\Campaign;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StartBlast extends Command
{
    protected $signature = 'start:blast';
    protected $description = 'Command description';
    protected $wa;

    public function __construct(WhatsappService $wa)
    {
        parent::__construct();
        $this->wa = $wa;
    }

    public function handle()
    {
        $startTime = now();
        Log::info("=== Starting Campaign Blast Process at " . $startTime->format('Y-m-d H:i:s') . " ===");
        logBlastInfo("=== BLAST PROCESS STARTED ===", [
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'process_id' => getmypid()
        ]);
        
        // Cleanup stuck campaigns (reset yang lebih dari 2 jam)
        $stuckCampaigns = Campaign::where('status', 'processing')
            ->where('updated_at', '<', now()->subHours(2))
            ->get();
        
        if ($stuckCampaigns->count() > 0) {
            Log::warning("Found {$stuckCampaigns->count()} stuck campaigns, resetting to waiting status");
            logBlastWarning("Found stuck campaigns", [
                'count' => $stuckCampaigns->count(),
                'campaigns' => $stuckCampaigns->pluck('id', 'name')->toArray()
            ]);
            
            foreach ($stuckCampaigns as $stuck) {
                Log::warning("Resetting stuck campaign ID: {$stuck->id}, Device: {$stuck->device->body}");
                logBlastWarning("Resetting stuck campaign", [
                    'campaign_id' => $stuck->id,
                    'campaign_name' => $stuck->name,
                    'device' => $stuck->device->body,
                    'last_update' => $stuck->updated_at->format('Y-m-d H:i:s')
                ]);
                // Release lock untuk device yang stuck
                Cache::forget('blast_lock_' . $stuck->device->id);
            }
            Campaign::where('status', 'processing')
                ->where('updated_at', '<', now()->subHours(2))
                ->update(['status' => 'waiting']);
        }
        
        // Get campaigns yang sudah waktunya dijalankan
        $waitingCampaigns = Campaign::where('schedule', '<=', now())
            ->whereIn('status', ['waiting'])
            ->with('phonebook', 'device')
            ->orderBy('schedule', 'asc') // Prioritas berdasarkan jadwal
            ->orderBy('created_at', 'asc') // Kemudian berdasarkan waktu dibuat
            ->get();
            
        Log::info("Found " . $waitingCampaigns->count() . " campaigns scheduled to run");
        logBlastInfo("Campaigns found for processing", [
            'total_campaigns' => $waitingCampaigns->count(),
            'campaigns' => $waitingCampaigns->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'device' => $c->device->body,
                    'schedule' => $c->schedule->format('Y-m-d H:i:s'),
                    'pending_blasts' => $c->blasts()->where('status', 'pending')->count()
                ];
            })->toArray()
        ]);
        $this->info("Processing " . $waitingCampaigns->count() . " campaigns");
        
        // Group campaigns by device untuk memastikan tidak ada konflik
        $campaignsByDevice = $waitingCampaigns->groupBy('device_id');
        
        foreach ($campaignsByDevice as $deviceId => $deviceCampaigns) {
            $device = $deviceCampaigns->first()->device;
            Log::info("Processing device {$device->body} (ID: {$deviceId}) with " . $deviceCampaigns->count() . " campaigns");
            logBlastInfo("Processing device", [
                'device_id' => $deviceId,
                'device_number' => $device->body,
                'device_status' => $device->status,
                'campaign_count' => $deviceCampaigns->count(),
                'campaigns' => $deviceCampaigns->pluck('name', 'id')->toArray()
            ]);
            
            // Cek apakah device sedang busy
            $lockKey = 'blast_lock_' . $deviceId;
            $lockData = Cache::get($lockKey);
            
            if ($lockData) {
                $lockInfo = is_array($lockData) ? $lockData : ['campaign_id' => $lockData, 'start_time' => now()];
                
                // Ambil lock duration dari cache atau gunakan default
                $lockDuration = Cache::get($lockKey . '_duration', 3600);
                $startTime = $lockInfo['start_time'] ?? now();
                $elapsedTime = now()->diffInSeconds($startTime);
                $remainingTime = max(0, $lockDuration - $elapsedTime);
                
                Log::info("Device {$device->body} is busy with campaign {$lockInfo['campaign_id']}");
                Log::info("Lock started: " . $startTime->format('H:i:s') . ", Duration: {$lockDuration}s, Elapsed: {$elapsedTime}s, Remaining: {$remainingTime}s");
                
                logBlastWarning("Device is busy - Skipping campaigns", [
                    'device' => $device->body,
                    'busy_with_campaign' => $lockInfo['campaign_id'] ?? 'unknown',
                    'lock_start_time' => $startTime->format('Y-m-d H:i:s'),
                    'lock_duration' => $lockDuration,
                    'elapsed_time' => $elapsedTime,
                    'remaining_time' => $remainingTime,
                    'skipped_campaigns' => $deviceCampaigns->pluck('name', 'id')->toArray()
                ]);
                    
                // Skip semua campaign untuk device ini
                foreach ($deviceCampaigns as $campaign) {
                    Log::info("Skipping campaign {$campaign->id} ({$campaign->name}) - Device {$device->body} is busy");
                }
                continue;
            }
            
            // Proses campaigns untuk device ini satu per satu
            foreach ($deviceCampaigns as $campaign) {
                Log::info("=== Processing Campaign ===");
                Log::info("Campaign ID: {$campaign->id}");
                Log::info("Campaign Name: {$campaign->name}");
                Log::info("Campaign Type: {$campaign->type}");
                Log::info("Device: {$device->body} (Status: {$device->status})");
                Log::info("Scheduled: {$campaign->schedule}");
                Log::info("Current Time: " . now()->format('Y-m-d H:i:s'));

                logBlastInfo("=== CAMPAIGN PROCESSING STARTED ===", [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'campaign_type' => $campaign->type,
                    'device' => $device->body,
                    'device_status' => $device->status,
                    'scheduled_time' => $campaign->schedule->format('Y-m-d H:i:s'),
                    'current_time' => now()->format('Y-m-d H:i:s'),
                    'delay_minutes' => now()->diffInMinutes($campaign->schedule)
                ]);

                try {
                    // Cek status device
                    if ($device->status != 'Connected') {
                        Log::warning("Campaign {$campaign->id} ({$campaign->name}) paused: Device {$device->body} not connected");
                        logBlastWarning("Campaign paused - Device not connected", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'device' => $device->body,
                            'device_status' => $device->status
                        ]);
                        $campaign->update(['status' => 'paused']);
                        continue;
                    }

                    // Double check lock (mungkin ada campaign lain yang baru saja mengambil lock)
                    if (Cache::has($lockKey)) {
                        Log::info("Device {$device->body} became busy during processing, skipping campaign {$campaign->id}");
                        logBlastWarning("Device became busy during processing", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'device' => $device->body
                        ]);
                        continue;
                    }

                    $pendingBlasts = $this->getPendingBlasts($campaign);
                    Log::info("Found " . $pendingBlasts->count() . " pending blasts (limit 3) for campaign {$campaign->id}");
                    
                    $totalPendingCount = $campaign->blasts()->where('status', 'pending')->count();
                    Log::info("Total pending blasts in campaign: {$totalPendingCount}");

                    logBlastInfo("Pending blasts analysis", [
                        'campaign_id' => $campaign->id,
                        'current_batch_size' => $pendingBlasts->count(),
                        'total_pending' => $totalPendingCount,
                        'batch_blast_ids' => $pendingBlasts->pluck('id')->toArray(),
                        'batch_receivers' => $pendingBlasts->pluck('receiver')->toArray()
                    ]);

                    if ($pendingBlasts->isEmpty()) {
                        $campaign->update(['status' => 'completed']);
                        Log::info("Campaign {$campaign->name} (ID: {$campaign->id}) completed - No pending blasts remaining");
                        logBlastInfo("Campaign completed - No pending blasts", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'completion_time' => now()->format('Y-m-d H:i:s')
                        ]);
                        continue;
                    }

                    // Hitung lock duration berdasarkan total pending blasts (bukan hanya batch ini)
                    $lockDuration = min(max(($totalPendingCount * 2), 600), 7200); // Min 10 menit, Max 2 jam
                    
                    // Set lock dengan informasi lebih detail
                    $lockInfo = [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'start_time' => now(),
                        'pending_count' => $totalPendingCount,
                        'batch_size' => $pendingBlasts->count()
                    ];
                    
                    Cache::put($lockKey, $lockInfo, $lockDuration);
                    Cache::put($lockKey . '_duration', $lockDuration, $lockDuration);
                    
                    Log::info("=== LOCK SET FOR DEVICE ===");
                    Log::info("Device: {$device->body} (ID: {$deviceId})");
                    Log::info("Lock Duration: {$lockDuration} seconds (" . round($lockDuration/60, 1) . " minutes)");
                    Log::info("Total Pending Blasts: {$totalPendingCount}");
                    Log::info("Current Batch Size: " . $pendingBlasts->count());
                    Log::info("Expected End Time: " . now()->addSeconds($lockDuration)->format('Y-m-d H:i:s'));

                    logBlastInfo("=== DEVICE LOCK SET ===", [
                        'device' => $device->body,
                        'device_id' => $deviceId,
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'lock_duration_seconds' => $lockDuration,
                        'lock_duration_minutes' => round($lockDuration/60, 1),
                        'total_pending_blasts' => $totalPendingCount,
                        'current_batch_size' => $pendingBlasts->count(),
                        'lock_start_time' => now()->format('Y-m-d H:i:s'),
                        'expected_end_time' => now()->addSeconds($lockDuration)->format('Y-m-d H:i:s')
                    ]);

                    $campaign->update(['status' => 'processing']);
                    Log::info("Campaign {$campaign->id} status updated to processing");

                    $blastdata = $pendingBlasts->map(function ($blast) {
                        Log::info("Preparing blast ID: {$blast->id}, Receiver: {$blast->receiver}");
                        return [
                            'receiver' => $blast->receiver,
                            'message' => $blast->message,
                            'id' => $blast->id,
                        ];
                    })->toArray();

                    // Delay yang lebih aman
                    $minDelay = max($campaign->delay, 180); // Minimal 3 menit
                    $randomDelay = rand(180, 480); // Random 3-8 menit
                    $finalDelay = $minDelay + $randomDelay;
                    
                    Log::info("Delay Configuration:");
                    Log::info("- Campaign Delay: {$campaign->delay} seconds");
                    Log::info("- Minimum Delay: {$minDelay} seconds");
                    Log::info("- Random Delay: {$randomDelay} seconds");
                    Log::info("- Final Delay: {$finalDelay} seconds (" . round($finalDelay/60, 1) . " minutes)");

                    logBlastInfo("Delay configuration calculated", [
                        'campaign_id' => $campaign->id,
                        'original_campaign_delay' => $campaign->delay,
                        'minimum_delay' => $minDelay,
                        'random_delay' => $randomDelay,
                        'final_delay_seconds' => $finalDelay,
                        'final_delay_minutes' => round($finalDelay/60, 1)
                    ]);

                    $data = [
                        'data' => $blastdata,
                        'type' => $campaign->type,
                        'delay' => $finalDelay,
                        'campaign_id' => $campaign->id,
                        'sender' => $device->body,
                    ];

                    // Jeda sebelum mengirim untuk keamanan
                    $preBlastDelay = rand(90, 180); // 1.5 - 3 menit
                    Log::info("Pre-blast safety delay: {$preBlastDelay} seconds");
                    logBlastInfo("Pre-blast safety delay", [
                        'campaign_id' => $campaign->id,
                        'delay_seconds' => $preBlastDelay,
                        'delay_minutes' => round($preBlastDelay/60, 1)
                    ]);
                    sleep($preBlastDelay);

                    try {
                        Log::info("=== SENDING BLAST ===");
                        Log::info("Campaign: {$campaign->name} (ID: {$campaign->id})");
                        Log::info("Device: {$device->body}");
                        Log::info("Batch Size: " . count($blastdata));
                        Log::info("Blast Data: " . json_encode($data, JSON_PRETTY_PRINT));
                        
                        logBlastInfo("=== BLAST EXECUTION STARTED ===", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'device' => $device->body,
                            'batch_size' => count($blastdata),
                            'blast_type' => $campaign->type,
                            'delay_between_messages' => $finalDelay,
                            'blast_ids' => array_column($blastdata, 'id'),
                            'receivers' => array_column($blastdata, 'receiver'),
                            'execution_time' => now()->format('Y-m-d H:i:s')
                        ]);
                        
                        $res = $this->wa->startBlast($data);
                        
                        Log::info("Blast API Response: " . json_encode($res, JSON_PRETTY_PRINT));
                        logBlastInfo("Blast API response received", [
                            'campaign_id' => $campaign->id,
                            'response' => $res,
                            'response_time' => now()->format('Y-m-d H:i:s')
                        ]);

                        if (isset($res->status) && $res->status === false && $res->message === 'Unauthorized') {
                            $campaign->update(['status' => 'failed']);
                            Log::error("Campaign {$campaign->id} failed: Unauthorized - Device {$device->body} authentication issue");
                            logBlastError("Campaign failed - Unauthorized", [
                                'campaign_id' => $campaign->id,
                                'campaign_name' => $campaign->name,
                                'device' => $device->body,
                                'error' => 'Unauthorized',
                                'failure_time' => now()->format('Y-m-d H:i:s')
                            ]);
                            Cache::forget($lockKey);
                            Cache::forget($lockKey . '_duration');
                            continue;
                        }
                        
                        Log::info("✓ Blast successfully sent for campaign {$campaign->id} ({$campaign->name}) via device {$device->body}");
                        logBlastInfo("✓ BLAST SUCCESSFULLY SENT", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'device' => $device->body,
                            'batch_size' => count($blastdata),
                            'blast_ids' => array_column($blastdata, 'id'),
                            'success_time' => now()->format('Y-m-d H:i:s')
                        ]);
                        
                        // Jika masih ada pending blasts, update status kembali ke waiting untuk diproses lagi
                        $remainingPending = $campaign->blasts()->where('status', 'pending')->count();
                        if ($remainingPending > 0) {
                            Log::info("Campaign {$campaign->id} has {$remainingPending} remaining pending blasts, will be processed in next cycle");
                            logBlastInfo("Campaign has remaining pending blasts", [
                                'campaign_id' => $campaign->id,
                                'remaining_pending' => $remainingPending,
                                'status_change' => 'processing -> waiting'
                            ]);
                            $campaign->update(['status' => 'waiting']);
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Failed to start blast for campaign {$campaign->id}: " . $e->getMessage());
                        Log::error("Error details: " . $e->getTraceAsString());
                        logBlastError("Blast execution failed", [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'device' => $device->body,
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString(),
                            'failure_time' => now()->format('Y-m-d H:i:s')
                        ]);
                        
                        // Release lock jika gagal kirim
                        Cache::forget($lockKey);
                        Cache::forget($lockKey . '_duration');
                        
                        // Retry mechanism dengan delay yang lebih besar
                        Log::info("Starting retry mechanism for campaign {$campaign->id}");
                        logBlastInfo("Starting retry mechanism", [
                            'campaign_id' => $campaign->id,
                            'max_retries' => 3
                        ]);
                        
                        for ($i = 1; $i <= 3; $i++) {
                            try {
                                $retryDelay = rand(120, 300); // 2-5 menit
                                Log::info("Retry attempt {$i}/3 for campaign {$campaign->id} after {$retryDelay} seconds");
                                logBlastInfo("Retry attempt starting", [
                                    'campaign_id' => $campaign->id,
                                    'retry_number' => $i,
                                    'total_retries' => 3,
                                    'retry_delay' => $retryDelay
                                ]);
                                sleep($retryDelay);
                                
                                $res = $this->wa->startBlast($data);
                                Log::info("Retry {$i} response: " . json_encode($res, JSON_PRETTY_PRINT));
                                logBlastInfo("Retry response received", [
                                    'campaign_id' => $campaign->id,
                                    'retry_number' => $i,
                                    'response' => $res
                                ]);
                                
                                if (isset($res->status) && $res->status === false && $res->message === 'Unauthorized') {
                                    $campaign->update(['status' => 'failed']);
                                    Log::error("Campaign {$campaign->id} failed on retry {$i}: Unauthorized");
                                    logBlastError("Retry failed - Unauthorized", [
                                        'campaign_id' => $campaign->id,
                                        'retry_number' => $i,
                                        'error' => 'Unauthorized'
                                    ]);
                                    break;
                                }
                                
                                // Re-set lock jika retry berhasil
                                Cache::put($lockKey, $lockInfo, $lockDuration);
                                Cache::put($lockKey . '_duration', $lockDuration, $lockDuration);
                                Log::info("✓ Retry {$i} successful for campaign {$campaign->id}, lock restored");
                                logBlastInfo("✓ Retry successful", [
                                    'campaign_id' => $campaign->id,
                                    'retry_number' => $i,
                                    'lock_restored' => true
                                ]);
                                break;
                                
                            } catch (\Exception $retryException) {
                                Log::error("Retry {$i} failed for campaign {$campaign->id}: " . $retryException->getMessage());
                                logBlastError("Retry attempt failed", [
                                    'campaign_id' => $campaign->id,
                                    'retry_number' => $i,
                                    'error_message' => $retryException->getMessage()
                                ]);
                                if ($i < 3) {
                                    $longDelay = rand(300, 600); // 5-10 menit untuk retry terakhir
                                    Log::info("Waiting {$longDelay} seconds before next retry");
                                    sleep($longDelay);
                                } else {
                                    Log::error("All retry attempts failed for campaign {$campaign->id}");
                                    logBlastError("All retry attempts failed", [
                                        'campaign_id' => $campaign->id,
                                        'campaign_name' => $campaign->name,
                                        'final_status' => 'failed'
                                    ]);
                                    $campaign->update(['status' => 'failed']);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Critical error processing campaign {$campaign->id}: " . $e->getMessage());
                    Log::error("Error trace: " . $e->getTraceAsString());
                    logBlastError("Critical error in campaign processing", [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'device' => $device->body,
                        'error_message' => $e->getMessage(),
                        'error_trace' => $e->getTraceAsString(),
                        'error_time' => now()->format('Y-m-d H:i:s')
                    ]);
                    
                    if (isset($lockKey)) {
                        Cache::forget($lockKey);
                        Cache::forget($lockKey . '_duration');
                        Log::info("Lock released due to critical error for device {$device->body}");
                        logBlastWarning("Lock released due to critical error", [
                            'device' => $device->body,
                            'campaign_id' => $campaign->id
                        ]);
                    }
                    continue;
                }
                
                // Jeda antar campaign pada device yang sama untuk keamanan
                if ($deviceCampaigns->last() !== $campaign) {
                    $intercampaignDelay = rand(300, 600); // 5-10 menit
                    Log::info("Inter-campaign delay on device {$device->body}: {$intercampaignDelay} seconds");
                    logBlastInfo("Inter-campaign safety delay", [
                        'device' => $device->body,
                        'completed_campaign' => $campaign->id,
                        'delay_seconds' => $intercampaignDelay,
                        'delay_minutes' => round($intercampaignDelay/60, 1)
                    ]);
                    sleep($intercampaignDelay);
                }
            }
        }
        
        $endTime = now();
        $totalProcessTime = $startTime->diffInSeconds($endTime);
        
        Log::info("=== Campaign Blast Process Completed at " . $endTime->format('Y-m-d H:i:s') . " ===");
        logBlastInfo("=== BLAST PROCESS COMPLETED ===", [
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'total_process_time_seconds' => $totalProcessTime,
            'total_process_time_minutes' => round($totalProcessTime/60, 1),
            'total_campaigns_found' => $waitingCampaigns->count(),
            'devices_processed' => $campaignsByDevice->count(),
            'process_id' => getmypid()
        ]);
    }

    public function getPendingBlasts($campaign)
    {
        return $campaign
            ->blasts()
            ->where('status', 'pending')
            ->limit(3)
            ->get();
    }
}