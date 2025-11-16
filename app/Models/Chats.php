<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chats extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','phone','device_id','unic_id','message','fromMe','timestamp','contact_id','file','status','device_unic_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function device()
    {
         return $this->belongsTo('App\Models\Device');
    }

    public function contact()
    {
         return $this->belongsTo('App\Models\Contact','contact_id','id');
    }
}
