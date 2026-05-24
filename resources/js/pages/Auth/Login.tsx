import { Head, useForm, Link } from '@inertiajs/react';
import { Mail, Lock, Eye, EyeOff } from 'lucide-react';
import React, { useState } from 'react';
import AuthCard from '@/components/AuthCard';
import Navbar from '@/components/Navbar';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e: React.SubmitEvent) => {
        e.preventDefault();
        post('/auth/login');
    };

    return (
        <>
            <Head title="Login" />
            <Navbar />

            <AuthCard>
                {/* Header */}
                <div className="mb-6 flex flex-col items-center text-center">
                    <img src="/logo.png" className="mb-4 h-10" />

                    <h2 className="text-xl font-semibold text-primary">
                        Halo lagi, Sahabat Moco!
                    </h2>

                    <p className="mt-2 text-sm text-gray-500">
                        Seneng deh liat kamu lagi. Yuk, masuk dulu buat lanjutin
                        pantau cuan kamu!
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="space-y-4">
                    {/* Email */}
                    <div>
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
                                className={`input-field pl-10 ${
                                    errors.email ? 'border-red-500' : ''
                                }`}
                            />
                        </div>

                        {errors.email && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    {/* Password */}
                    <div>
                        <div className="relative">
                            <Lock
                                className="absolute top-3.5 left-3 text-gray-400"
                                size={18}
                            />

                            <input
                                type={showPassword ? 'text' : 'password'}
                                placeholder="Password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                className={`input-field pr-10 pl-10 ${
                                    errors.password ? 'border-red-500' : ''
                                }`}
                            />

                            {/* TOGGLE */}
                            <button
                                type="button"
                                onClick={() => setShowPassword((prev) => !prev)}
                                className="absolute top-3.5 right-3 text-gray-400"
                            >
                                {showPassword ? (
                                    <Eye size={18} />
                                ) : (
                                    <EyeOff size={18} />
                                )}
                            </button>
                        </div>

                        {errors.password && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    {/* Forgot */}
                    <div className="text-right text-sm">
                        <Link href="/auth/forget-password">
                            <span className="cursor-pointer text-primary hover:underline">
                                Lupa Password
                            </span>
                        </Link>
                    </div>

                    {/* Submit */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn w-full border-primary bg-primary text-white hover:bg-primary-medium"
                    >
                        Masuk
                    </button>
                    <a
                        href="/auth/oauth/google/redirect"
                        className="btn w-full bg-white text-black shadow-sm hover:shadow-md flex items-center gap-3"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            className="h-5 w-5"
                        >
                            <path
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                fill="#4285F4"
                            />
                            <path
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853"
                            />
                            <path
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                fill="#FBBC05"
                            />
                            <path
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335"
                            />
                        </svg>
                        Lanjut dengan Google
                    </a>

                    {/* Register */}
                    <p className="text-center text-sm text-gray-500">
                        Belum punya akun?{' '}
                        <Link
                            href="/auth/register"
                            className="font-medium text-secondary"
                        >
                            Daftar di sini
                        </Link>
                    </p>
                </form>
            </AuthCard>
        </>
    );
}
