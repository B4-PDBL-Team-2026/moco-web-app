import { LogOut } from 'lucide-react';

interface ForceLogoutDialogProps {
    open: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    userName?: string;
}

export default function ForceLogoutDialog({
    open,
    onConfirm,
    onCancel,
    userName = 'Pengguna',
}: ForceLogoutDialogProps) {
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
                {/* LogOut icon with orange circle bg */}
                <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-orange-50">
                    <LogOut
                        size={32}
                        className="text-orange-500"
                    />
                </div>

                <h3 className="mb-3 text-lg font-bold text-gray-800">
                    Paksa Logout Sesi?
                </h3>
                <p className="mb-6 text-sm leading-relaxed text-gray-500">
                    Sesi aktif untuk <span className="font-semibold text-gray-700">{userName}</span> akan dihentikan paksa. Pengguna harus masuk kembali.
                </p>

                <button
                    onClick={onConfirm}
                    className="mb-3 w-full rounded-xl bg-orange-500 py-3 text-sm font-bold text-white transition hover:bg-orange-600"
                >
                    Ya, Paksa Keluar
                </button>

                <button
                    onClick={onCancel}
                    className="w-full rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-500 transition hover:bg-gray-50"
                >
                    Batal
                </button>
            </div>
        </div>
    );
}
