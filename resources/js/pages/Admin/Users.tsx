import { PencilLine, Power, Search, Trash, Verified } from 'lucide-react';
import { useState } from 'react';

import DeleteConfirmDialog from '@/components/DeleteConfirmDialog';
import type { User } from '@/components/EditUserDrawer';
import EditUserDrawer from '@/components/EditUserDrawer';
import ForceLogoutDialog from '@/components/ForceLogoutDialog';
import AdminLayout from '@/layouts/AdminLayout';

const INITIAL_USERS: User[] = [
    {
        id: 1,
        name: 'Alice Johnson',
        email: 'alice.j@example.com',
        joinedAt: '12 Jan 2026',
        status: 'active',
        isLoggedIn: true,
        avatarInitials: 'AJ',
        emailVerified: true,
    },
    {
        id: 2,
        name: 'Budi Santoso',
        email: 'budi.s@example.com',
        joinedAt: '18 Jan 2026',
        status: 'active',
        isLoggedIn: false,
        avatarInitials: 'BS',
        emailVerified: true,
    },
    {
        id: 3,
        name: 'Charlie Brown',
        email: 'charlie.b@example.com',
        joinedAt: '03 Feb 2026',
        status: 'active',
        isLoggedIn: false,
        avatarInitials: 'CB',
        emailVerified: false,
    },
    {
        id: 4,
        name: 'Dewi Lestari',
        email: 'dewi.l@example.com',
        joinedAt: '15 Feb 2026',
        status: 'active',
        isLoggedIn: true,
        avatarInitials: 'DL',
        emailVerified: true,
    },
    {
        id: 5,
        name: 'Eko Prasetyo',
        email: 'eko.p@example.com',
        joinedAt: '22 Feb 2026',
        status: 'active',
        isLoggedIn: false,
        avatarInitials: 'EP',
        emailVerified: true,
    },
    {
        id: 6,
        name: 'Farida Aulia',
        email: 'farida.a@example.com',
        joinedAt: '01 Mar 2026',
        status: 'active',
        isLoggedIn: true,
        avatarInitials: 'FA',
        emailVerified: true,
    },
    {
        id: 7,
        name: 'Guntur Wijaya',
        email: 'guntur.w@example.com',
        joinedAt: '10 Mar 2026',
        status: 'banned',
        isLoggedIn: false,
        avatarInitials: 'GW',
        banDuration: '3 hari',
        emailVerified: true,
    },
    {
        id: 8,
        name: 'Hana Putri',
        email: 'hana.p@example.com',
        joinedAt: '14 Mar 2026',
        status: 'active',
        isLoggedIn: true,
        avatarInitials: 'HP',
        emailVerified: false,
    },
    {
        id: 9,
        name: 'Indra Kusuma',
        email: 'indra.k@example.com',
        joinedAt: '20 Mar 2026',
        status: 'active',
        isLoggedIn: false,
        avatarInitials: 'IK',
        emailVerified: true,
    },
    {
        id: 10,
        name: 'Joko Widodo',
        email: 'joko.w@example.com',
        joinedAt: '29 Mar 2026',
        status: 'active',
        isLoggedIn: false,
        avatarInitials: 'JW',
        emailVerified: true,
    },
];

const PAGE_SIZE = 5; // Simulates easily configurable page sizes

