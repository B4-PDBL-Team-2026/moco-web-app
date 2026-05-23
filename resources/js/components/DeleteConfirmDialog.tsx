import { TrashIcon } from '@phosphor-icons/react';

interface DeleteConfirmDialogProps {
    open: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    isLoading?: boolean;
    title?: string;
    description?: string;
}

export default function DeleteConfirmDialog({
    open,
    onConfirm,
    onCancel,
    isLoading,
    title = 'Hapus Biaya Tetap?',
    description = 'Pengeluaran tetap ini akan dihapus dari daftar dan tidak akan dihitung di siklus berikutnya.',
}: DeleteConfirmDialogProps) {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/30 backdrop-blur-sm"
                onClick={onCancel}
            />

            {/* Dialog */}
            <div className="relative z-10 w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl">
                {/* Trash icon with red circle bg */}
                <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                    <TrashIcon
                        size={32}
                        weight="regular"
                        className="text-red-400"
                    />
                </div>

                <h3 className="mb-3 text-lg font-bold text-gray-800">
                    {title}
                </h3>
                <p className="mb-6 text-sm leading-relaxed text-gray-500">
                    {description}
                </p>

                <button
                    onClick={onConfirm}
                    disabled={isLoading}
                    className="mb-3 w-full rounded-xl bg-red-500 py-3 text-sm font-bold text-white transition hover:bg-red-600 disabled:opacity-60"
                >
                    {isLoading ? 'Menghapus...' : 'Ya, Hapus'}
                </button>

                <button
                    onClick={onCancel}
                    disabled={isLoading}
                    className="w-full rounded-xl border border-primary py-3 text-sm font-semibold text-primary transition hover:bg-primary-light disabled:opacity-60"
                >
                    Batal
                </button>
            </div>
        </div>
    );
}
