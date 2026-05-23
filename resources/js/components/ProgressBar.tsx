type Props = {
    step: number;
    total?: number;
};

export default function ProgressBar({ step, total = 4 }: Props) {
    const percentage = (step / total) * 100;

    return (
        <div className="mb-10">
            <div className="h-2 w-full rounded-full bg-gray-200">
                <div
                    className="h-2 rounded-full bg-secondary transition-all"
                    style={{ width: `${percentage}%` }}
                />
            </div>

            <div className="mt-2 flex justify-between text-sm">
                <span className="text-secondary">Langkah {step}</span>
                <span className="text-gray-300">Langkah {total}</span>
            </div>
        </div>
    );
}