export default function Users() {
    const [users, setUsers] = useState<User[]>(INITIAL_USERS);
    const [searchQuery, setSearchQuery] = useState('');
    const [currentPage, setCurrentPage] = useState(1);

    // Selected user for actions
    const [selectedUser, setSelectedUser] = useState<User | null>(null);

    // Dialog state controllers
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isForceLogoutOpen, setIsForceLogoutOpen] = useState(false);
    const [isDeleteOpen, setIsDeleteOpen] = useState(false);

    // Client-side search filtering
    const filteredUsers = users.filter((user) => {
        const query = searchQuery.toLowerCase().trim();
        return (
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
        );
    });

    // Reset pagination to first page when filtering changes
    const handleSearchChange = (val: string) => {
        setSearchQuery(val);
        setCurrentPage(1);
    };

    // Pagination calculations
    const totalItems = filteredUsers.length;
    const totalPages = Math.ceil(totalItems / PAGE_SIZE) || 1;
    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const endIndex = Math.min(startIndex + PAGE_SIZE, totalItems);
    const paginatedUsers = filteredUsers.slice(startIndex, endIndex);

    // Handle Edit profile saving
    const handleSaveUser = (
        id: number,
        name: string,
        status: 'active' | 'banned',
        banDuration?: string,
    ) => {
        setUsers((prev) =>
            prev.map((user) => {
                if (user.id === id) {
                    // If marked banned, also force log out
                    const isLoggedIn =
                        status === 'banned' ? false : user.isLoggedIn;
                    // Re-calculate initials
                    const initials = name
                        .split(' ')
                        .map((n) => n[0])
                        .slice(0, 2)
                        .join('')
                        .toUpperCase();
                    return {
                        ...user,
                        name,
                        status,
                        banDuration,
                        isLoggedIn,
                        avatarInitials: initials || user.avatarInitials,
                    };
                }
                return user;
            }),
        );
    };

    // Handle Force logout action
    const handleForceLogout = () => {
        if (selectedUser) {
            setUsers((prev) =>
                prev.map((user) =>
                    user.id === selectedUser.id
                        ? { ...user, isLoggedIn: false }
                        : user,
                ),
            );
            setIsForceLogoutOpen(false);
            setSelectedUser(null);
        }
    };

    // Handle Delete user action
    const handleDeleteUser = () => {
        if (selectedUser) {
            setUsers((prev) =>
                prev.filter((user) => user.id !== selectedUser.id),
            );
            setIsDeleteOpen(false);
            setSelectedUser(null);

            // Adjust pagination page if page becomes empty
            const remainingCount = filteredUsers.length - 1;
            const maxPage = Math.ceil(remainingCount / PAGE_SIZE) || 1;
            if (currentPage > maxPage) {
                setCurrentPage(maxPage);
            }
        }
    };

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
                            onChange={(e) => handleSearchChange(e.target.value)}
                            className="w-full rounded-2xl border border-gray-200 bg-white py-3 pr-4 pl-10 text-sm text-gray-800 placeholder-gray-400 transition outline-none focus:border-primary focus:ring-1 focus:ring-primary/20"
                        />
                    </div>
                </div>

                {/* Table Container */}
                <div className="mt-8 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-gray-600">
                            <thead className="border-b border-gray-50 bg-gray-50/50 text-[11px] font-bold tracking-wider text-primary uppercase">
                                <tr>
                                    <th className="px-6 py-4 text-center">#</th>
                                    <th className="px-6 py-4">Pengguna</th>
                                    <th className="px-6 py-4">Terdaftar</th>
                                    <th className="px-6 py-4">Status</th>
                                    <th className="px-6 py-4 text-center">
                                        Sesi
                                    </th>
                                    <th className="px-6 py-4 text-center">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {paginatedUsers.length > 0 ? (
                                    paginatedUsers.map((user, idx) => {
                                        const globalIndex =
                                            startIndex + idx + 1;
                                        return (
                                            <tr
                                                key={user.id}
                                                className="transition hover:bg-gray-50/50"
                                            >
                                                {/* Index Column */}
                                                <td className="px-6 py-5 text-center font-semibold text-gray-400">
                                                    {globalIndex}
                                                </td>

                                                {/* Profile Details Column */}
                                                <td className="px-6 py-5">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-light font-bold text-primary">
                                                            {
                                                                user.avatarInitials
                                                            }
                                                        </div>
                                                        <div>
                                                            <p className="font-bold text-gray-800">
                                                                {user.name}
                                                            </p>
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
                                                                            size={
                                                                                14
                                                                            }
                                                                            className="stroke-[3px]"
                                                                        />
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Date Registered Column */}
                                                <td className="px-6 py-5 font-medium text-gray-500">
                                                    {user.joinedAt}
                                                </td>

                                                {/* Status Badge Column */}
                                                <td className="px-6 py-5">
                                                    {user.status ===
                                                    'active' ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-600">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-green-500" />
                                                            Aktif
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600">
                                                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-red-500" />
                                                            Banned (
                                                            {user.banDuration})
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Session Badge Column */}
                                                <td className="px-6 py-5 text-center">
                                                    {user.isLoggedIn ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-primary-light px-2.5 py-1 text-xs font-bold text-primary">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                                                            Online
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full border border-gray-100 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-400">
                                                            Offline
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Action Buttons Column */}
                                                <td className="px-6 py-5">
                                                    <div className="flex items-center justify-center gap-1.5">
                                                        {/* Edit Button */}
                                                        <button
                                                            title="Edit Profil"
                                                            onClick={() => {
                                                                setSelectedUser(
                                                                    user,
                                                                );
                                                                setIsEditOpen(
                                                                    true,
                                                                );
                                                            }}
                                                            className="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                                        >
                                                            <PencilLine
                                                                size={18}
                                                            />
                                                        </button>

                                                        {/* Force Logout Button */}
                                                        <button
                                                            title="Paksa Logout"
                                                            disabled={
                                                                !user.isLoggedIn
                                                            }
                                                            onClick={() => {
                                                                setSelectedUser(
                                                                    user,
                                                                );
                                                                setIsForceLogoutOpen(
                                                                    true,
                                                                );
                                                            }}
                                                            className={`flex h-9 w-9 items-center justify-center rounded-xl transition ${
                                                                user.isLoggedIn
                                                                    ? 'text-orange-400 hover:bg-orange-50 hover:text-orange-600'
                                                                    : 'cursor-not-allowed text-gray-200'
                                                            }`}
                                                        >
                                                            <Power size={18} />
                                                        </button>

                                                        {/* Delete Button */}
                                                        <button
                                                            title="Hapus Pengguna"
                                                            onClick={() => {
                                                                setSelectedUser(
                                                                    user,
                                                                );
                                                                setIsDeleteOpen(
                                                                    true,
                                                                );
                                                            }}
                                                            className="flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                                                        >
                                                            <Trash size={18} />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-12 text-center text-sm text-gray-400"
                                        >
                                            Tidak ada pengguna ditemukan untuk
                                            pencarian "{searchQuery}"
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Bar */}
                    {totalItems > 0 && (
                        <div className="flex flex-col gap-4 border-t border-gray-50 bg-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            {/* Summary Text */}
                            <p className="text-xs font-semibold text-gray-400">
                                Menampilkan{' '}
                                <span className="font-bold text-gray-700">
                                    {startIndex + 1}
                                </span>{' '}
                                hingga{' '}
                                <span className="font-bold text-gray-700">
                                    {endIndex}
                                </span>{' '}
                                dari{' '}
                                <span className="font-bold text-gray-700">
                                    {totalItems}
                                </span>{' '}
                                pengguna
                            </p>

                            {/* Navigation Buttons */}
                            <div className="flex gap-2">
                                <button
                                    disabled={currentPage === 1}
                                    onClick={() =>
                                        setCurrentPage((c) =>
                                            Math.max(c - 1, 1),
                                        )
                                    }
                                    className={`rounded-xl border px-4 py-2.5 text-xs font-bold transition ${
                                        currentPage === 1
                                            ? 'cursor-not-allowed border-gray-100 text-gray-300'
                                            : 'border-gray-200 text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    Sebelumnya
                                </button>
                                <button
                                    disabled={currentPage === totalPages}
                                    onClick={() =>
                                        setCurrentPage((c) =>
                                            Math.min(c + 1, totalPages),
                                        )
                                    }
                                    className={`rounded-xl border px-4 py-2.5 text-xs font-bold transition ${
                                        currentPage === totalPages
                                            ? 'cursor-not-allowed border-gray-100 text-gray-300'
                                            : 'border-gray-200 text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    Selanjutnya
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Actions: Slide-out drawer for Edit */}
            <EditUserDrawer
                open={isEditOpen}
                user={selectedUser}
                onClose={() => {
                    setIsEditOpen(false);
                    setSelectedUser(null);
                }}
                onSave={handleSaveUser}
            />

            {/* Actions: Modal for Force Logout */}
            <ForceLogoutDialog
                open={isForceLogoutOpen}
                userName={selectedUser?.name}
                onConfirm={handleForceLogout}
                onCancel={() => {
                    setIsForceLogoutOpen(false);
                    setSelectedUser(null);
                }}
            />

            {/* Actions: Modal for Delete */}
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
