<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandingPageAnalyticController extends Controller
{
    public function trackVisit(Request $request)
    {
        Log::info('new visitor detected');
        $ip = $request->ip();
        $date = now()->toDateString();

        $visit = DB::table('landing_page_analytics')
            ->where('ip_address', $ip)
            ->where('visited_date', $date)
            ->first();

        if (! $visit) {
            $id = DB::table('landing_page_analytics')->insertGetId([
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'reached_scroll_depth' => false,
                'visited_date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['id' => $id]);
        }

        return response()->json(['id' => $visit->id]);
    }

    public function trackScroll(Request $request)
    {
        Log::info('new scroll detected');

        $id = $request->input('visit_id');

        if ($id) {
            DB::table('landing_page_analytics')
                ->where('id', $id)
                ->update(['reached_scroll_depth' => true, 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
