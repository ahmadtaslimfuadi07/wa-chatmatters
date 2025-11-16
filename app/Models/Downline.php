<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Downline extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function getDownlineUsersId($userId)
    {
        $downlineUsersId = collect();
        $this->getDownlineUsersIdRecursive($userId, $downlineUsersId);

        return $downlineUsersId->unique()->values()->all();
    }

    private function getDownlineUsersIdRecursive($userId, &$downlineUsersId)
    {
        $downlines = $this->where('user_id', $userId)->pluck('downline_user_id')->toArray();

        foreach ($downlines as $downline) {
            $downlineUsersId[] = $downline;
            $this->getDownlineUsersIdRecursive($downline, $downlineUsersId);
        }
    }
}
