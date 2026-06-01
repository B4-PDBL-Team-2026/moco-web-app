<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageAnalytic extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'reached_scroll_depth',
        'visited_date',
    ];
}
