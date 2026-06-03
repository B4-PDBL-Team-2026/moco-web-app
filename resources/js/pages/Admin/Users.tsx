import { router } from '@inertiajs/react';
import { PencilLine, Power, Search, Trash, Verified } from 'lucide-react';
import { useEffect, useState } from 'react';

import Badge from '@/components/Badge';
import type { Column } from '@/components/DataTable';
import DataTable from '@/components/DataTable';
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import type { User } from '@/components/EditUserDrawer';
import EditUserDrawer from '@/components/EditUserDrawer';
import ForceLogoutDialog from '@/components/ForceLogoutDialog';
import Pagination from '@/components/Pagination';
import AdminLayout from '@/layouts/AdminLayout';

interface PaginatedData {
    data: User[];
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

interface UsersPageProps {
    users: PaginatedData;
    filters: { search?: string };
}

export default function Users({ users, filters }: UsersPageProps) {
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [selectedUser, setSelectedUser] = useState<User | null>(null);

    // Dialog state controllers
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isForceLogoutOpen, setIsForceLogoutOpen] = useState(false);
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);

    // Effect: Debounce search every 1 char typed
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (searchQuery !== filters.search) {
                router.get(
                    '/admin/users',
                    { search: searchQuery },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [searchQuery, filters.search]);

    // Handle Edit profile saving via Inertia
    const handleSaveUser = (
        id: number,
        name: string,
        status: 'active' | 'banned',
        banDuration?: string,
    ) => {
        router.put(
            `/admin/users/${id}`,
            { name, status, banDuration },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsEditOpen(false);
                    setSelectedUser(null);
                },
            },
        );
    };

    // Handle Force logout via Inertia
    const handleForceLogout = () => {
        if (selectedUser) {
            router.post(
                `/admin/users/${selectedUser.id}/force-logout`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setIsForceLogoutOpen(false);
                        setSelectedUser(null);
                    },
                },
            );
        }
    };

    // Handle Delete user via Inertia
    const handleDeleteUser = () => {
        if (selectedUser) {
            router.delete(`/admin/users/${selectedUser.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setIsDeleteOpen(false);
                    setSelectedUser(null);
                },
            });
        }
    };

    // Helper navigasi halaman
    const changePage = (page: number) => {
        router.get(
            '/admin/users',
            { search: searchQuery, page },
            { preserveState: true, preserveScroll: true },
        );
    };

    const columns: Column<User>[] = [
        {
            key: 'index',
            label: '#',
            align: 'center',
            className: 'font-semibold text-gray-400',
            render: (_, idx) => users.from + idx,
        },
        {
            key: 'user',
            label: 'Pengguna',
            render: (user) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-light font-bold text-primary">
                        {user.avatarInitials}
                    </div>
                    <div>
                        <p className="font-bold text-gray-800">{user.name}</p>
                        <div className="flex items-center gap-1.5">
                            <p className="text-xs text-gray-400">
                                {user.email}
                            </p>
                            {user.emailVerified && (
                                <span
                                    title="Email Terverifikasi"
                                    className="inline-flex items-center justify-center rounded-full bg-green-50 p-0.5 text-green-500"
                                >
                                    <Verified
                                        size={14}
                                        className="stroke-[3px]"
                                    />
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            ),
        },
        {
            key: 'joinedAt',
            label: 'Terdaftar',
            className: 'font-medium text-gray-500',
        },
        {
            key: 'status',
            label: 'Status',
            render: (user) =>
                user.status === 'active' ? (
                    <Badge color="emerald" dot={true}>
                        Aktif
                    </Badge>
                ) : (
                    <Badge color="red" dot={true} dotPulse={true}>
                        Banned ({user.banDuration})
                    </Badge>
                ),
        },
        {
            key: 'session',
            label: 'Sesi',
            align: 'center',
            render: (user) =>
                user.isLoggedIn ? (
                    <Badge color="primary" dot={true}>
                        Online
                    </Badge>
                ) : (
                    <Badge color="gray">
                        Offline
                    </Badge>
                ),
        },
        {
            key: 'actions',
            label: 'Aksi',
            align: 'center',
            render: (user) => (
                <div className="flex items-center justify-center gap-1.5">
                    <button
                        title="Edit Profil"
                        onClick={() => {
                            setSelectedUser(user);
                            setIsEditOpen(true);
                        }}
                        className="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    >
                        <PencilLine size={18} />
                    </button>
                    <button
                        title="Paksa Logout"
                        disabled={!user.isLoggedIn}
                        onClick={() => {
                            setSelectedUser(user);
                            setIsForceLogoutOpen(true);
                        }}
                        className={`flex h-9 w-9 items-center justify-center rounded-xl transition ${
                            user.isLoggedIn
                                ? 'text-orange-400 hover:bg-orange-50 hover:text-orange-600'
                                : 'cursor-not-allowed text-gray-200'
                        }`}
                    >
                        <Power size={18} />
                    </button>
                    <button
                        title="Hapus Pengguna"
                        onClick={() => {
                            setSelectedUser(user);
                            setIsDeleteOpen(true);
                        }}
                        className="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                    >
                        <Trash size={18} />
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout>
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header Section */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">
                            Kelola User
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Pantau, ubah status, tangani sesi, dan hapus akun
                            pengguna MOCO.
                        </p>
                    </div>

                    {/* Search Field */}
                    <div className="relative w-full max-w-md sm:w-80">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                            <Search size={18} className="text-gray-400" />
                        </span>
                        <input
                            type="text"
                            placeholder="Cari nama atau email..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full rounded-2xl border border-gray-200 bg-white py-3 pr-4 pl-10 text-sm text-gray-800 placeholder-gray-400 transition outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                        />
                    </div>
                </div>

                {/* Table Container */}
                <div className="mt-8 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <DataTable
                        columns={columns}
                        data={users.data}
                        emptyMessage={`Tidak ada pengguna ditemukan untuk pencarian "${searchQuery}"`}
                    />

                    {/* Pagination Bar bawaan Laravel/Inertia */}
                    <Pagination
                        currentPage={users.current_page}
                        totalPages={users.last_page}
                        totalItems={users.total}
                        from={users.from}
                        to={users.to}
                        onPageChange={changePage}
                        itemLabel="pengguna"
                        showPageNumbers={true}
                    />
                </div>
            </div>

            <EditUserDrawer
                open={isEditOpen}
                user={selectedUser}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedUser(null);
                }}
                onSave={handleSaveUser}
            />

            <ForceLogoutDialog
                open={isForceLogoutOpen}
                userName={selectedUser?.name}
                onConfirm={handleForceLogout}
                onCancel={() => {
                    setIsForceLogoutOpen(false);
                    setSelectedUser(null);
                }}
            />

            <DeleteConfirmDialog
                open={isDeleteOpen}
                title="Hapus Pengguna?"
                description={`Apakah Anda yakin ingin menghapus akun ${selectedUser?.name}? Seluruh data keuangan, preferensi, dan riwayat budgeting pengguna akan terhapus permanen.`}
                onConfirm={handleDeleteUser}
                onCancel={() => {
                    setIsDeleteOpen(false);
                    setSelectedUser(null);
                }}
            />
        </AdminLayout>
    );
}
