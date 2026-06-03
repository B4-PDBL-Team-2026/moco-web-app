import { Head } from '@inertiajs/react';
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

interface DashboardProps {
    landingPageStats: {
        totalVisitors: string;
        uniqueVisitors: string;
        scrollDepthReached: string;
    };
    userStats: Record<
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
    >;
    chartData: Record<
        TimeFilter,
        { label: string; activeUsers: number; registeredUsers: number }[]
    >;
}

export default function Dashboard({ landingPageStats, userStats, chartData }: DashboardProps) {
    const [filter, setFilter] = useState<TimeFilter>('daily');

    const currentStats = userStats[filter];

    return (
        <AdminLayout>
            <Head title="Dashboard Admin" />

            <div className="space-y-8 p-6 lg:p-8">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Dashboard Admin
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Pantau performa aplikasi dan interaksi landing page MOCO.
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
                            value={landingPageStats.totalVisitors}
                            icon={<Eye className="size-6" />}
                            accent="green"
                            layout="horizontal"
                        />
                        <StatCard
                            title="Pengunjung Unik"
                            value={landingPageStats.uniqueVisitors}
                            icon={<UsersRound className="size-6" />}
                            accent="purple"
                            layout="horizontal"
                        />
                        <StatCard
                            title="Lihat sampai akhir"
                            value={landingPageStats.scrollDepthReached}
                            icon={<FileCheck className="size-6" />}
                            accent="blue"
                            layout="horizontal"
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
                                // Panggil chartData dari props Inertia
                                data={chartData[filter]}
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
