<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingMessage extends Model
{
    protected $fillable = [
        'device_id',
        'sender',
        'message_content',
        'message_type',
        'timestamp',
        'is_read',
        'raw_data'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime',
        'is_read' => 'boolean',
        'raw_data' => 'array'
    ];
    
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
    
    // Format nomor tanpa +62
    public function getFormattedSenderAttribute()
    {
        return str_replace(['+62', '@c.us'], ['0', ''], $this->sender);
    }
    
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    
    public function scopeRecent($query)
    {
        return $query->where('timestamp', '>=', now()->subDays(7));
    }
}
