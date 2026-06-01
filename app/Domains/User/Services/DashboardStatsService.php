<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use Carbon\Carbon;
use Carbon\Constants\UnitValue;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function getDashboardData(): array
    {
        return [
            'landingPageStats' => $this->getLandingPageStats(),
            'userStats' => [
                'daily' => $this->calculateStatsForPeriod('daily'),
                'weekly' => $this->calculateStatsForPeriod('weekly'),
                'monthly' => $this->calculateStatsForPeriod('monthly'),
            ],
            'chartData' => [
                'daily' => $this->getDailyChartData(),
                'weekly' => $this->getWeeklyChartData(),
                'monthly' => $this->getMonthlyChartData(),
            ],
        ];
    }

    private function calculateStatsForPeriod(string $period): array
    {
        $now = Carbon::now();

        match ($period) {
            'daily' => [
                $currentStart = $now->copy()->startOfDay(),
                $currentEnd = $now->copy()->endOfDay(),
                $previousStart = $now->copy()->subDay()->startOfDay(),
                $previousEnd = $now->copy()->subDay()->endOfDay(),
                $trendLabel = 'yesterday',
            ],
            'weekly' => [
                $currentStart = $now->copy()->startOfWeek(UnitValue::MONDAY),
                $currentEnd = $now->copy()->endOfWeek(UnitValue::SUNDAY),
                $previousStart = $now->copy()->subWeek()->startOfWeek(UnitValue::MONDAY),
                $previousEnd = $now->copy()->subWeek()->endOfWeek(UnitValue::SUNDAY),
                $trendLabel = 'last week',
            ],
            'monthly' => [
                $currentStart = $now->copy()->startOfMonth(),
                $currentEnd = $now->copy()->endOfMonth(),
                $previousStart = $now->copy()->subMonth()->startOfMonth(),
                $previousEnd = $now->copy()->subMonth()->endOfMonth(),
                $trendLabel = 'last month',
            ]
        };

        // EXCLUDE ADMIN: registered users
        $currentRegistered = User::query()
            ->where('role', '!=', 'admin')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();

        $previousRegistered = User::query()
            ->where('role', '!=', 'admin')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $registeredChange = $this->calculatePercentageChange($currentRegistered, $previousRegistered);

        // active users
        $currentActive = $this->countActiveUsers($currentStart, $currentEnd);
        $previousActive = $this->countActiveUsers($previousStart, $previousEnd);
        $activeChange = $this->calculatePercentageChange($currentActive, $previousActive);

        return [
            'activeUsers' => [
                'value' => number_format($currentActive, 0, ',', '.'),
                'change' => [
                    'value' => "{$activeChange['percentage']}% $trendLabel",
                    'trend' => $activeChange['trend'],
                ],
            ],
            'registeredUsers' => [
                'value' => number_format($currentRegistered, 0, ',', '.'),
                'change' => [
                    'value' => "{$registeredChange['percentage']}% $trendLabel",
                    'trend' => $registeredChange['trend'],
                ],
            ],
        ];
    }

    private function getDailyChartData(): array
    {
        $data = [];
        $startOfWeek = Carbon::now()->startOfWeek(UnitValue::MONDAY);
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        foreach ($days as $index => $dayName) {
            $dayStart = $startOfWeek->copy()->addDays($index)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            $data[] = [
                'label' => $dayName,
                'activeUsers' => $this->countActiveUsers($dayStart, $dayEnd),
                'registeredUsers' => User::query()
                    ->where('role', '!=', 'admin')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count(),
            ];
        }

        return $data;
    }

    private function getWeeklyChartData(): array
    {
        $data = [];
        $startOfMonth = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 4; $i++) {
            $weekStart = $startOfMonth->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(UnitValue::SUNDAY);

            if ($weekEnd->gt($startOfMonth->copy()->endOfMonth())) {
                $weekEnd = $startOfMonth->copy()->endOfMonth();
            }

            $data[] = [
                'label' => 'Week '.($i + 1),
                'activeUsers' => $this->countActiveUsers($weekStart, $weekEnd),
                'registeredUsers' => User::query()
                    ->where('role', '!=', 'admin')
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->count(),
            ];
        }

        return $data;
    }

    private function getMonthlyChartData(): array
    {
        $data = [];
        $startOfYear = Carbon::now()->startOfYear();
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Des'];

        foreach ($months as $index => $monthName) {
            $monthStart = $startOfYear->copy()->addMonths($index)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $data[] = [
                'label' => $monthName,
                'activeUsers' => $this->countActiveUsers($monthStart, $monthEnd),
                'registeredUsers' => User::query()
                    ->where('role', '!=', 'admin')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count(),
            ];
        }

        return $data;
    }

    private function getLandingPageStats(): array
    {
        $totalVisitors = DB::table('landing_page_analytics')->count();
        $uniqueVisitors = DB::table('landing_page_analytics')->distinct('ip_address')->count('ip_address');
        $scrollDepthReached = DB::table('landing_page_analytics')->where('reached_scroll_depth', true)->count();

        return [
            'totalVisitors' => number_format($totalVisitors, 0, ',', '.'),
            'uniqueVisitors' => number_format($uniqueVisitors, 0, ',', '.'),
            'scrollDepthReached' => number_format($scrollDepthReached, 0, ',', '.'),
        ];
    }

    private function countActiveUsers(Carbon $start, Carbon $end): int
    {
        $startUnix = $start->timestamp;
        $endUnix = $end->timestamp;

        // Query 1: Web Users (sessions)
        // Kita join ke table users biar bisa nge-filter role admin
        $sessionsQuery = DB::table('sessions')
            ->select('sessions.user_id')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->where('users.role', '!=', 'admin')
            ->whereNotNull('sessions.user_id')
            ->whereBetween('sessions.last_activity', [$startUnix, $endUnix]);

        // Query 2: Mobile Users (Sanctum)
        // Sama, join ke table users juga
        $tokensQuery = DB::table('personal_access_tokens')
            ->select('personal_access_tokens.tokenable_id as user_id')
            ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
            ->where('personal_access_tokens.tokenable_type', User::class)
            ->where('users.role', '!=', 'admin') // EXCLUDE ADMIN
            ->whereBetween('personal_access_tokens.last_used_at', [$start, $end]);

        // Union operation
        return DB::table($sessionsQuery->union($tokensQuery), 'combined_active_users')
            ->distinct()
            ->count('user_id');
    }

    private function calculatePercentageChange(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [
                'percentage' => $current > 0 ? '+100' : '0',
                'trend' => $current > 0 ? 'up' : 'down',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $trend = $change >= 0 ? 'up' : 'down';

        $sign = $change > 0 ? '+' : '';
        $formatted = $sign.number_format($change, 1, ',', '.');

        return [
            'percentage' => $formatted,
            'trend' => $trend,
        ];
    }
}
