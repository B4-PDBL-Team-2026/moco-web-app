import ProgressBar from '@/components/ProgressBar';

function InfoIcon() {
    return (
        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-400 text-[11px] text-gray-400">
            i
        </span>
    );
}

export default function OnboardingStep2({ form, next, prev }: any) {
    return (
        <>
            <ProgressBar step={2} />

            <h1 className="mb-1 text-3xl font-bold text-primary">
                Maksimal Alokasi dan Minimal Alokasi
            </h1>
            <p className="mb-6 text-sm text-gray-500">
                Tetapkan rentang aman pengeluaran harian Anda.
            </p>

            {/* Ceiling / Maksimal */}
            <div className="mb-4">
                <div className="mb-1.5 flex items-center justify-between">
                    <label className="text-sm font-semibold text-gray-700">
                        Maksimal Alokasi Budget Harian
                    </label>
                    <InfoIcon />
                </div>
                <input
                    type="number"
                    placeholder="Rp. 0"
                    className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-center text-base text-gray-700 placeholder-gray-300 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                    value={form.data.ceilingLimit}
                    onChange={(e) =>
                        form.setData('ceilingLimit', e.target.value)
                    }
                />
            </div>

            {/* Flooring / Minimal */}
            <div className="mb-8">
                <div className="mb-1.5 flex items-center justify-between">
                    <label className="text-sm font-semibold text-gray-700">
                        Minimal Alokasi Budget Harian
                    </label>
                    <InfoIcon />
                </div>
                <input
                    type="number"
                    placeholder="Rp. 0"
                    className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-center text-base text-gray-700 placeholder-gray-300 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                    value={form.data.flooringLimit}
                    onChange={(e) =>
                        form.setData('flooringLimit', e.target.value)
                    }
                />
            </div>

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
