import { Head, useForm } from '@inertiajs/react';
import { 
    Star, 
    MessageSquare, 
    X, 
    Send, 
    Mail, 
    User, 
    Smartphone, 
    Laptop, 
    Tag, 
    Clock, 
    CheckCircle2,
    Search,
    Filter,
    Calendar
} from 'lucide-react';
import React, { useState } from 'react';
import { 
    ResponsiveContainer, 
    PieChart, 
    Pie, 
    Cell, 
    Tooltip, 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis,
    Legend
} from 'recharts';

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

// Data Dummy List Feedback
const DUMMY_FEEDBACK_LIST: Feedback[] = [
    {
        id: 1,
        created_at: '2026-06-03 10:30',
        user: {
            name: 'Budi Santoso',
            email: 'budi.santoso@example.com'
        },
        platform: 'Web App',
        category: 'Laporan Masalah (Bug)',
        rating: 2,
        message: 'Aplikasi sering logout sendiri saat saya membuka halaman riwayat transaksi. Mohon segera diperbaiki karena cukup mengganggu.',
        status: 'pending'
    },
    {
        id: 2,
        created_at: '2026-06-02 14:15',
        user: {
            name: 'Siti Rahma',
            email: 'siti.rahma@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Permintaan Fitur Baru',
        rating: 5,
        message: 'Sangat bagus! Bisakah ditambahkan fitur ekspor laporan keuangan mingguan ke format Excel (.xlsx)? Ini akan sangat membantu.',
        status: 'replied',
        admin_reply: 'Halo Siti, terima kasih atas sarannya! Fitur ekspor laporan ke Excel saat ini sedang dalam tahap pengembangan dan direncanakan rilis pada versi berikutnya.',
        replied_at: '2026-06-02 16:00'
    },
    {
        id: 3,
        created_at: '2026-06-01 09:45',
        user: {
            name: 'Andi Wijaya',
            email: 'andi.wijaya@example.com'
        },
        platform: 'Web App',
        category: 'Saran / Masukan Umum',
        rating: 4,
        message: 'Tampilan dashboard-nya bersih dan mudah dipahami. Warna branding primary hijau-nya juga segar di mata.',
        status: 'pending'
    },
    {
        id: 4,
        created_at: '2026-05-30 11:20',
        user: {
            name: 'Dewi Lestari',
            email: 'dewi.lestari@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Laporan Masalah (Bug)',
        rating: 1,
        message: 'Gagal melakukan verifikasi email. Kode OTP tidak pernah masuk ke inbox maupun spam saya.',
        status: 'pending'
    },
    {
        id: 5,
        created_at: '2026-05-28 15:40',
        user: {
            name: 'Rian Hidayat',
            email: 'rian.hidayat@example.com'
        },
        platform: 'Web App',
        category: 'Permintaan Fitur Baru',
        rating: 4,
        message: 'Apakah ada rencana integrasi dengan e-wallet lokal seperti GoPay atau OVO untuk pencatatan otomatis?',
        status: 'replied',
        admin_reply: 'Halo Rian, terima kasih! Integrasi e-wallet lokal masuk dalam roadmap kuartal ke-4 tahun ini. Tetap pantau update kami ya.',
        replied_at: '2026-05-29 09:15'
    },
    {
        id: 6,
        created_at: '2026-05-27 10:00',
        user: {
            name: 'Eko Prasetyo',
            email: 'eko.prasetyo@example.com'
        },
        platform: 'Web App',
        category: 'Laporan Masalah (Bug)',
        rating: 3,
        message: 'Grafik pengeluaran bulanan tidak muncul jika data transaksi di atas 50 item.',
        status: 'pending'
    },
    {
        id: 7,
        created_at: '2026-05-26 16:30',
        user: {
            name: 'Fitriani Lestari',
            email: 'fitriani.lestari@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Saran / Masukan Umum',
        rating: 5,
        message: 'User interface-nya sangat intuitif! Sangat membantu saya mengelola keuangan bulanan keluarga kecil saya.',
        status: 'replied',
        admin_reply: 'Halo Fitriani, senang mendengarnya! Terima kasih atas dukungannya.',
        replied_at: '2026-05-27 08:30'
    },
    {
        id: 8,
        created_at: '2026-05-25 09:15',
        user: {
            name: 'Hadi Wibowo',
            email: 'hadi.wibowo@example.com'
        },
        platform: 'Web App',
        category: 'Permintaan Fitur Baru',
        rating: 4,
        message: 'Tolong buat fitur budget sharing antar akun agar bisa kelola keuangan bareng istri.',
        status: 'pending'
    },
    {
        id: 9,
        created_at: '2026-05-24 14:20',
        user: {
            name: 'Indah Permata',
            email: 'indah.permata@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Laporan Masalah (Bug)',
        rating: 2,
        message: 'Aplikasi crash terus saat memotret struk belanja menggunakan kamera internal.',
        status: 'pending'
    },
    {
        id: 10,
        created_at: '2026-05-23 11:10',
        user: {
            name: 'Joko Widodo',
            email: 'joko.widodo@example.com'
        },
        platform: 'Web App',
        category: 'Saran / Masukan Umum',
        rating: 4,
        message: 'Dokumentasi API kurang lengkap bagi pengembang independen. Mohon diperluas.',
        status: 'replied',
        admin_reply: 'Halo Joko, kami sedang menyusun dokumentasi API versi 2 yang lebih lengkap dan ramah developer. Terima kasih masukannya.',
        replied_at: '2026-05-24 10:00'
    },
    {
        id: 11,
        created_at: '2026-05-22 08:45',
        user: {
            name: 'Kartika Sari',
            email: 'kartika.sari@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Permintaan Fitur Baru',
        rating: 5,
        message: 'Semoga ke depannya bisa ditambahkan fitur chart investasi emas juga.',
        status: 'pending'
    },
    {
        id: 12,
        created_at: '2026-05-21 13:05',
        user: {
            name: 'Lukman Hakim',
            email: 'lukman.hakim@example.com'
        },
        platform: 'Web App',
        category: 'Laporan Masalah (Bug)',
        rating: 2,
        message: 'Tombol reset filter tanggal pada history transaksi tidak berfungsi.',
        status: 'pending'
    },
    {
        id: 13,
        created_at: '2026-05-20 15:50',
        user: {
            name: 'Maria Ulfah',
            email: 'maria.ulfah@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Saran / Masukan Umum',
        rating: 3,
        message: 'Ukuran font di beberapa bagian dashboard terasa kekecilan untuk dibaca.',
        status: 'replied',
        admin_reply: 'Halo Maria, terima kasih masukannya. Kami akan menyesuaikan kontras dan ukuran font pada rilis minor berikutnya.',
        replied_at: '2026-05-21 09:30'
    },
    {
        id: 14,
        created_at: '2026-05-19 10:25',
        user: {
            name: 'Nugroho',
            email: 'nugroho@example.com'
        },
        platform: 'Web App',
        category: 'Permintaan Fitur Baru',
        rating: 4,
        message: 'Apakah bisa ditambahkan notifikasi WhatsApp sebagai alternatif pengingat tagihan bulanan?',
        status: 'pending'
    },
    {
        id: 15,
        created_at: '2026-05-18 16:15',
        user: {
            name: 'Oki Setiawan',
            email: 'oki.setiawan@example.com'
        },
        platform: 'Mobile App (Android)',
        category: 'Laporan Masalah (Bug)',
        rating: 3,
        message: 'Sinkronisasi data cloud kadang memakan waktu sangat lama jika jaringan internet tidak stabil.',
        status: 'pending'
    }
];

// Data Dummy Grafik Platform
const PLATFORM_DATA = [
    { name: 'Web App', value: 70, color: '#10B981' }, // emerald-500
    { name: 'Mobile Android', value: 30, color: '#3B82F6' } // blue-500
];

// Data Dummy Grafik Kategori
const CATEGORY_DATA = [
    { name: 'Bug', value: 45 },
    { name: 'Fitur Baru', value: 60 },
    { name: 'Saran Umum', value: 19 }
];

export default function Index() {
    const [selectedFeedback, setSelectedFeedback] = useState<Feedback | null>(null);
    const [isOpen, setIsOpen] = useState(false);
    const [listData, setListData] = useState<Feedback[]>(DUMMY_FEEDBACK_LIST);

    // Filter states
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');

    // Pagination state
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 10;

    const { data, setData, post, processing, reset } = useForm({
        admin_reply: '',
    });

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
        
        // Simulasi pengiriman form (static-preview interactivity)
        setTimeout(() => {
            if (selectedFeedback) {
                const updatedList = listData.map((item) => {
                    if (item.id === selectedFeedback.id) {
                        return {
                            ...item,
                            status: 'replied' as const,
                            admin_reply: data.admin_reply,
                            replied_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
                        };
                    }
                    return item;
                });
                setListData(updatedList);
                handleCloseReply();
            }
        }, 1000);
    };

    const getCategoryBadge = (category: string) => {
        if (category.includes('Bug')) {
            return (
                <span className="inline-flex items-center gap-1.5 text-xs text-red-700 bg-red-50 border border-red-200 rounded-full px-2.5 py-1 font-bold">
                    <Tag size={12} className="text-red-400" />
                    Bug
                </span>
            );
        }
        if (category.includes('Fitur')) {
            return (
                <span className="inline-flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-1 font-bold">
                    <Tag size={12} className="text-emerald-400" />
                    Fitur Baru
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1.5 text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-full px-2.5 py-1 font-bold">
                <Tag size={12} className="text-blue-400" />
                Saran Umum
            </span>
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
    const filteredListData = listData.filter((item) => {
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            const matchName = item.user.name.toLowerCase().includes(query);
            const matchEmail = item.user.email.toLowerCase().includes(query);
            const matchMessage = item.message.toLowerCase().includes(query);
            if (!matchName && !matchEmail && !matchMessage) return false;
        }
        if (statusFilter && item.status !== statusFilter) {
            return false;
        }
        if (categoryFilter) {
            if (categoryFilter === 'Bug' && !item.category.includes('Bug')) return false;
            if (categoryFilter === 'Fitur' && !item.category.includes('Fitur')) return false;
            if (categoryFilter === 'Saran' && !item.category.includes('Saran') && !item.category.includes('Masukan')) return false;
        }
        if (startDate || endDate) {
            const itemDateStr = item.created_at.substring(0, 10); // 'YYYY-MM-DD'
            if (startDate && itemDateStr < startDate) return false;
            if (endDate && itemDateStr > endDate) return false;
        }
        return true;
    });

    // Pagination calculations
    const totalFiltered = filteredListData.length;
    const totalPages = Math.ceil(totalFiltered / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalFiltered);
    const paginatedData = filteredListData.slice(startIndex, startIndex + itemsPerPage);

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
                    {/* Metrik: Total Masukan */}
                    <div className="flex flex-col justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div>
                            <span className="rounded-xl bg-emerald-500/10 p-3 text-emerald-600 inline-flex">
                                <MessageSquare className="size-6" />
                            </span>
                            <h3 className="mt-4 text-sm font-bold text-gray-400">Total Masukan</h3>
                            <p className="mt-2 text-3xl font-black text-gray-950">124</p>
                        </div>
                        <p className="mt-4 text-xs font-semibold text-gray-400">
                            Dari semua platform & kategori
                        </p>
                    </div>

                    {/* Metrik: Rata-rata Rating */}
                    <div className="flex flex-col justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div>
                            <span className="rounded-xl bg-amber-50 p-3 text-amber-500 border border-amber-100 inline-flex">
                                <Star className="size-6 fill-amber-400 text-amber-500" />
                            </span>
                            <h3 className="mt-4 text-sm font-bold text-gray-400">Rata-rata Rating</h3>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-3xl font-black text-gray-950">4.2</span>
                                <span className="text-sm font-bold text-gray-400">/ 5.0</span>
                            </div>
                        </div>
                        <div className="mt-4 flex items-center gap-2">
                            {renderStars(4)}
                            <span className="text-xs font-bold text-gray-400">(Bintang Statis)</span>
                        </div>
                    </div>

                    {/* Grafik Platform (PieChart) */}
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-bold text-gray-900 mb-3">Distribusi Platform</h3>
                        <div className="h-44 w-full relative">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={PLATFORM_DATA}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={45}
                                        outerRadius={65}
                                        paddingAngle={4}
                                        dataKey="value"
                                    >
                                        {PLATFORM_DATA.map((entry, index) => (
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
                            {PLATFORM_DATA.map((p) => (
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
                                    data={CATEGORY_DATA}
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
                            Menampilkan {filteredListData.length} dari {listData.length} masukan
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
                                        setCurrentPage(1);
                                    }}
                                    placeholder="Cari nama, email, pesan..."
                                    className="w-full pl-9 pr-8 py-2 text-xs font-bold rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                                />
                                {searchQuery && (
                                    <button 
                                        onClick={() => {
                                            setSearchQuery('');
                                            setCurrentPage(1);
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
                                    setCurrentPage(1);
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
                                    setCurrentPage(1);
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
                                            setCurrentPage(1);
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
                                            setCurrentPage(1);
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
                                            setCurrentPage(1);
                                        }}
                                        className="text-gray-400 hover:text-gray-600 shrink-0 ml-1"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50/70 text-xs font-black uppercase tracking-wider text-gray-500">
                                    <th className="px-6 py-4">Tanggal</th>
                                    <th className="px-6 py-4">Pengguna</th>
                                    <th className="px-6 py-4">Platform</th>
                                    <th className="px-6 py-4">Kategori</th>
                                    <th className="px-6 py-4">Rating</th>
                                    <th className="px-6 py-4">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm font-bold text-gray-700">
                                {paginatedData.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-10 text-center text-gray-400 font-medium">
                                            Tidak ada masukan yang sesuai dengan filter.
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedData.map((item) => (
                                        <tr key={item.id} className="hover:bg-gray-50/50 transition duration-150">
                                            <td className="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                                {item.created_at}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="text-gray-900 font-extrabold">{item.user.name}</span>
                                                    <span className="text-xs font-normal text-gray-400 mt-0.5">{item.user.email}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex items-center gap-1.5 text-xs">
                                                    {item.platform.includes('Web') ? (
                                                        <Laptop size={14} className="text-gray-400" />
                                                    ) : (
                                                        <Smartphone size={14} className="text-gray-400" />
                                                    )}
                                                    {item.platform}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {getCategoryBadge(item.category)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {renderStars(item.rating)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {item.status === 'replied' ? (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                                        <CheckCircle2 size={12} />
                                                        Replied
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-bold text-amber-700">
                                                        <Clock size={12} />
                                                        Pending
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right whitespace-nowrap">
                                                <button
                                                    onClick={() => handleOpenReply(item)}
                                                    className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                >
                                                    Lihat & Balas
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination UI */}
                    {totalPages > 1 && (
                        <div className="border-t border-gray-100 bg-white px-6 py-4 flex items-center justify-between">
                            <div className="text-xs text-gray-500 font-bold">
                                Menampilkan <span className="text-gray-900">{startIndex + 1}</span> sampai <span className="text-gray-900">{endIndex}</span> dari <span className="text-gray-900">{totalFiltered}</span> masukan
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
                                    disabled={currentPage === 1}
                                    className="px-3 py-1.5 text-xs font-bold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    Sebelumnya
                                </button>
                                {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                                    <button
                                        key={page}
                                        onClick={() => setCurrentPage(page)}
                                        className={`px-3 py-1.5 text-xs font-bold rounded-lg transition ${
                                            currentPage === page
                                                ? 'bg-primary text-white'
                                                : 'border border-gray-200 bg-white hover:bg-gray-50'
                                        }`}
                                    >
                                        {page}
                                    </button>
                                ))}
                                <button
                                    onClick={() => setCurrentPage((prev) => Math.min(prev + 1, totalPages))}
                                    disabled={currentPage === totalPages}
                                    className="px-3 py-1.5 text-xs font-bold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    Selanjutnya
                                </button>
                            </div>
                        </div>
                    )}
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