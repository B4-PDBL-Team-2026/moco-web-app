import React from 'react';

interface PaginationProps {
    currentPage: number;
    totalPages: number;
    totalItems: number;
    from: number;
    to: number;
    onPageChange: (page: number) => void;
    itemLabel: string;
    showPageNumbers?: boolean;
}

export default function Pagination({
    currentPage,
    totalPages,
    totalItems,
    from,
    to,
    onPageChange,
    itemLabel,
    showPageNumbers = true,
}: PaginationProps) {
    if (totalItems === 0) return null;

    return (
        <div className="border-t border-gray-100 bg-white px-6 py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-xs text-gray-500 font-bold">
                Menampilkan <span className="text-gray-900">{from}</span> sampai <span className="text-gray-900">{to}</span> dari <span className="text-gray-900">{totalItems}</span> {itemLabel}
            </div>
            
            <div className="flex items-center gap-2">
                <button
                    disabled={currentPage === 1}
                    onClick={() => onPageChange(currentPage - 1)}
                    className="px-3 py-1.5 text-xs font-bold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    Sebelumnya
                </button>

                {showPageNumbers && Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                    <button
                        key={page}
                        onClick={() => onPageChange(page)}
                        className={`px-3 py-1.5 text-xs font-bold rounded-lg transition ${
                            currentPage === page
                                ? 'bg-primary text-white border border-primary'
                                : 'border border-gray-200 bg-white hover:bg-gray-50'
                        }`}
                    >
                        {page}
                    </button>
                ))}

                <button
                    disabled={currentPage === totalPages || totalPages === 0}
                    onClick={() => onPageChange(currentPage + 1)}
                    className="px-3 py-1.5 text-xs font-bold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    Selanjutnya
                </button>
            </div>
        </div>
    );
}
