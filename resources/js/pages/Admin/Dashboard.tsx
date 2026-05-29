import { Eye, FileCheck, UserPlus, Users, UsersRound } from 'lucide-react';
import { useState } from 'react';

import StatCard from '@/components/StatCard';
import StatFilterTabs from '@/components/StatFilterTabs';
import UserStatsChart from '@/components/UserStatsChart';
import AdminLayout from '@/layouts/AdminLayout';

type TimeFilter = 'daily' | 'weekly' | 'monthly';

const FILTER_OPTIONS: { key: TimeFilter; label: string }[] = [
    { key: 'daily', label: 'Harian' },
    { key: 'weekly', label: 'Mingguan' },
    { key: 'monthly', label: 'Bulanan' },
];

const USER_STATS: Record<
    TimeFilter,
    {
        activeUsers: {
            value: string;
            change: { value: string; trend: 'up' | 'down' };
        };
        registeredUsers: {
            value: string;
            change: { value: string; trend: 'up' | 'down' };
        };
    }
> = {
    daily: {
        activeUsers: {
            value: '1.284',
            change: { value: '+2,3% vs kemarin', trend: 'up' },
        },
        registeredUsers: {
            value: '87',
            change: { value: '+5,4% vs kemarin', trend: 'up' },
        },
    },
    weekly: {
        activeUsers: {
            value: '6.921',
            change: { value: '+8,1% vs minggu lalu', trend: 'up' },
        },
        registeredUsers: {
            value: '412',
            change: { value: '+12,3% vs minggu lalu', trend: 'up' },
        },
    },
    monthly: {
        activeUsers: {
            value: '24.130',
            change: { value: '+15,4% vs bulan lalu', trend: 'up' },
        },
        registeredUsers: {
            value: '1.860',
            change: { value: '+18,2% vs bulan lalu', trend: 'up' },
        },
    },
};

const CHART_DATA: Record<
    TimeFilter,
    { label: string; activeUsers: number; registeredUsers: number }[]
> = {
    daily: [
        { label: 'Senin', activeUsers: 1012, registeredUsers: 64 },
        { label: 'Selasa', activeUsers: 1105, registeredUsers: 72 },
        { label: 'Rabu', activeUsers: 1098, registeredUsers: 68 },
        { label: 'Kamis', activeUsers: 1201, registeredUsers: 81 },
        { label: 'Jumat', activeUsers: 1184, registeredUsers: 79 },
        { label: 'Sabtu', activeUsers: 1240, registeredUsers: 85 },
        { label: 'Minggu', activeUsers: 1284, registeredUsers: 87 },
    ],
    weekly: [
        { label: 'Minggu 1', activeUsers: 5980, registeredUsers: 340 },
        { label: 'Minggu 2', activeUsers: 6120, registeredUsers: 362 },
        { label: 'Minggu 3', activeUsers: 6490, registeredUsers: 388 },
        { label: 'Minggu 4', activeUsers: 6921, registeredUsers: 412 },
    ],
    monthly: [
        { label: 'Jan', activeUsers: 12500, registeredUsers: 950 },
        { label: 'Feb', activeUsers: 13200, registeredUsers: 1020 },
        { label: 'Mar', activeUsers: 14100, registeredUsers: 1110 },
        { label: 'Apr', activeUsers: 15300, registeredUsers: 1180 },
        { label: 'Mei', activeUsers: 16800, registeredUsers: 1290 },
        { label: 'Jun', activeUsers: 17500, registeredUsers: 1350 },
        { label: 'Jul', activeUsers: 18900, registeredUsers: 1420 },
        { label: 'Agu', activeUsers: 19400, registeredUsers: 1490 },
        { label: 'Sep', activeUsers: 20800, registeredUsers: 1580 },
        { label: 'Okt', activeUsers: 21500, registeredUsers: 1650 },
        { label: 'Nov', activeUsers: 22900, registeredUsers: 1740 },
        { label: 'Des', activeUsers: 24130, registeredUsers: 1860 },
    ],
};

const LANDING_PAGE_STATS = {
    totalVisitors: '14.320',
    uniqueVisitors: '9.841',
    scrollDepthReached: '3.207',
};

export default function Dashboard() {
    const [filter, setFilter] = useState<TimeFilter>('daily');

    const currentStats = USER_STATS[filter];

    return (
        <AdminLayout>
            <div className="space-y-8 p-6 lg:p-8">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Dashboard Admin
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Pantau performa aplikasi dan interaksi landing page
                        MOCO.
                    </p>
                </div>

                {/* Section: Landing Page Statistics */}
                <div className="space-y-4">
                    <h2 className="text-lg font-semibold text-gray-800">
                        Statistik Landing Page
                    </h2>

                    <div className="grid gap-6 md:grid-cols-3">
                        <StatCard
                            title="Total Pengunjung"
                            value={LANDING_PAGE_STATS.totalVisitors}
                            icon={<Eye className="size-6" />}
                            accent="green"
                        />
                        <StatCard
                            title="Pengunjung Unik"
                            value={LANDING_PAGE_STATS.uniqueVisitors}
                            icon={<UsersRound className="size-6" />}
                            accent="purple"
                        />
                        <StatCard
                            title="Lihat sampai akhir"
                            value={LANDING_PAGE_STATS.scrollDepthReached}
                            icon={<FileCheck className="size-6" />}
                            accent="blue"
                        />
                    </div>
                </div>

                {/* Section: User Statistics */}
                <div className="space-y-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-lg font-semibold text-gray-800">
                            Statistik Pengguna
                        </h2>
                        <StatFilterTabs
                            active={filter}
                            onChange={(key) => setFilter(key)}
                            options={FILTER_OPTIONS}
                        />
                    </div>

                    <div className="grid grid-cols-6 gap-6">
                        {/* Line Chart: Span 4 columns on large screens, spanning 2 rows */}
                        <div className="col-span-6 lg:col-span-4 lg:row-span-2">
                            <UserStatsChart
                                data={CHART_DATA[filter]}
                                filter={filter}
                            />
                        </div>

                        {/* Active Users Card: Span 2 columns */}
                        <div className="col-span-6 sm:col-span-3 lg:col-span-2">
                            <StatCard
                                title="Pengguna Aktif"
                                value={currentStats.activeUsers.value}
                                change={currentStats.activeUsers.change}
                                icon={<Users className="size-6" />}
                                accent="blue"
                            />
                        </div>

                        {/* Registered Users Card: Span 2 columns */}
                        <div className="col-span-6 sm:col-span-3 lg:col-span-2">
                            <StatCard
                                title="Pengguna Terdaftar"
                                value={currentStats.registeredUsers.value}
                                change={currentStats.registeredUsers.change}
                                icon={<UserPlus className="size-6" />}
                                accent="orange"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
