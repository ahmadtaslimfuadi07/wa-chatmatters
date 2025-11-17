<?php

namespace App\Models;
use App\Autoload\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;
class Device extends Model
{
    use HasFactory, HasUid;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'sync',
        'sync_progress',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->uuid = Str::uuid()->toString();
        });
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function smstransaction()
    {
        return $this->hasMany('App\Models\Smstransaction');
    }

    public function chats()
    {
        return $this->hasMany('App\Models\Chats');
    }

    public function getReceivedCountAttribute()
    {
        return $this->chats()->where('fromMe', 'false')->count();
    }

    public function getSentCountAttribute()
    {
        return $this->chats()->where('fromMe', 'true')->count();
    }
    
}
