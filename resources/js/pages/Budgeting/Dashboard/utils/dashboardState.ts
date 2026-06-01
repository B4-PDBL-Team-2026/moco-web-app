import { DashboardSummary } from '../types';

export interface CalculatedDashboardState {
    isOverbudget: boolean;
    rawPercentage: number;
    progressPercentage: number;
    greetingTitle: string;
    greetingSubtitle: string;
    mainCardLimitLabel: string;
    progressBarColor: string;
    safetyDays: number;
    isSeriousWarning: boolean;
}

/**
 * Calculates dynamic UI states based on backend budget metrics and user status.
 * Follows strict English conventions for names and comments.
 */
export function calculateDashboardState(
    summary: DashboardSummary, 
    userName: string,
    budgetStatus: 'stabil' | 'defisit' | 'kritis' | 'surplus'
): CalculatedDashboardState {
    const { todaySpent, todayLimit, currentBalance, safetyFlooring } = summary;

    // Check if the user has spent more than their daily limit
    const isOverbudget = todaySpent > todayLimit;
    
    // Calculate raw and visual (capped at 100%) percentages
    const rawPercentage = todayLimit > 0 ? (todaySpent / todayLimit) * 100 : 0;
    const progressPercentage = Math.min(rawPercentage, 100);

    // Initialize default states
    let greetingTitle = `Hai, ${userName}!`;
    let greetingSubtitle = "";
    let mainCardLimitLabel = "Batas harian aktual";
    let progressBarColor = "bg-green-500"; // Green is default for under-limit
    let isSeriousWarning = false;

    if (budgetStatus === 'surplus') {
        if (isOverbudget) {
            // State 3: Surplus, but Daily allowance exceeded (Overbudget mockup)
            greetingSubtitle = "Melewati batas, jatah harian aktual besok akan berkurang!";
            progressBarColor = "bg-red-500"; // Red color
        } else if (rawPercentage > 80) {
            // State 2: Surplus, approaching limit (Approaching optimal mockup)
            greetingSubtitle = "Sedang di jalur hemat! Pertahankan agar tabungan maksimal.";
            mainCardLimitLabel = "Batas optimal";
            progressBarColor = "bg-amber-500"; // Orange color
        } else {
            // State 1: Surplus, well under limit (Optimal safe mockup)
            greetingSubtitle = "Limit optimal terlewati! Jatah tabungan akan berkurang seiring transaksi bertambah.";
            progressBarColor = "bg-green-500"; // Green color
        }
    } else if (budgetStatus === 'kritis') {
        // Critical emergency modes
        if (todayLimit <= safetyFlooring) {
            // State 6: Kritis, under Extreme Saving limit (Hemat Ekstrem)
            greetingSubtitle = "Saldo sangat terbatas! Tetap di mode Hemat Ekstrem agar cukup sampai akhir bulan.";
            mainCardLimitLabel = "Batas hemat ekstrem";
            progressBarColor = "bg-green-500"; // Green color
        } else {
            // State 7: Kritis, pushed into Survival limit (Bertahan Hidup)
            greetingSubtitle = "Mode Hemat Ekstrem terlewati. Anda sekarang menggunakan jatah “Bertahan Hidup”.";
            mainCardLimitLabel = "Batas bertahan hidup";
            progressBarColor = "bg-amber-500"; // Orange color
        }
    } else if (budgetStatus === 'defisit') {
        // Deficit modes (Mockups 8, 9, 10)
        if (todayLimit <= 0) {
            isSeriousWarning = true;
            greetingSubtitle = "Anda tidak memiliki saldo untuk membayar fixed cost dan jajan harian!";
        } else {
            isSeriousWarning = false;
            greetingSubtitle = "Saldo Anda sudah minus. Setiap pengeluaran hari ini akan memperbesar total defisit Anda.";
            mainCardLimitLabel = "Batas harian maksimal";
            progressBarColor = "bg-red-500"; // Red is default for deficit mode
        }
    } else {
        // Stabil modes (Mockups 4 & 5)
        if (isOverbudget) {
            // State 5: Stabil, over limit
            greetingSubtitle = "Batas harian aktual terlewati, sebaiknya berhenti belanja hari ini!";
            progressBarColor = "bg-red-500"; // Red color
        } else {
            // State 4: Stabil, under limit
            greetingSubtitle = "Kondisi aman. Jaga pengeluaran tetap di bawah batas harian aktual.";
            progressBarColor = "bg-green-500"; // Green color
        }
    }

    // Calculate dynamic safety period (Current balance divided by daily limit)
    const safetyDays = todayLimit > 0 ? Math.max(0, Math.floor(currentBalance / todayLimit)) : 0;

    return {
        isOverbudget,
        rawPercentage: Math.round(rawPercentage),
        progressPercentage,
        greetingTitle,
        greetingSubtitle,
        mainCardLimitLabel,
        progressBarColor,
        safetyDays,
        isSeriousWarning,
    };
}
