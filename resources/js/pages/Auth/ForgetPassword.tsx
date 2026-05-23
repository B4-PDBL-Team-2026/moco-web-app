import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import AuthCard from '@/components/AuthCard';
import Navbar from '@/components/Navbar';

export default function ForgetPassword() {
    const { flash } = usePage().props as any;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const [sent, setSent] = useState(false);
    const [counter, setCounter] = useState(0);

    const submit = (e: React.SubmitEvent) => {
        e.preventDefault();

        post('/auth/forget-password', {
            onSuccess: () => {
                setSent(true);
                setCounter(60);
            },
        });
    };

    // countdown logic
    useEffect(() => {
        if (counter <= 0) return;

        const timer = setInterval(() => {
            setCounter((prev) => prev - 1);
        }, 1000);

        return () => clearInterval(timer);
    }, [counter]);

    const resend = () => {
        if (counter > 0) return;

        post('/auth/forget-password', {
            onSuccess: () => {
                setCounter(60);
            },
        });
    };

    return (
        <>
            <Head title="Forget Password" />
            <Navbar />

            <AuthCard>
                {/* HEADER */}
                <div className="mb-6 flex flex-col items-center text-center">
                    <img src="/logo.png" className="mb-4 h-10" alt="logo" />

                    <h2 className="text-xl font-semibold text-primary">
                        Lupa Password
                    </h2>

                    {!sent ? (
                        <p className="mt-2 text-sm text-gray-500">
                            Lupa password? Santai, masukin email kamu di bawah
                            biar kita kirimin link gantinya.
                        </p>
                    ) : (
                        <p className="mt-2 text-sm text-gray-500">
                            Sip, link reset udah meluncur ke emailmu! Coba cek
                            inbox ya. Kalau nggak ada, coba intip folder spam.
                        </p>
                    )}
                </div>

                {/* BEFORE SEND */}
                {!sent && (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="relative">
                            <Mail
                                className="absolute top-3.5 left-3 text-gray-400"
                                size={18}
                            />
                            <input
                                type="email"
                                placeholder="Email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                className="input-field pl-10"
                            />
                        </div>

                        {errors.email && (
                            <p className="text-sm text-red-500">
                                *{errors.email}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="btn w-full border-primary bg-primary text-white"
                        >
                            Kirim Link Reset
                        </button>
                    </form>
                )}

                {/* AFTER SEND */}
                {sent && (
                    <div className="space-y-4 text-center">
                        <p className="text-sm text-gray-500">
                            Tidak menerima email?
                        </p>

                        <button
                            onClick={resend}
                            disabled={counter > 0}
                            className={`btn w-full ${
                                counter > 0
                                    ? 'border-gray-200 bg-gray-200 text-gray-400'
                                    : 'border-primary bg-primary text-white'
                            }`}
                        >
                            {counter > 0
                                ? `Kirim ulang dalam ${counter}s`
                                : 'Kirim Ulang'}
                        </button>
                    </div>
                )}

                {/* FOOTER */}
                <p className="mt-6 text-center text-sm text-gray-500">
                    Ingat password?{' '}
                    <Link href="/auth/login" className="font-medium text-secondary">
                        Kembali ke login
                    </Link>
                </p>

                {/* SUCCESS FLASH */}
                {flash?.success && (
                    <p className="mt-4 text-center text-sm text-green-600">
                        {flash.success}
                    </p>
                )}
            </AuthCard>
        </>
    );
}
