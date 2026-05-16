<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fixed-cost:remind')
    ->timezone('Asia/Jakarta')
    ->dailyAt('08:00');

Schedule::command('fixed-cost:remind')
    ->timezone('Asia/Jakarta')
    ->dailyAt('14:00');

Schedule::command('fixed-cost:remind')
    ->timezone('Asia/Jakarta')
    ->dailyAt('19:00');

Schedule::command('fixed-cost:mark-overdue')
    ->timezone('Asia/Jakarta')
    ->daily();
