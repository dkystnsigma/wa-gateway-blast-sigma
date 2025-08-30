<?php

namespace App\Console\Commands;

use App\Models\Blast;
use App\Models\Campaign;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartBlast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:blast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $wa;
    public function __construct(WhatsappService $wa)
    {
        parent::__construct();
        $this->wa = $wa;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        Log::info("=== Starting Campaign Blast Process ===");
        Campaign::where('schedule', '<=', now())
            ->whereIn('status', ['waiting', 'processing'])
            ->with('phonebook', 'device')
            ->chunk(20, function ($waitingCampaigns) {
                Log::info("Found " . $waitingCampaigns->count() . " campaigns to process");
                $this->info("Processing " . $waitingCampaigns->count() . " campaigns");
                foreach ($waitingCampaigns as $campaign) {
                    Log::info("Processing campaign ID: {$campaign->id}, Name: {$campaign->name}, Type: {$campaign->type}");

                    try {
                        Log::info("Checking device status for campaign {$campaign->id} - Device: {$campaign->device->body}, Status: {$campaign->device->status}");
                        if ($campaign->device->status != 'Connected') {
                            Log::warning("Campaign {$campaign->id} paused: Device not connected");
                            $campaign->update(['status' => 'paused']);
                            continue;
                        }

                        $campaign->update(['status' => 'processing']);
                        Log::info("Campaign {$campaign->id} status updated to processing");
                        $pendingBlasts = $this->getPendingBlasts($campaign);
                        Log::info("Found " . $pendingBlasts->count() . " pending blasts for campaign {$campaign->id}");

                        if ($pendingBlasts->isEmpty()) {
                            $campaign->update(['status' => 'completed']);
                            Log::info("Campaign {$campaign->name} completed - No pending blasts remaining");
                            continue;
                        }
                        $blastdata = $pendingBlasts->map(function ($blast) {
                            Log::info("Preparing blast ID: {$blast->id}, Receiver: {$blast->receiver}");
                            return [
                                'receiver' => $blast->receiver,
                                'message' => $blast->message,
                                'id' => $blast->id,
                            ];
                        })->toArray();


                        $data = [
                            'data' => $blastdata,
                            'type' => $campaign->type,
                            'delay' => $campaign->delay,
                            'campaign_id' => $campaign->id,
                            'sender' => $campaign->device->body,
                        ];


                        try {
                            $res = $this->wa->startBlast($data);
                            
                            if (isset($res->status) && $res->status === false && $res->message === 'Unauthorized') {
                                $campaign->update(['status' => 'failed']);
                                Log::error("Campaign {$campaign->id} failed: Unauthorized");
                                continue;
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to start blast for campaign {$campaign->id}: " . $e->getMessage());
                            for ($i = 0; $i < 3; $i++) {
                                try {
                                    $res = $this->wa->startBlast($data);
                                    if (isset($res->status) && $res->status === false && $res->message === 'Unauthorized') {
                                        $campaign->update(['status' => 'failed']);
                                        Log::error("Campaign {$campaign->id} failed: Unauthorized");
                                        break;
                                    }
                                    break;
                                } catch (\Exception $e) {
                                    Log::error("Retry $i failed for campaign {$campaign->id}: " . $e->getMessage());
                                    sleep(5);
                                }
                            }
                        }
                    } catch (\Exception $e) {

                        Log::error("Failed to update campaign status or fetch pending blasts: " . $e->getMessage());
                        continue;
                    }
                }
            });
    }

    public function getPendingBlasts($campaign)
    {
        return $campaign
            ->blasts()
            ->where('status', 'pending')
            ->limit(15)
            ->get();
    }
}
