import { X } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface User {
    id: number;
    name: string;
    email: string;
    joinedAt: string;
    status: 'active' | 'banned';
    isLoggedIn: boolean;
    avatarInitials: string;
    banDuration?: string;
    emailVerified?: boolean;
}

interface EditUserDrawerProps {
    open: boolean;
    user: User | null;
    onClose: () => void;
    onSave: (
        id: number,
        name: string,
        status: 'active' | 'banned',
        banDuration?: string,
    ) => void;
}

const TIME_UNITS = [
    { value: 'menit', label: 'menit' },
    { value: 'jam', label: 'jam' },
    { value: 'hari', label: 'hari' },
    { value: 'minggu', label: 'minggu' },
    { value: 'bulan', label: 'bulan' },
];

export default function EditUserDrawer({
    open,
    user,
    onClose,
    onSave,
}: EditUserDrawerProps) {
    const [name, setName] = useState('');
    const [status, setStatus] = useState<'active' | 'banned'>('active');
    const [banValue, setBanValue] = useState<number>(1);
    const [banUnit, setBanUnit] = useState<string>('hari');
    const [errors, setErrors] = useState<{ name?: string; banValue?: string }>(
        {},
    );

    // Sync state when user changes or drawer opens
    useEffect(() => {
        if (user) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setName(user.name);
            setStatus(user.status);
            setErrors({});

            if (user.status === 'banned' && user.banDuration) {
                // Parse existing banDuration, e.g., "5 hari"
                const parts = user.banDuration.split(' ');
                const val = parseInt(parts[0], 10);
                const unit = parts[1];
                if (!isNaN(val)) setBanValue(val);
                if (unit) setBanUnit(unit);
            } else {
                setBanValue(1);
                setBanUnit('hari');
            }
        }
    }, [user, open]);

    if (!open || !user) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const newErrors: { name?: string; banValue?: string } = {};

        if (!name.trim()) {
            newErrors.name = 'Nama tidak boleh kosong';
        }

        if (status === 'banned') {
            if (!banValue || banValue <= 0) {
                newErrors.banValue = 'Durasi ban harus lebih dari 0';
            }
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        const durationStr =
            status === 'banned' ? `${banValue} ${banUnit}` : undefined;
        onSave(user.id, name.trim(), status, durationStr);
        onClose();
    };

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm transition-opacity duration-300"
                onClick={onClose}
            />

            {/* Sliding Drawer Container */}
            <div className="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-light font-bold text-primary">
                            {user.avatarInitials}
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-gray-800">
                                Edit Profil Pengguna
                            </h2>
                            <p className="text-xs text-gray-400">
                                Ubah detail informasi akun
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    >
                        <X size={18} />
                    </button>
                </div>

                {/* Form Body */}
                <form
                    onSubmit={handleSubmit}
                    className="flex flex-1 flex-col justify-between overflow-y-auto"
                >
                    <div className="space-y-6 px-6 py-6">
                        {/* Email (Read-only) */}
                        <div>
                            <label className="mb-1.5 block text-[11px] font-bold tracking-widest text-gray-400 uppercase">
                                Alamat Email
                            </label>
                            <input
                                type="email"
                                value={user.email}
                                disabled
                                className="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm text-gray-400 outline-none"
                            />
                            <p className="mt-1.5 text-xs text-gray-400">
                                Email tidak dapat diubah demi keamanan akun.
                            </p>
                        </div>

                        {/* Name (Editable) */}
                        <div>
                            <label
                                className={`mb-1.5 block text-[11px] font-bold tracking-widest uppercase ${errors.name ? 'text-red-500' : 'text-primary'}`}
                            >
                                Nama Lengkap
                            </label>
                            <input
                                type="text"
                                value={name}
                                onChange={(e) => {
                                    setName(e.target.value);
                                    if (e.target.value.trim()) {
                                        setErrors((prev) => ({
                                            ...prev,
                                            name: undefined,
                                        }));
                                    }
                                }}
                                placeholder="Masukkan nama lengkap"
                                className={`w-full rounded-xl border px-4 py-3.5 text-sm text-gray-800 transition outline-none ${
                                    errors.name
                                        ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-200'
                                        : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20'
                                }`}
                            />
                            {errors.name && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        {/* Status (Editable Active vs Ban Akun) */}
                        <div>
                            <label className="mb-1.5 block text-[11px] font-bold tracking-widest text-primary uppercase">
                                Status Akun
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onClick={() => setStatus('active')}
                                    className={`flex items-center justify-center gap-2 rounded-xl border py-3.5 text-sm font-semibold transition ${
                                        status === 'active'
                                            ? 'border-green-500 bg-green-50 text-green-600'
                                            : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                                    }`}
                                >
                                    <span
                                        className={`h-2.5 w-2.5 rounded-full ${status === 'active' ? 'bg-green-500' : 'bg-gray-300'}`}
                                    />
                                    Aktif
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setStatus('banned')}
                                    className={`flex items-center justify-center gap-2 rounded-xl border py-3.5 text-sm font-semibold transition ${
                                        status === 'banned'
                                            ? 'border-red-500 bg-red-50 text-red-600'
                                            : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                                    }`}
                                >
                                    <span
                                        className={`h-2.5 w-2.5 rounded-full ${status === 'banned' ? 'bg-red-500' : 'bg-gray-300'}`}
                                    />
                                    Ban Akun
                                </button>
                            </div>
                        </div>

                        {/* Ban Duration Inputs - visible only when status is 'banned' */}
                        {status === 'banned' && (
                            <div className="animate-fadeIn space-y-4 rounded-2xl border border-red-100 bg-red-50/30 p-5">
                                <h3 className="text-xs font-bold tracking-wider text-red-600 uppercase">
                                    Konfigurasi Waktu Ban
                                </h3>

                                <div className="grid grid-cols-3 gap-3">
                                    {/* Numeric Input */}
                                    <div className="col-span-2">
                                        <label className="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                                            Durasi
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            value={banValue}
                                            onChange={(e) => {
                                                const val = parseInt(
                                                    e.target.value,
                                                    10,
                                                );
                                                setBanValue(
                                                    isNaN(val) ? 1 : val,
                                                );
                                                if (val > 0) {
                                                    setErrors((prev) => ({
                                                        ...prev,
                                                        banValue: undefined,
                                                    }));
                                                }
                                            }}
                                            className={`w-full rounded-xl border bg-white px-3 py-2.5 text-sm text-gray-800 transition outline-none ${
                                                errors.banValue
                                                    ? 'border-red-400 focus:border-red-500'
                                                    : 'border-gray-200 focus:border-red-400'
                                            }`}
                                        />
                                    </div>

                                    {/* Dropdown Select */}
                                    <div>
                                        <label className="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                                            Satuan
                                        </label>
                                        <select
                                            value={banUnit}
                                            onChange={(e) =>
                                                setBanUnit(e.target.value)
                                            }
                                            className="w-full rounded-xl border border-gray-200 bg-white px-2 py-2.5 text-sm text-gray-800 transition outline-none focus:border-red-400"
                                        >
                                            {TIME_UNITS.map((unit) => (
                                                <option
                                                    key={unit.value}
                                                    value={unit.value}
                                                >
                                                    {unit.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                {errors.banValue && (
                                    <p className="text-xs text-red-500">
                                        {errors.banValue}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Footer Actions */}
                    <div className="flex gap-3 border-t border-gray-100 bg-gray-50 px-6 py-5">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-500 transition hover:bg-gray-100"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="flex-1 rounded-xl bg-secondary py-3 text-sm font-bold text-white shadow-sm transition hover:bg-secondary/90"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
