import type { ReactNode } from 'react';

export default function OnboardingCard({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-gray-100">
            <div className="flex flex-1 items-center justify-center px-4">
                <div className="w-full max-w-xl rounded-[28px] border border-gray-300 bg-white p-12 shadow-sm">
                    {children}
                </div>
            </div>
        </div>
    );
}
