export interface UnpaidFixedCost {
    name: string;
    amount: number;
    cycle: string;
    due_value: number;
}

export interface DashboardSummary {
    serverTime: string;
    currentBalance: number;
    budgetCycle: string;
    safetyCeiling: number;
    safetyFlooring: number;
    todaySpent: number;
    todayLimit: number;
    tomorrowLimitPrediction: number;
    rawTodayLimit: number;
    unpaidFixedCosts: UnpaidFixedCost[];
}

export interface TransactionCategory {
    id: number | null;
    name: string | null;
    icon: string | null;
    type: 'income' | 'expense' | null;
}

export interface TransactionItem {
    feedType: 'single' | 'batch';
    id: number;
    name: string;
    amount: number;
    type: 'income' | 'expense';
    note: string | null;
    transactionAt: string;
    source: string;
    category: TransactionCategory | null;
}
