import { Head, Link, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    Calendar,
    Tag,
    Coffee,
    Car,
    Gamepad2,
    ShoppingBag,
    Receipt,
    HeartPulse,
    GraduationCap,
    Plane
} from 'lucide-react';
import React from 'react';
import AppLayout from '@/layouts/AppLayout';
import type { TransactionCategory } from './types';

interface CreateProps {
    categories: TransactionCategory[];
}

export default function Create({ categories }: CreateProps) {
    // Initialize Inertia useForm hook
    const { data, setData, post, processing, errors } = useForm({
        categoryId: '',
        name: '',
        amount: '',
        type: 'expense', // default to expense/Pengeluaran
        note: '',
        transactionAt: new Date().toISOString().substring(0, 10), // default to today's date YYYY-MM-DD
    });

    // Helper to render category icon matching the mockup system categories
    const getCategoryIcon = (categoryName: string) => {
        const name = categoryName.toLowerCase();
        if (name.includes('makan') || name.includes('minum') || name.includes('kuliner') || name.includes('food')) {
            return <Coffee size={20} />;
        }
        if (name.includes('transport') || name.includes('mobil') || name.includes('motor') || name.includes('car')) {
            return <Car size={20} />;
        }
        if (name.includes('hobi') || name.includes('game') || name.includes('hobby')) {
            return <Gamepad2 size={20} />;
        }
        if (name.includes('belanja') || name.includes('shop') || name.includes('mall')) {
            return <ShoppingBag size={20} />;
        }
        if (name.includes('tagihan') || name.includes('listrik') || name.includes('air') || name.includes('bill')) {
            return <Receipt size={20} />;
        }
        if (name.includes('sehat') || name.includes('medis') || name.includes('health') || name.includes('kesehatan')) {
            return <HeartPulse size={20} />;
        }
        if (name.includes('didik') || name.includes('sekolah') || name.includes('kuliah') || name.includes('education') || name.includes('pendidikan')) {
            return <GraduationCap size={20} />;
        }
        if (name.includes('libur') || name.includes('wisata') || name.includes('jalan') || name.includes('plane') || name.includes('liburan')) {
            return <Plane size={20} />;
        }
        return <Tag size={20} />;
    };

    // Filter categories based on transaction type (expense vs income)
    const filteredCategories = categories.filter(
        (category) => category.type === data.type
    );

    // Limit to top 8 categories to match the mockup grid perfectly
    const displayCategories = filteredCategories.slice(0, 8);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/transactions');
    };

    return (
        <AppLayout>
            <Head title="Tambah Transaksi - Moco" />

            <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

                {/* Header Row with Back Button */}
                <div className="mb-8 flex items-center">
                    <Link
                        href="/dashboard"
                        className="mr-4 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 transition shadow-sm"
                        aria-label="Kembali ke Dashboard"
                    >
                        <ChevronLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-black text-blue-900 tracking-tight">
                        Tambah Transaksi
                    </h1>
                </div>

                {/* Form Main Container */}
                <form onSubmit={handleSubmit} className="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 space-y-6">

                    {/* Tab Selection: Pengeluaran vs Pemasukan */}
                    <div className="grid grid-cols-2 gap-1 rounded-2xl bg-gray-100 p-1.5">
                        <button
                            type="button"
                            onClick={() => {
                                setData((prev) => ({
                                    ...prev,
                                    type: 'expense',
                                    categoryId: '',
                                }));
                            }}
                            className={`flex items-center justify-center rounded-xl py-3.5 text-base font-extrabold transition-all duration-200 ${
                                data.type === 'expense'
                                    ? 'bg-rose-500 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            Pengeluaran
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setData((prev) => ({
                                    ...prev,
                                    type: 'income',
                                    categoryId: '',
                                }));
                            }}
                            className={`flex items-center justify-center rounded-xl py-3.5 text-base font-extrabold transition-all duration-200 ${
                                data.type === 'income'
                                    ? 'bg-emerald-500 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            Pemasukan
                        </button>
                    </div>
                    {errors.type && (
                        <p className="mt-1 text-xs font-bold text-red-500">{errors.type}</p>
                    )}

                    {/* Nominal Input Field */}
                    <div>
                        <label className="block text-sm font-black text-gray-800 tracking-tight mb-2">
                            Nominal
                        </label>
                        <input
                            type="number"
                            min="1"
                            step="any"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="w-full rounded-2xl border border-gray-200 py-3.5 px-4 text-sm font-bold text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                            placeholder="Masukkan jumlah transaksi"
                            required
                        />
                        {errors.amount && (
                            <p className="mt-1.5 text-xs font-bold text-red-500">{errors.amount}</p>
                        )}
                    </div>

                    {/* Transaction Name Input Field */}
                    <div>
                        <label className="block text-sm font-black text-gray-800 tracking-tight mb-2">
                            Judul / Nama Transaksi
                        </label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded-2xl border border-gray-200 py-3.5 px-4 text-sm font-bold text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                            placeholder="Contoh: Nasi Ayam Malay"
                            required
                        />
                        {errors.name && (
                            <p className="mt-1.5 text-xs font-bold text-red-500">{errors.name}</p>
                        )}
                    </div>

                    {/* Pilih Kategori Section with Grid Selector */}
                    <div>
                        <div className="mb-3 flex items-center justify-between">
                            <label className="text-sm font-black text-gray-800 tracking-tight">
                                Pilih Kategori
                            </label>
                            <button
                                type="button"
                                className="text-xs font-extrabold text-blue-600 hover:text-blue-700 transition"
                            >
                                Lihat Semua
                            </button>
                        </div>

                        <div className="grid grid-cols-4 gap-4">
                            {displayCategories.map((category) => {
                                const isSelected = String(category.id) === String(data.categoryId);
                                return (
                                    <button
                                        key={category.id}
                                        type="button"
                                        onClick={() => setData('categoryId', String(category.id))}
                                        className={`flex flex-col items-center gap-2 rounded-2xl p-3 transition duration-200 border-2 ${
                                            isSelected
                                                ? 'border-blue-500 bg-blue-50/30 scale-105 shadow-sm'
                                                : 'border-transparent hover:bg-gray-50'
                                        }`}
                                    >
                                        <div className={`flex h-12 w-12 items-center justify-center rounded-2xl transition duration-200 ${
                                            isSelected
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-blue-50/70 text-blue-600'
                                        }`}>
                                            {getCategoryIcon(category.name || '')}
                                        </div>
                                        <span className="text-2xs font-extrabold text-blue-900/90 text-center tracking-tight leading-tight">
                                            {category.name}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        {errors.categoryId && (
                            <p className="mt-1.5 text-xs font-bold text-red-500">{errors.categoryId}</p>
                        )}
                    </div>

                    {/* Pilih Tanggal Section */}
                    <div>
                        <label className="block text-sm font-black text-gray-800 tracking-tight mb-2">
                            Pilih Tanggal
                        </label>
                        <div className="relative">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <Calendar size={18} />
                            </div>
                            <input
                                type="date"
                                max={new Date().toISOString().substring(0, 10)}
                                value={data.transactionAt}
                                onChange={(e) => setData('transactionAt', e.target.value)}
                                className="w-full rounded-2xl border border-gray-200 py-3.5 pl-11 pr-4 text-sm font-bold text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                                required
                            />
                        </div>
                        {errors.transactionAt && (
                            <p className="mt-1.5 text-xs font-bold text-red-500">{errors.transactionAt}</p>
                        )}
                    </div>

                    {/* Catatan Section */}
                    <div>
                        <label className="block text-sm font-black text-gray-800 tracking-tight mb-2">
                            Catatan (Opsional)
                        </label>
                        <textarea
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            rows={3}
                            className="w-full rounded-2xl border border-gray-200 py-3.5 px-4 text-sm font-bold text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition"
                            placeholder="Tambah catatan untuk transaksi ini"
                        />
                        {errors.note && (
                            <p className="mt-1.5 text-xs font-bold text-red-500">{errors.note}</p>
                        )}
                    </div>

                    {/* Save Button */}
                    <div className="pt-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-2xl bg-blue-600 py-4 text-base font-extrabold text-white shadow-lg shadow-blue-600/10 hover:bg-blue-700 hover:scale-[1.005] active:scale-[0.995] transition duration-200 disabled:opacity-50"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </div>

                </form>

            </div>
        </AppLayout>
    );
}
