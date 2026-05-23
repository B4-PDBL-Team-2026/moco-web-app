import type { ReactNode } from 'react';

export default function AuthCard({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-gray-100">
            <div className="flex flex-1 items-center justify-center px-4">
                <div className="w-full max-w-md rounded-[28px] border border-gray-200 bg-white p-10 shadow-sm">
                    {children}
                </div>
            </div>
        </div>
    );
}
