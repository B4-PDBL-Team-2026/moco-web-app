import React from 'react';

export interface Column<T> {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
    className?: string;
    render?: (item: T, index: number) => React.ReactNode;
}

interface DataTableProps {
    columns: Column<any>[];
    data: any[];
    emptyMessage?: string;
}

export default function DataTable({
    columns,
    data,
    emptyMessage = 'Tidak ada data ditemukan.',
}: DataTableProps) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
                <thead>
                    <tr className="border-b border-gray-100 bg-gray-50/70 text-xs font-black uppercase tracking-wider text-gray-500">
                        {columns.map((col) => {
                            const alignClass =
                                col.align === 'center'
                                    ? 'text-center'
                                    : col.align === 'right'
                                    ? 'text-right'
                                    : 'text-left';
                            return (
                                <th
                                    key={col.key}
                                    className={`px-6 py-4 font-black ${alignClass} ${col.className || ''}`}
                                >
                                    {col.label}
                                </th>
                            );
                        })}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 text-sm font-bold text-gray-700">
                    {data.length === 0 ? (
                        <tr>
                            <td
                                colSpan={columns.length}
                                className="px-6 py-10 text-center text-gray-400 font-medium"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        data.map((item, itemIdx) => (
                            <tr
                                key={item.id || itemIdx}
                                className="hover:bg-gray-50/50 transition duration-150"
                            >
                                {columns.map((col) => {
                                    const alignClass =
                                        col.align === 'center'
                                            ? 'text-center'
                                            : col.align === 'right'
                                            ? 'text-right'
                                            : 'text-left';
                                    return (
                                        <td
                                            key={col.key}
                                            className={`px-6 py-4 whitespace-nowrap ${alignClass} ${col.className || ''}`}
                                        >
                                            {col.render
                                                ? col.render(item, itemIdx)
                                                : (item[col.key] as React.ReactNode)}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
