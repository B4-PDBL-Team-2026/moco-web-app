<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fixed-cost:remind')
    ->timezone('Asia/Jakarta')
    ->dailyAt('08:00');
