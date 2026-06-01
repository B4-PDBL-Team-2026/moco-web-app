import ProgressBar from '@/components/ProgressBar';

export default function OnboardingStep1({ form, next, prev }: any) {
    return (
        <>
            <ProgressBar step={1} />

            <h1 className="mb-1 text-3xl font-bold text-primary">
                Atur Saldo Awal
            </h1>
            <p className="mb-5 text-sm text-gray-500">
                Masukkan saldo awal kamu untuk mulai.
            </p>

            {/* Info box */}
            <div className="mb-5 flex items-start gap-3 rounded-xl bg-primary-light px-4 py-3">
                <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-white">
                    i
                </span>
                <p className="text-sm leading-relaxed text-primary">
                    Secara default, saldo kamu akan dibagi rata untuk satu bulan
                    ke depan. Pembagian dimulai berdasarkan hari ini.
                </p>
            </div>

            {/* Input */}
            <div className="relative mb-1">
                <input
                    type="number"
                    placeholder="Rp 0"
                    className={`w-full rounded-xl border bg-white px-4 py-3 text-center text-base text-gray-700 placeholder-gray-300 ring-0 transition outline-none ${
                        form.errors.initialBalance
                            ? 'border-red-400 focus:border-red-400 focus:ring-2 focus:ring-red-200'
                            : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20'
                    }`}
                    value={form.data.initialBalance}
                    onChange={(e) => {
                        form.setData('initialBalance', e.target.value);
                        form.clearErrors('initialBalance');
                    }}
                />
            </div>
            {form.errors.initialBalance && (
                <p className="mb-4 text-xs text-red-500">
                    {form.errors.initialBalance}
                </p>
            )}
            {!form.errors.initialBalance && <div className="mb-6" />}

            {/* Actions */}
            <div className="flex justify-between gap-3">
                <button
                    onClick={prev}
                    className="flex-1 rounded-xl bg-primary-light py-3 text-sm font-semibold text-primary transition hover:bg-primary/20"
                >
                    Kembali
                </button>
                <button
                    onClick={next}
                    className="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90"
                >
                    Selanjutnya
                </button>
            </div>
        </>
    );
}
