import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Wallet, Lock, Mail, Trash2, User, ChevronRight, MessageSquare } from 'lucide-react';
import React, { useState } from 'react';
import AppLayout from '@/layouts/AppLayout';

export default function Settings() {
    const { auth, budget_setting } = usePage().props as any;
    const user = auth.user;
    
    // States untuk toggle form dan modal
    const [isBudgetOpen, setIsBudgetOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);
    const [isVerifyModalOpen, setIsVerifyModalOpen] = useState(false); 

    // Form Budget 
    const { data, setData, post, processing } = useForm({
        min_allocation: budget_setting?.min_allocation || 0,
        max_allocation: budget_setting?.max_allocation || 0,
    });

    // Form Delete Akun
    const deleteForm = useForm({ password: '' });

    // Form Ganti Password
    const passwordForm = useForm({ email: user?.email || '' });

    // Form Verifikasi Email
    const verificationForm = useForm({});

    // Handler Budget
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/update-budget', {
            onSuccess: (): void => setIsBudgetOpen(false),
        });
    };

    // Handler Delete Akun
    const submitDelete = (e: React.FormEvent) => {
        e.preventDefault();
        deleteForm.delete('/settings/delete-account', {
            onSuccess: () => setIsDeleteModalOpen(false),
        });
    };

    // Handler Ganti Password
    const handleResetPassword = () => {
        passwordForm.post('/auth/forget-password', {
            onSuccess: () => setIsPasswordModalOpen(true),
            preserveScroll: true,
        });
    };

    // Handler Verifikasi Email 
    const handleSendVerification = () => {
        verificationForm.post('/settings/send-verification', {
            onSuccess: () => setIsVerifyModalOpen(true),
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title="Profil & Pengaturan" />
            <div className="max-w-5xl mx-auto py-10 px-8">
                <h1 className="text-4xl font-bold text-[#2E5AA7] mb-8">Profil & Pengaturan</h1>

                <div className="space-y-12">
                    <div className="bg-white border border-[#E2E2E2] shadow-sm rounded-xl p-6 flex items-center justify-between">
                        <div className="flex items-center gap-6">
                            <div className="w-20 h-20 bg-[#E3EAF7] rounded-full flex items-center justify-center">
                                <User size={40} className="text-[#5C84D1]" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-[#101010]">{user?.name}</h3>
                                <p className="text-base text-[#595D62]">{user?.email}</p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <h4 className="text-sm font-bold text-[#2E5AA7] tracking-widest">KEUANGAN</h4>
                        <div className="bg-white border border-[#E2E8F0] rounded-2xl">
                            <button onClick={() => setIsBudgetOpen(!isBudgetOpen)} className="p-6 border-b border-[#E2E2E2] flex justify-between items-center w-full hover:bg-gray-50 transition-all rounded-t-2xl">
                                <div className="flex gap-4 items-center">
                                    <div className="w-10 h-10 bg-[#E3EAF7] rounded-xl flex items-center justify-center"><Wallet className="text-[#2E5AA7]" size={20} /></div>
                                    <div className="text-left"><h3 className="font-semibold text-lg">Batas Budget Harian</h3><p className="text-[#595D62]">Atur alokasi budget harian Anda</p></div>
                                </div>
                                <ChevronRight className={`transition-transform ${isBudgetOpen ? 'rotate-90' : ''}`} size={24} />
                            </button>
                            
                            {isBudgetOpen && (
                                <form onSubmit={submit} className="animate-in fade-in duration-200">
                                    <div className="p-6 grid grid-cols-2 gap-8 border-b border-[#E2E2E2]">
                                        <div><p className="text-[10px] font-bold text-[#595D62] uppercase tracking-widest">MINIMAL</p><p className="text-base font-semibold text-[#2E5AA7]">Rp {Number(data.min_allocation).toLocaleString('id-ID')}</p></div>
                                        <div className="border-l border-[#E2E2E2] pl-8"><p className="text-[10px] font-bold text-[#595D62] uppercase tracking-widest">MAKSIMAL</p><p className="text-base font-semibold text-[#2E5AA7]">Rp {Number(data.max_allocation).toLocaleString('id-ID')}</p></div>
                                    </div>
                                    <div className="p-6 grid grid-cols-2 gap-6">
                                        <div><label className="text-[10px] font-bold text-[#595D62] uppercase tracking-widest">Minimal Alokasi</label><input type="number" value={data.min_allocation} onChange={(e) => setData('min_allocation', e.target.value)} className="w-full mt-2 p-3 border border-[#E2E2E2] rounded-xl" /></div>
                                        <div><label className="text-[10px] font-bold text-[#595D62] uppercase tracking-widest">Maksimal Alokasi</label><input type="number" value={data.max_allocation} onChange={(e) => setData('max_allocation', e.target.value)} className="w-full mt-2 p-3 border border-[#E2E2E2] rounded-xl" /></div>
                                    </div>
                                    <div className="p-6 flex justify-end gap-3">
                                        <button type="button" onClick={() => setIsBudgetOpen(false)} className="px-8 py-3 border border-[#E2E2E2] rounded-xl">Batal</button>
                                        <button type="submit" disabled={processing} className="px-8 py-3 bg-[#2E5AA7] text-white rounded-xl disabled:opacity-50">{processing ? 'Menyimpan...' : 'Simpan Perubahan'}</button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <h4 className="text-sm font-bold text-[#2E5AA7] tracking-widest">AKUN</h4>
                        <div className="bg-white border border-[#E2E2E2] rounded-2xl divide-y divide-[#E2E2E2] overflow-hidden">
                            
                             {/* Menu Ganti Password */}
                             <button 
                                onClick={handleResetPassword} 
                                disabled={passwordForm.processing} 
                                className="flex justify-between items-center p-6 w-full hover:bg-gray-50 transition-colors disabled:opacity-50"
                             >
                                <div className="flex gap-4">
                                    <div className="w-10 h-10 bg-[#E3EAF7] rounded-xl flex items-center justify-center"><Lock size={20} className="text-[#2E5AA7]" /></div>
                                    <div className="text-left">
                                        <h3 className="font-semibold text-lg text-[#101010]">Ganti Password</h3>
                                        <p className="text-[#595D62]">Perbarui keamanan akunmu</p>
                                    </div>
                                </div>
                                <ChevronRight className="text-[#595D62]" size={20} />
                             </button>

                             {/* Menu Verifikasi Email */}
                             <button 
                                onClick={handleSendVerification}
                                disabled={verificationForm.processing}
                                className="flex justify-between items-center p-6 w-full hover:bg-gray-50 transition-colors disabled:opacity-50"
                             >
                                <div className="flex gap-4">
                                    <div className="w-10 h-10 bg-[#E3EAF7] rounded-xl flex items-center justify-center"><Mail size={20} className="text-[#2E5AA7]" /></div>
                                    <div className="text-left">
                                        <h3 className="font-semibold text-lg text-[#101010]">Verifikasi Email</h3>
                                        <p className="text-[#595D62]">Tingkatkan keamanan akun dengan verifikasi email</p>
                                    </div>
                                </div>
                                <ChevronRight className="text-[#595D62]" size={20} />
                             </button>

                             {/* Menu Feedback */}
                             <Link 
                                href="/feedback" 
                                className="flex justify-between items-center p-6 w-full hover:bg-gray-50 transition-colors"
                             >
                                <div className="flex gap-4">
                                    <div className="w-10 h-10 bg-[#E3EAF7] rounded-xl flex items-center justify-center"><MessageSquare size={20} className="text-[#2E5AA7]" /></div>
                                    <div className="text-left">
                                        <h3 className="font-semibold text-lg text-[#101010]">Kirim Masukan</h3>
                                        <p className="text-[#595D62]">Bantu kami meningkatkan kualitas layanan aplikasi</p>
                                    </div>
                                </div>
                                <ChevronRight className="text-[#595D62]" size={20} />
                             </Link>

                             {/* Menu Hapus Akun */}
                             <button onClick={() => setIsDeleteModalOpen(true)} className="flex justify-between items-center p-6 w-full hover:bg-red-50 transition-colors">
                                <div className="flex gap-4">
                                    <div className="w-10 h-10 bg-[#FFEAEC] rounded-xl flex items-center justify-center"><Trash2 size={20} className="text-[#FF4E64]" /></div>
                                    <div className="text-left"><h3 className="font-semibold text-lg text-[#101010]">Hapus Akun</h3><p className="text-[#595D62]">Hapus akun ini secara permanen</p></div>
                                </div>
                                <ChevronRight className="text-[#595D62]" size={20} />
                             </button>

                        </div>
                    </div>
                </div>
            </div>

            {/* Verifikasi Email (Baru Sesuai Gambar) */}
            {isVerifyModalOpen && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-8 rounded-2xl max-w-md w-full shadow-2xl text-center animate-in fade-in zoom-in-95 duration-200">
                        <h2 className="text-2xl font-bold mb-4 text-[#101010]">Verifikasi Email Terkirim</h2>
                        <p className="text-[#595D62] mb-8 text-base">Email verifikasi telah dikirim. Silakan cek inbox atau folder spam Anda.</p>
                        <div className="flex gap-4 justify-center">
                            <button type="button" onClick={() => setIsVerifyModalOpen(false)} className="flex-1 py-3 border border-[#E2E2E2] rounded-xl font-medium text-[#595D62] hover:bg-gray-50 transition">
                                Tutup
                            </button>
                            <button type="button" onClick={() => setIsVerifyModalOpen(false)} className="flex-1 py-3 bg-[#2E5AA7] text-white rounded-xl font-medium hover:bg-[#254a8a] transition">
                                Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/*  Ganti Password */}
            {isPasswordModalOpen && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-8 rounded-2xl max-w-md w-full shadow-2xl text-center">
                        <h2 className="text-2xl font-bold mb-4">Reset Password Terkirim</h2>
                        <p className="text-[#595D62] mb-8">Email reset password telah dikirim. Silakan cek inbox atau folder spam Anda.</p>
                        <div className="flex gap-4 justify-center">
                            <button type="button" onClick={() => setIsPasswordModalOpen(false)} className="flex-1 py-3 border border-[#E2E2E2] rounded-xl font-medium text-[#595D62] hover:bg-gray-50 transition">
                                Tutup
                            </button>
                            <button type="button" onClick={() => setIsPasswordModalOpen(false)} className="flex-1 py-3 bg-[#2E5AA7] text-white rounded-xl font-medium hover:bg-[#254a8a] transition">
                                Mengerti
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/*  Hapus Akun */}
            {isDeleteModalOpen && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-8 rounded-2xl max-w-lg w-full shadow-2xl">
                        <h2 className="text-2xl font-bold mb-4">Hapus Akun</h2>
                        <p className="text-[#595D62] mb-6">Apakah Anda yakin ingin menghapus akun Anda? Tindakan ini tidak dapat dibatalkan.</p>
                        <form onSubmit={submitDelete}>
                            <input type="password" placeholder="Password" className="w-full p-3 border rounded-xl mb-6" onChange={(e) => deleteForm.setData('password', e.target.value)} />
                            <div className="flex gap-4">
                                <button type="button" onClick={() => setIsDeleteModalOpen(false)} className="flex-1 py-3 border rounded-xl">Batal</button>
                                <button type="submit" className="flex-1 py-3 bg-[#FF4E64] text-white rounded-xl">Hapus Akun</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}