import ProgressBar from '@/components/ProgressBar';

function formatRp(value: string | number) {
    const num = Number(value);
    if (isNaN(num)) return 'Rp 0';
    return (
        'Rp ' +
        num.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })
    );
}

function StatusBadge({ label }: { label: string }) {
    return (
        <span className="rounded-full bg-secondary px-3 py-1 text-xs font-bold text-white">
            {label}
        </span>
    );
}

function SummaryRow({
    label,
    value,
    valueClass = 'font-bold text-gray-800',
}: {
    label: string;
    value: React.ReactNode;
    valueClass?: string;
}) {
    return (
        <div className="flex items-center justify-between border-b border-gray-100 py-3 last:border-0">
            <span className="text-sm text-gray-500">{label}</span>
            <span className={`text-sm ${valueClass}`}>{value}</span>
        </div>
    );
}

const CYCLE_LABEL: Record<string, string> = {
    monthly: 'Bulanan',
    weekly: 'Mingguan',
};

export default function OnboardingStep4({ preview, prev, submit }: any) {
    if (!preview) return null;

    const cycleLabel = CYCLE_LABEL[preview.cycleType] ?? preview.cycleType;

    // Determine financial condition label
    const dailyAllowance = Number(preview.dailyAllowance);
    const conditionLabel =
        dailyAllowance > 0 ? 'Stabil' : dailyAllowance === 0 ? 'Pas' : 'Kritis';

    const reservedCost = Number(preview.reservedCost);

    return (
        <>
            <ProgressBar step={4} />

            <h1 className="mb-1 text-3xl font-bold text-primary">
                Ringkasan Akhir
            </h1>
            <p className="mb-5 text-sm text-gray-500">
                {dailyAllowance > 0
                    ? 'Kondisi stabil, jaga pebgeluaran tetap sesuai batas harian.'
                    : 'Periksa kembali pengaturan budget kamu.'}
            </p>

            {/* Summary card */}
            <div className="mb-5 rounded-xl border border-gray-200 bg-white px-5">
                {/* Financial condition row */}
                <div className="flex items-center justify-between border-b border-gray-100 py-3">
                    <span className="text-sm text-gray-500">
                        Kondisi Keuangan
                    </span>
                    <StatusBadge label={conditionLabel} />
                </div>

                <SummaryRow
                    label="Siklus"
                    value={cycleLabel}
                    valueClass="font-bold text-primary"
                />

                <SummaryRow
                    label="Saldo Sekarang"
                    value={formatRp(preview.currentBalance)}
                />

                <SummaryRow
                    label="Sisa Hari"
                    value={`${preview.remainingDays} hari`}
                />

                <SummaryRow
                    label="Total Tagihan untuk Dibayar"
                    value={formatRp(reservedCost)}
                />

                <SummaryRow
                    label="Jatah Harian Aktual"
                    value={formatRp(preview.dailyAllowance)}
                    valueClass="font-bold text-green-600"
                />
            </div>

            {/* Fixed costs validity note */}
            <div className="mb-6 text-sm text-gray-500">
                <span className="font-semibold text-gray-700">
                    Fixed Cost Valid
                </span>
                <br />
                {preview.fixedCostsCount > 0
                    ? `${preview.fixedCostsCount} tagihan aktif pada siklus ini.`
                    : 'Tidak ada fixed cost valid pada sisa siklus ini.'}
            </div>

            {/* Actions */}
            <div className="flex justify-between gap-3">
                <button
                    type="button"
                    onClick={prev}
                    className="flex-1 rounded-xl bg-primary-light py-3 text-sm font-semibold text-primary transition hover:bg-primary/20"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    onClick={submit}
                    className="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90"
                >
                    Selesai
                </button>
            </div>
        </>
    );
}
