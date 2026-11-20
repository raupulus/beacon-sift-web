<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('feeds:fetch')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('beacon:prune')
    ->dailyAt('03:30');
