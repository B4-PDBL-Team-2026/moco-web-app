<?php

namespace App\Http\Resources\Budgeting;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardDataResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'serverTime' => $this['serverTime'],
            'currentBalance' => $this['currentBalance'],
            'budgetCycle' => $this['budgetCycle'],
            'safetyCeiling' => $this['safetyCeiling'],
            'safetyFlooring' => $this['safetyFlooring'],
            'todaySpent' => $this['todaySpent'],
            'todayLimit' => $this['todayLimit'],
            'tomorrowLimitPrediction' => $this['tomorrowLimitPrediction'],
            'rawTodayLimit' => $this['rawTodayLimit'],
            'unpaidFixedCosts' => $this['unpaidFixedCosts'],
        ];
    }
}
