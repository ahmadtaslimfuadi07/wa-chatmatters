<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;
class Webhooks extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function device()
    {
         return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
