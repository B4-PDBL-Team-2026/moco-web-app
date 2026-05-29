import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
} from 'recharts';

interface ChartDataPoint {
    label: string;
    activeUsers: number;
    registeredUsers: number;
}

interface UserStatsChartProps {
    data: ChartDataPoint[];
    filter: 'daily' | 'weekly' | 'monthly';
}

const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
        return (
            <div className="rounded-xl border border-gray-100 bg-white p-3.5 shadow-lg">
                <p className="text-xs font-semibold text-gray-500 mb-2">{label}</p>
                <div className="space-y-1.5">
                    {payload.map((entry: any) => (
                        <div
                            key={entry.name}
                            className="flex items-center gap-3 text-xs font-semibold"
                        >
                            <span
                                className="h-2 w-2 rounded-full"
                                style={{ backgroundColor: entry.color }}
                            />
                            <span className="text-gray-500">{entry.name}:</span>
                            <span className="text-gray-900 ml-auto">
                                {Number(entry.value).toLocaleString('id-ID')}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        );
    }
    return null;
};

export default function UserStatsChart({ data, filter }: UserStatsChartProps) {
    // Generate description based on time filter
    const getPeriodDescription = () => {
        switch (filter) {
            case 'daily':
                return 'Statistik harian selama 1 minggu terakhir';
            case 'weekly':
                return 'Statistik mingguan selama 1 bulan terakhir';
            case 'monthly':
                return 'Statistik bulanan selama 1 tahun terakhir';
            default:
                return '';
        }
    };

    return (
        <div className="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            {/* Chart Header */}
            <div className="mb-6">
                <h3 className="text-base font-bold text-gray-900">
                    Tren Pertumbuhan Pengguna
                </h3>
                <p className="mt-0.5 text-xs text-gray-400">
                    {getPeriodDescription()}
                </p>
            </div>

            {/* Chart Body */}
            <div className="min-h-60 w-full flex-1">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart
                        key={filter} // Re-renders the component to trigger new animation when filter changes
                        data={data}
                        margin={{ top: 5, right: 10, left: 0, bottom: 5 }}
                    >
                        <CartesianGrid
                            strokeDasharray="3 3"
                            stroke="#F3F4F6"
                            vertical={false}
                        />
                        <XAxis
                            dataKey="label"
                            stroke="#9CA3AF"
                            fontSize={11}
                            tickLine={false}
                            axisLine={false}
                            dy={10}
                        />
                        <YAxis
                            stroke="#9CA3AF"
                            fontSize={11}
                            tickLine={false}
                            axisLine={false}
                            dx={-10}
                            tickFormatter={(value) =>
                                Number(value).toLocaleString('id-ID')
                            }
                        />
                        <Tooltip content={<CustomTooltip />} />
                        <Legend
                            verticalAlign="top"
                            align="right"
                            iconType="circle"
                            iconSize={6}
                            height={36}
                            formatter={(value) => (
                                <span className="text-xs font-semibold text-gray-600">
                                    {value}
                                </span>
                            )}
                        />
                        <Line
                            name="Pengguna Aktif"
                            type="monotone"
                            dataKey="activeUsers"
                            stroke="#2E5AA7"
                            strokeWidth={2.5}
                            dot={{ r: 4, stroke: '#2E5AA7', strokeWidth: 1.5, fill: '#FFFFFF' }}
                            activeDot={{ r: 6, fill: '#2E5AA7' }}
                            isAnimationActive={true}
                            animationDuration={700}
                            animationEasing="ease-in-out"
                        />
                        <Line
                            name="Pengguna Terdaftar"
                            type="monotone"
                            dataKey="registeredUsers"
                            stroke="#FF9C13"
                            strokeWidth={2.5}
                            dot={{ r: 4, stroke: '#FF9C13', strokeWidth: 1.5, fill: '#FFFFFF' }}
                            activeDot={{ r: 6, fill: '#FF9C13' }}
                            isAnimationActive={true}
                            animationDuration={700}
                            animationEasing="ease-in-out"
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
