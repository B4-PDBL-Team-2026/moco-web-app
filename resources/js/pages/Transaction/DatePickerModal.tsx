import { useState, useEffect } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface Props {
  open: boolean;
  value: string; // ISO format or YYYY-MM-DD
  onConfirm: (date: string) => void;
  onClose: () => void;
}

const MONTH_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const DAY_SHORT = ['S', 'S', 'R', 'K', 'J', 'S', 'M']; // Sen, Sel, Rab, Kam, Jum, Sab, Min

function getDaysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(year: number, month: number) {
  // adjust getDay() to make Monday = 0, Sunday = 6
  const day = new Date(year, month, 1).getDay();
  return day === 0 ? 6 : day - 1;
}

export default function DatePickerModal({
  open,
  value,
  onConfirm,
  onClose,
}: Props) {
  const [currentYear, setCurrentYear] = useState(new Date().getFullYear());
  const [currentMonth, setCurrentMonth] = useState(new Date().getMonth());
  const [selectedDate, setSelectedDate] = useState<string>('');

  // Sync state on open
  useEffect(() => {
    if (!open) return;
    
    let initialDate = value;
    if (!initialDate) {
      initialDate = new Date().toISOString().split('T')[0];
    }
    
    setSelectedDate(initialDate);
    
    const [y, m] = initialDate.split('-').map(Number);
    if (!isNaN(y) && !isNaN(m)) {
      setCurrentYear(y);
      setCurrentMonth(m - 1);
    }
  }, [open, value]);

  if (!open) return null;

  const daysCount = getDaysInMonth(currentYear, currentMonth);
  const firstDayOffset = getFirstDayOfMonth(currentYear, currentMonth);

  const handlePrevMonth = () => {
    setCurrentMonth((m) => {
      if (m === 0) {
        setCurrentYear((y) => y - 1);
        return 11;
      }
      return m - 1;
    });
  };

  const handleNextMonth = () => {
    setCurrentMonth((m) => {
      if (m === 11) {
        setCurrentYear((y) => y + 1);
        return 0;
      }
      return m + 1;
    });
  };

  const selectDay = (day: number) => {
    const mm = String(currentMonth + 1).padStart(2, '0');
    const dd = String(day).padStart(2, '0');
    setSelectedDate(`${currentYear}-${mm}-${dd}`);
  };

  const handleConfirm = () => {
    if (selectedDate) {
      onConfirm(selectedDate);
    }
  };

  // Generate date cells
  const cells: (number | null)[] = [];
  for (let i = 0; i < firstDayOffset; i++) {
    cells.push(null);
  }
  for (let d = 1; d <= daysCount; d++) {
    cells.push(d);
  }

  return (
    <div className="absolute inset-0 z-50 flex items-center justify-center bg-black/40">
      <div className="w-[380px] rounded-[32px] bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-xl font-black text-gray-900">
          Pilih Tanggal
        </h2>

        {/* Month Navigation */}
        <div className="mb-4 flex items-center justify-between">
          <button
            type="button"
            onClick={handlePrevMonth}
            className="flex h-8 w-8 items-center justify-center rounded-full border border-gray-100 hover:bg-gray-50 active:scale-95 cursor-pointer text-gray-600"
          >
            <ChevronLeft size={16} strokeWidth={2.5} />
          </button>
          <span className="text-sm font-black text-gray-800">
            {MONTH_ID[currentMonth]} {currentYear}
          </span>
          <button
            type="button"
            onClick={handleNextMonth}
            className="flex h-8 w-8 items-center justify-center rounded-full border border-gray-100 hover:bg-gray-50 active:scale-95 cursor-pointer text-gray-600"
          >
            <ChevronRight size={16} strokeWidth={2.5} />
          </button>
        </div>

        {/* Calendar Grid */}
        <div className="rounded-2xl border border-gray-100 p-4 bg-gray-50/30">
          <div className="mb-3 grid grid-cols-7 gap-2 text-center">
            {DAY_SHORT.map((day, idx) => (
              <div
                key={idx}
                className="text-[10px] font-black text-gray-400 uppercase tracking-wider"
              >
                {day}
              </div>
            ))}
          </div>

          <div className="grid grid-cols-7 gap-2 text-center">
            {cells.map((day, idx) => {
              if (day === null) {
                return <div key={`empty-${idx}`} />;
              }

              const mm = String(currentMonth + 1).padStart(2, '0');
              const dd = String(day).padStart(2, '0');
              const cellDateStr = `${currentYear}-${mm}-${dd}`;
              const isSelected = cellDateStr === selectedDate;

              return (
                <button
                  key={`day-${day}`}
                  type="button"
                  onClick={() => selectDay(day)}
                  className={`
                    h-9
                    w-9
                    mx-auto
                    flex
                    items-center
                    justify-center
                    rounded-xl
                    text-xs
                    font-bold
                    transition-all
                    duration-150
                    cursor-pointer
                    ${
                      isSelected
                        ? "bg-[#355EB7] text-white shadow-md shadow-blue-500/20 scale-105"
                        : "text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                    }
                  `}
                >
                  {day}
                </button>
              );
            })}
          </div>
        </div>

        {/* Action Buttons */}
        <div className="mt-6 flex gap-4">
          <button
            type="button"
            onClick={onClose}
            className="
              flex-1
              rounded-full
              border
              border-red-500
              py-3
              text-sm
              font-bold
              text-red-500
              hover:bg-red-50
              active:scale-98
              transition-all
              cursor-pointer
            "
          >
            Batal
          </button>

          <button
            type="button"
            onClick={handleConfirm}
            disabled={!selectedDate}
            className="
              flex-1
              rounded-full
              bg-[#FF9800]
              py-3
              text-sm
              font-bold
              text-white
              hover:bg-orange-600
              active:scale-98
              transition-all
              disabled:opacity-50
              disabled:pointer-events-none
              cursor-pointer
            "
          >
            Pilih
          </button>
        </div>
      </div>
    </div>
  );
}