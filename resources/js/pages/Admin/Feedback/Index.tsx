import { Head, router, useForm } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    Clock,
    Laptop,
    Mail,
    MessageSquare,
    Search,
    Smartphone,
    Star,
    Tag,
    User,
    X
} from 'lucide-react';
import React, { useState, useEffect } from 'react';
import {
    Bar,
    BarChart,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis
} from 'recharts';

import Badge from '@/components/Badge';
import type { Column } from '@/components/DataTable';
import DataTable from '@/components/DataTable';
import Pagination from '@/components/Pagination';
import StatCard from '@/components/StatCard';
import AdminLayout from '@/layouts/AdminLayout';

// Interface Feedback
interface Feedback {
    id: number;
    created_at: string;
    user: {
        name: string;
        email: string;
    };
    platform: string;
    category: string;
    rating: number;
    message: string;
    status: 'pending' | 'replied';
    admin_reply?: string;
    replied_at?: string;
}

interface Stats {
    total_masukan: number;
    avg_rating: string;
    platform_data: { name: string; value: number; color: string }[];
    category_data: { name: string; value: number }[];
}

interface IndexProps {
    feedbacks: {
        data: Feedback[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
        category?: string;
        start_date?: string;
        end_date?: string;
    };
    stats: Stats;
}

export default function Index({ feedbacks, filters, stats }: IndexProps) {
    const [selectedFeedback, setSelectedFeedback] = useState<Feedback | null>(null);
    const [isOpen, setIsOpen] = useState(false);

    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const { data, setData, post, processing, reset } = useForm({
        admin_reply: '',
    });

    useEffect(() => {
        const debounce = setTimeout(() => {
            router.get('/admin/feedback', {
                search: searchQuery,
                status: statusFilter,
                category: categoryFilter,
                start_date: startDate,
                end_date: endDate,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(debounce);
    }, [searchQuery, statusFilter, categoryFilter, startDate, endDate]);

    const handleOpenReply = (feedback: Feedback) => {
        setSelectedFeedback(feedback);
        setData('admin_reply', feedback.admin_reply || '');
        setIsOpen(true);
    };

    const handleCloseReply = () => {
        setIsOpen(false);
        setSelectedFeedback(null);
        reset();
    };

    const handleSubmitReply = (e: React.FormEvent) => {
        e.preventDefault();

        if (selectedFeedback) {
            post(`/admin/feedback/${selectedFeedback.id}/respond`, {
                onSuccess: () => {
                    handleCloseReply();
                },
            });
        }
    };

    const getCategoryBadge = (category: string) => {
        if (category.includes('Bug')) {
            return (
                <Badge color="red" icon={<Tag size={12} className="text-red-400" />}>
                    Bug
                </Badge>
            );
        }
        if (category.includes('Fitur')) {
            return (
                <Badge color="emerald" icon={<Tag size={12} className="text-emerald-400" />}>
                    Fitur Baru
                </Badge>
            );
        }
        return (
            <Badge color="blue" icon={<Tag size={12} className="text-blue-400" />}>
                Saran Umum
            </Badge>
        );
    };

    const renderStars = (rating: number) => {
        return (
            <div className="flex items-center gap-0.5">
                {[1, 2, 3, 4, 5].map((val) => (
                    <Star
                        key={val}
                        size={14}
                        className={
                            val <= rating
                                ? 'fill-amber-400 text-amber-400 stroke-amber-500'
                                : 'text-gray-200'
                        }
                    />
                ))}
            </div>
        );
    };

    // Filter logic
    const columns: Column<Feedback>[] = [
        {
            key: 'created_at',
            label: 'Tanggal',
            className: 'whitespace-nowrap text-xs text-gray-400',
        },
        {
            key: 'user',
            label: 'Pengguna',
            render: (item) => (
                <div className="flex flex-col">
                    <span className="text-gray-900 font-extrabold">{item.user.name}</span>
                    <span className="text-xs font-normal text-gray-400 mt-0.5">{item.user.email}</span>
                </div>
            ),
        },
        {
            key: 'platform',
            label: 'Platform',
            className: 'whitespace-nowrap',
            render: (item) => (
                <span className="inline-flex items-center gap-1.5 text-xs">
                    {item.platform.includes('Web') ? (
                        <Laptop size={14} className="text-gray-400" />
                    ) : (
                        <Smartphone size={14} className="text-gray-400" />
                    )}
                    {item.platform}
                </span>
            ),
        },
        {
            key: 'category',
            label: 'Kategori',
            render: (item) => getCategoryBadge(item.category),
        },
        {
            key: 'rating',
            label: 'Rating',
            className: 'whitespace-nowrap',
            render: (item) => renderStars(item.rating),
        },
        {
            key: 'status',
            label: 'Status',
            className: 'whitespace-nowrap',
            render: (item) => (
                item.status === 'replied' ? (
                    <Badge color="emerald" icon={<CheckCircle2 size={12} />}>
                        Replied
                    </Badge>
                ) : (
                    <Badge color="amber" icon={<Clock size={12} />}>
                        Pending
                    </Badge>
                )
            ),
        },
        {
            key: 'actions',
            label: 'Aksi',
            align: 'right',
            className: 'whitespace-nowrap',
            render: (item) => (
                <button
                    onClick={() => handleOpenReply(item)}
                    className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
                    Lihat & Balas
                </button>
            ),
        },
    ];

    return (
        <AdminLayout>
            <Head title="Kelola Feedback Pengguna" />

            <div className="space-y-8 p-6 lg:p-8 font-sans bg-gray-50/50 min-h-screen">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-black text-gray-900 tracking-tight">
                        Masukan & Feedback Pengguna
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Analisis data statistik kepuasan serta tanggapi feedback langsung dari pengguna.
                    </p>
                </div>

                {/* Bagian 1: Tinjauan Statistik */}
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <StatCard
                        title="Total Masukan"
                        value={stats.total_masukan.toString()}
                        description="Dari semua platform & kategori"
                        icon={<MessageSquare className="size-6" />}
                        accent="emerald"
                        />

                    <StatCard
                        title="Rata-rata Rating"
                        value={stats.avg_rating}
                        footer={
                            <div className="flex items-center gap-2">
                                {renderStars(Math.round(parseFloat(stats.avg_rating)))}
                                <span className="text-xs font-bold text-gray-400">/ 5.0</span>
                            </div>
                        }
                        icon={<Star className="size-6 fill-amber-400 text-amber-500" />}
                        accent="amber"
                    />

                    {/* Grafik Platform (PieChart) */}
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-bold text-gray-900 mb-3">Distribusi Platform</h3>
                        <div className="h-44 w-full relative">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={stats.platform_data}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={45}
                                        outerRadius={65}
                                        paddingAngle={4}
                                        dataKey="value"
                                    >
                                        {stats.platform_data.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip
                                        formatter={(value) => [`${value}%`, 'Persentase']}
                                        contentStyle={{ borderRadius: '12px', borderColor: '#F3F4F6' }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                        <div className="flex justify-center gap-3 text-[11px] font-bold text-gray-500 mt-2">
                            {stats.platform_data.map((p) => (
                                <div key={p.name} className="flex items-center gap-1.5">
                                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: p.color }} />
                                    <span>{p.name} ({p.value}%)</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Grafik Kategori (BarChart) */}
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-bold text-gray-900 mb-3">Kategori Masukan</h3>
                        <div className="h-44 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={stats.category_data}
                                    margin={{ top: 10, right: 10, left: -15, bottom: 10 }}
                                >
                                    <XAxis
                                        dataKey="name"
                                        stroke="#9CA3AF"
                                        fontSize={9}
                                        tickLine={false}
                                        axisLine={false}
                                        interval={0}
                                    />
                                    <YAxis
                                        stroke="#9CA3AF"
                                        fontSize={10}
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <Tooltip
                                        cursor={{ fill: 'transparent' }}
                                        contentStyle={{ borderRadius: '12px', borderColor: '#F3F4F6' }}
                                    />
                                    <Bar dataKey="value" fill="#10B981" radius={[6, 6, 0, 0]} barSize={28} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                        <div className="text-center text-[10px] font-bold text-gray-400 mt-2">
                            Jumlah masukan berdasarkan kategori utama
                        </div>
                    </div>
                </div>

                {/* Bagian 2: Tabel Data */}
                <div className="rounded-3xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                    <div className="border-b border-gray-100 px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 className="text-lg font-black text-gray-900 tracking-tight">Daftar Masukan Terbaru</h2>
                        <span className="text-xs font-bold text-gray-400 bg-gray-50 px-2.5 py-1 rounded-lg">
                            Menampilkan {feedbacks.data.length} dari {feedbacks.total} masukan
                        </span>
                    </div>

                    {/* Filter bar */}
                    <div className="bg-gray-50/50 border-b border-gray-100 p-6 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        {/* Search */}
                        <div className="md:col-span-4 space-y-1">
                            <label className="text-[10px] font-black uppercase tracking-wider text-gray-400">Pencarian</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 size-4" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => {
                                        setSearchQuery(e.target.value);
                                    }}
                                    placeholder="Cari nama, email, pesan..."
                                    className="w-full pl-9 pr-8 py-2 text-xs font-bold rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                                />
                                {searchQuery && (
                                    <button
                                        onClick={() => {
                                            setSearchQuery('');
                                        }}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        <X className="size-3" />
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Status Filter */}
                        <div className="md:col-span-2 space-y-1">
                            <label className="text-[10px] font-black uppercase tracking-wider text-gray-400">Status</label>
                            <select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                }}
                                className="w-full px-3 py-2 text-xs font-bold rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="replied">Replied</option>
                            </select>
                        </div>

                        {/* Kategori Filter */}
                        <div className="md:col-span-2 space-y-1">
                            <label className="text-[10px] font-black uppercase tracking-wider text-gray-400">Kategori</label>
                            <select
                                value={categoryFilter}
                                onChange={(e) => {
                                    setCategoryFilter(e.target.value);
                                }}
                                className="w-full px-3 py-2 text-xs font-bold rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                            >
                                <option value="">Semua Kategori</option>
                                <option value="Bug">Bug</option>
                                <option value="Fitur">Fitur Baru</option>
                                <option value="Saran">Saran Umum</option>
                            </select>
                        </div>

                        {/* Rentang Waktu (Merged UX) */}
                        <div className="md:col-span-4 space-y-1">
                            <label className="text-[10px] font-black uppercase tracking-wider text-gray-400">Rentang Waktu Masukan</label>
                            <div className="flex items-center gap-2 bg-white rounded-xl border border-gray-200 px-3 py-1.5 shadow-sm">
                                <Calendar className="text-gray-400 size-4 shrink-0" />
                                <div className="flex items-center gap-1.5 w-full">
                                    <input
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => {
                                            setStartDate(e.target.value);
                                        }}
                                        className="w-full p-0 text-xs font-bold border-none bg-transparent focus:ring-0 focus:outline-none"
                                        placeholder="Mulai"
                                    />
                                    <span className="text-xs font-bold text-gray-400">s/d</span>
                                    <input
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => {
                                            setEndDate(e.target.value);
                                        }}
                                        className="w-full p-0 text-xs font-bold border-none bg-transparent focus:ring-0 focus:outline-none"
                                        placeholder="Selesai"
                                    />
                                </div>
                                {(startDate || endDate) && (
                                    <button
                                        onClick={() => {
                                            setStartDate('');
                                            setEndDate('');
                                        }}
                                        className="text-gray-400 hover:text-gray-600 shrink-0 ml-1"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    <DataTable
                        columns={columns}
                        data={feedbacks.data}
                        emptyMessage="Tidak ada masukan yang sesuai dengan filter."
                    />

                    {/* Pagination UI */}
                    <Pagination
                        currentPage={feedbacks.current_page}
                        totalPages={feedbacks.last_page}
                        totalItems={feedbacks.total}
                        from={feedbacks.from || 0}
                        to={feedbacks.to || 0}
                        onPageChange={(page) => {
                            router.get(feedbacks.links[page]?.url || '/admin/feedback', {
                                search: searchQuery,
                                status: statusFilter,
                                category: categoryFilter,
                                start_date: startDate,
                                end_date: endDate,
                            }, { preserveState: true, preserveScroll: true });
                        }}
                        itemLabel="masukan"
                        showPageNumbers={true}
                    />
                </div>

                {/* Bagian 3: Panel Detail & Balasan */}
                {isOpen && selectedFeedback && (
                    <div className="fixed inset-0 z-50 flex justify-end">
                        <div
                            className="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                            onClick={handleCloseReply}
                        />

                        <div className="relative z-10 w-full max-w-lg bg-white h-screen shadow-2xl flex flex-col justify-between animate-slide-in duration-300">
                            {/* Header */}
                            <div className="flex items-center justify-between border-b border-gray-100 p-6">
                                <div className="flex flex-col">
                                    <h3 className="text-lg font-black text-gray-900 tracking-tight">Detail Feedback</h3>
                                    <p className="text-xs text-gray-400 mt-0.5">ID Masukan #{selectedFeedback.id}</p>
                                </div>
                                <button
                                    onClick={handleCloseReply}
                                    className="rounded-xl border border-gray-200 p-2 text-gray-400 hover:bg-gray-50 hover:text-gray-700 transition"
                                >
                                    <X size={18} />
                                </button>
                            </div>

                            {/* Content */}
                            <div className="flex-1 overflow-y-auto p-6 space-y-6">
                                <div className="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 space-y-3">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <User size={18} />
                                        </div>
                                        <div>
                                            <p className="text-sm font-extrabold text-gray-900">{selectedFeedback.user.name}</p>
                                            <p className="text-xs font-normal text-gray-500">{selectedFeedback.user.email}</p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100 text-xs text-gray-500">
                                        <div>
                                            <span className="block font-normal text-gray-400">Platform</span>
                                            <span className="font-bold text-gray-800">{selectedFeedback.platform}</span>
                                        </div>
                                        <div>
                                            <span className="block font-normal text-gray-400">Kategori</span>
                                            <span className="font-bold text-gray-800">{selectedFeedback.category}</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-between border-b border-gray-100 pb-4">
                                    <div>
                                        <p className="text-xs font-normal text-gray-400">Rating Pengguna</p>
                                        <div className="flex items-center gap-1.5 mt-1">
                                            {renderStars(selectedFeedback.rating)}
                                            <span className="text-xs font-black text-gray-900">{selectedFeedback.rating}.0 / 5.0</span>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-xs font-normal text-gray-400">Dikirim Pada</p>
                                        <p className="text-xs font-bold text-gray-800 mt-1">{selectedFeedback.created_at}</p>
                                    </div>
                                </div>

                                <div>
                                    <h4 className="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Isi Pesan</h4>
                                    <p className="text-sm leading-relaxed text-gray-800 bg-gray-50/50 rounded-2xl border border-gray-100 p-4 font-medium">
                                        "{selectedFeedback.message}"
                                    </p>
                                </div>

                                {selectedFeedback.status === 'replied' && selectedFeedback.admin_reply && (
                                    <div className="border-t border-gray-100 pt-6 space-y-2">
                                        <div className="flex justify-between items-center">
                                            <h4 className="text-xs font-black uppercase tracking-wider text-emerald-600">Balasan Terkirim</h4>
                                            <span className="text-[10px] font-bold text-gray-400">{selectedFeedback.replied_at}</span>
                                        </div>
                                        <p className="text-sm leading-relaxed text-emerald-800 bg-emerald-50/50 rounded-2xl border border-emerald-100 p-4 font-medium">
                                            {selectedFeedback.admin_reply}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Form Balasan */}
                            <div className="border-t border-gray-100 p-6 bg-white">
                                <form onSubmit={handleSubmitReply} className="space-y-4">
                                    <div>
                                        <label htmlFor="admin_reply" className="block text-xs font-black uppercase tracking-wider text-gray-400 mb-2">
                                            Tulis Balasan Email
                                        </label>
                                        <textarea
                                            id="admin_reply"
                                            value={data.admin_reply}
                                            onChange={(e) => setData('admin_reply', e.target.value)}
                                            rows={4}
                                            required
                                            placeholder="Tulis pesan balasan ke pengguna di sini..."
                                            className="w-full rounded-2xl border border-gray-200 bg-white py-3.5 px-4 text-sm font-bold text-gray-900 placeholder-gray-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary transition duration-200"
                                        />
                                    </div>

                                    <div className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={handleCloseReply}
                                            className="w-1/3 rounded-2xl border border-gray-200 bg-white py-3.5 px-4 text-xs font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none"
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="flex-1 flex items-center justify-center gap-2 rounded-2xl bg-primary py-3.5 px-4 text-xs font-extrabold text-white shadow-md hover:bg-primary-medium transition duration-200 disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-2 h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                    </svg>
                                                    Mengirim...
                                                </>
                                            ) : (
                                                <>
                                                    <Mail size={16} />
                                                    Kirim Balasan via Email
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
