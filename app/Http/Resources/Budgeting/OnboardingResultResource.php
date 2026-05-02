<?php

namespace App\Http\Resources\Budgeting;

use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'userId' => $this->userId,
            'cycleType' => $this->cycleType,
            'currentBalance' => $this->currentBalance,
            'reservedCost' => $this->reservedCost,
            'dailyAllowance' => $this->dailyAllowance,
            'cycleKey' => $this->cycleKey,
            'cycleStartDate' => $this->cycleStartDate,
            'cycleEndDate' => $this->cycleEndDate,
            'remainingDays' => $this->remainingDays,
            'fixedCostsCount' => $this->fixedCostsCount,
            'hasOnboarded' => $this->hasOnboarded,
        ];
    }
}
