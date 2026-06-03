import { Head, Link } from '@inertiajs/react';

interface Props {
    bannedUntil?: string | null;
}

function formatBannedUntil(iso: string): string {
    const date = new Date(iso);
    return date.toLocaleString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZoneName: 'short',
    });
}

export default function Banned({ bannedUntil }: Props) {
    const hasDuration = Boolean(bannedUntil);

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <Head title="Akun Diblokir" />

            <div className="flex items-center gap-5">
                <span className="flex flex-col text-primary">
                    <h1 className="text-6xl font-bold">moco</h1>
                    <p className="text-xl">Money Control</p>
                </span>
            </div>

            <div className="mt-6 w-full overflow-hidden rounded-2xl border border-gray-100 bg-white p-8 shadow-md sm:max-w-lg">
                <div className="mb-6 flex justify-center">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                        <svg
                            className="h-8 w-8 text-red-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                            />
                        </svg>
                    </div>
                </div>

                <h2 className="mb-4 text-center text-3xl font-semibold text-gray-800">
                    Akun Kamu Diblokir
                </h2>

                <p className="mb-6 text-center text-gray-600">
                    {hasDuration
                        ? 'Akun kamu sedang dalam masa pemblokiran sementara oleh admin.'
                        : 'Akun kamu telah diblokir secara permanen oleh admin.'}
                </p>

                {hasDuration && bannedUntil && (
                    <div className="mb-6 rounded-lg bg-red-50 p-4 text-center text-sm text-red-700">
                        Blokir akan berakhir pada:{' '}
                        <span className="font-semibold">
                            {formatBannedUntil(bannedUntil)}
                        </span>
                    </div>
                )}

                <p className="mb-6 text-center text-sm text-gray-500">
                    Kalau kamu merasa ini adalah kesalahan, silakan hubungi tim
                    support kami untuk bantuan lebih lanjut.
                </p>

                <div className="flex items-center justify-center">
                    <Link
                        href="/auth/login"
                        className="text-sm font-medium text-primary hover:underline"
                    >
                        Kembali ke halaman login
                    </Link>
                </div>
            </div>
        </div>
    );
}
