import { Head, useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
import React, { useState } from 'react';

interface FeedbackForm {
    platform: string;
    category: string;
    rating: number | '';
    message: string;
}

export default function Create() {
    const { data, setData, post, processing, errors, reset } =
        useForm<FeedbackForm>({
            platform: '',
            category: 'Saran / Masukan Umum',
            rating: '',
            message: '',
        });

    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post('/feedback', {
            onSuccess: () => {
                reset();
                setSuccessMessage(
                    'Terima kasih! Masukan Anda telah kami terima.',
                );
                setTimeout(() => {
                    setSuccessMessage(null);
                }, 5000);
            },
        });
    };

    return (
        <>
            <Head title="Kirim Feedback Pengguna" />

            <div className="flex min-h-screen flex-col bg-gray-50 font-sans">
                <div className="flex flex-1 items-center justify-center p-4 sm:p-6 md:p-8">
                    <div className="w-full max-w-xl rounded-3xl border border-gray-100 bg-white p-6 shadow-xl transition-all duration-300 sm:p-10">
                        <div className="mb-8 text-center">
                            <div className="mb-4 flex justify-center">
                                <img
                                    src="/logo.png"
                                    alt="MOCO logo"
                                    className="size-24 object-contain"
                                />
                            </div>
                            <h1 className="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">
                                Feedback Pengguna
                            </h1>
                            <p className="mt-2 text-sm text-gray-500">
                                Bantu kami meningkatkan layanan dengan
                                memberikan masukan atau melaporkan kendala yang
                                Anda alami.
                            </p>
                        </div>

                        {successMessage && (
                            <div className="animate-fade-in mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth={2}
                                    stroke="currentColor"
                                    className="h-5 w-5 shrink-0"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{successMessage}</span>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* 1. Platform (Radio Buttons - Wajib) */}
                            <div>
                                <label className="mb-3 block text-sm font-black tracking-tight text-gray-800">
                                    Platform{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label
                                        className={`flex cursor-pointer items-center gap-3 rounded-2xl border p-4 transition-all duration-200 ${
                                            data.platform === 'Web App'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="platform"
                                            value="Web App"
                                            checked={
                                                data.platform === 'Web App'
                                            }
                                            onChange={(e) =>
                                                setData(
                                                    'platform',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span className="text-sm font-bold">
                                            Web App
                                        </span>
                                    </label>

                                    <label
                                        className={`flex cursor-pointer items-center gap-3 rounded-2xl border p-4 transition-all duration-200 ${
                                            data.platform ===
                                            'Mobile App (Android)'
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="platform"
                                            value="Mobile App (Android)"
                                            checked={
                                                data.platform ===
                                                'Mobile App (Android)'
                                            }
                                            onChange={(e) =>
                                                setData(
                                                    'platform',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span className="text-sm font-bold">
                                            Mobile App (Android)
                                        </span>
                                    </label>
                                </div>
                                {errors.platform && (
                                    <p className="mt-1.5 text-xs font-bold text-red-500">
                                        {errors.platform}
                                    </p>
                                )}
                            </div>

                            {/* 2. Kategori Masukan (Dropdown/Select - Wajib) */}
                            <div>
                                <label
                                    htmlFor="category"
                                    className="mb-2 block text-sm font-black tracking-tight text-gray-800"
                                >
                                    Kategori Masukan{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="category"
                                    value={data.category}
                                    onChange={(e) =>
                                        setData('category', e.target.value)
                                    }
                                    className="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3.5 text-sm font-bold text-gray-900 transition duration-200 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                >
                                    <option value="Laporan Masalah (Bug)">
                                        Laporan Masalah (Bug)
                                    </option>
                                    <option value="Permintaan Fitur Baru">
                                        Permintaan Fitur Baru
                                    </option>
                                    <option value="Saran / Masukan Umum">
                                        Saran / Masukan Umum
                                    </option>
                                </select>
                                {errors.category && (
                                    <p className="mt-1.5 text-xs font-bold text-red-500">
                                        {errors.category}
                                    </p>
                                )}
                            </div>

                            {/* 3. Rating Kepuasan (Skala 1-5 - Wajib) */}
                            <div>
                                <label className="mb-2 block text-sm font-black tracking-tight text-gray-800">
                                    Rating Kepuasan{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <div className="flex items-center gap-3 py-2">
                                    {[1, 2, 3, 4, 5].map((starValue) => {
                                        const isActive =
                                            typeof data.rating === 'number' &&
                                            data.rating >= starValue;
                                        return (
                                            <button
                                                key={starValue}
                                                type="button"
                                                onClick={() =>
                                                    setData('rating', starValue)
                                                }
                                                className="group focus:outline-none"
                                                aria-label={`Beri rating ${starValue} dari 5 bintang`}
                                            >
                                                <Star
                                                    size={32}
                                                    className={`transform transition-all duration-200 group-hover:scale-110 active:scale-95 ${
                                                        isActive
                                                            ? 'fill-amber-400 stroke-amber-500 text-amber-400'
                                                            : 'text-gray-300 hover:text-amber-300'
                                                    }`}
                                                />
                                            </button>
                                        );
                                    })}
                                </div>
                                {errors.rating && (
                                    <p className="mt-1.5 text-xs font-bold text-red-500">
                                        {errors.rating}
                                    </p>
                                )}
                            </div>

                            {/* 4. Pesan Feedback (Textarea - Wajib) */}
                            <div>
                                <div className="mb-2 flex items-center justify-between">
                                    <label
                                        htmlFor="message"
                                        className="block text-sm font-black tracking-tight text-gray-800"
                                    >
                                        Pesan Feedback{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                </div>
                                <div className="relative">
                                    <textarea
                                        id="message"
                                        value={data.message}
                                        onChange={(e) =>
                                            setData('message', e.target.value)
                                        }
                                        rows={5}
                                        maxLength={1000}
                                        placeholder="Tulis masukan Anda di sini (minimal 10 karakter)..."
                                        className="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3.5 pb-10 text-sm font-bold text-gray-900 placeholder-gray-400 transition duration-200 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    />
                                    <div className="absolute right-4 bottom-3.5 text-xs font-bold text-gray-400">
                                        {data.message.length}/1000 karakter
                                    </div>
                                </div>
                                {errors.message && (
                                    <p className="mt-1.5 text-xs font-bold text-red-500">
                                        {errors.message}
                                    </p>
                                )}
                            </div>

                            {/* Submit Button */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-lg shadow-primary/10 transition duration-200 hover:scale-[1.005] hover:bg-primary-medium active:scale-[0.995] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processing ? (
                                        <>
                                            <svg
                                                className="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    className="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    strokeWidth="4"
                                                />
                                                <path
                                                    className="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                />
                                            </svg>
                                            Mengirim...
                                        </>
                                    ) : (
                                        'Kirim Feedback'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
