<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','phone','device_id','name','lid','device_phone','device_lid'];



    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function schedulecontacts()
    {
        return $this->hasMany('App\Models\Schedulecontact');
    }


    public function groupcontacts()
    {
        return $this->hasMany(Groupcontact::class);
    }

    public function groupcontact()
    {
        return $this->belongsToMany(Group::class,'groupcontacts');
    }

    public function device()
    {
        return $this->belongsTo('App\Models\Device');
    }
    
    public function chats()
    {
        return $this->hasMany('App\Models\Chats')->orderBy('timestamp');
    }

    public function lastmessages() 
    {
        return $this->hasMany('App\Models\Chats')->orderBy('timestamp', 'desc')->first();
    }
}
