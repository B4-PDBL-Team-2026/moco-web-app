import { Head, useForm, Link } from '@inertiajs/react';
import { Mail, Lock, User, EyeOff, Eye } from 'lucide-react';
import React, { useState } from 'react';
import AuthCard from '@/components/AuthCard';
import Navbar from '@/components/Navbar';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e: React.SubmitEvent) => {
        e.preventDefault();
        post('/auth/register');
    };

    return (
        <>
            <Head title="Register" />
            <Navbar />

            <AuthCard>
                {/* Header */}
                <div className="mb-6 flex flex-col items-center text-center">
                    <img src="/logo.png" className="mb-4 h-10" />

                    <h2 className="text-xl font-semibold text-primary">
                        Buat Akun Baru
                    </h2>

                    <p className="mt-2 text-sm text-gray-500">
                        Mulai perjalanan finansial sehatmu bareng Moco. Daftar
                        sekarang, gratis kok!
                    </p>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="space-y-4">
                    {/* Name */}
                    <div>
                        <div className="relative">
                            <User
                                className="absolute top-3.5 left-3 text-gray-400"
                                size={18}
                            />
                            <input
                                type="text"
                                placeholder="Nama Lengkap"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                className={`input-field pl-10 ${errors.name ? 'border-red-500' : ''}`}
                            />
                        </div>
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.name}
                            </p>
                        )}
                    </div>

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
                                className={`input-field pl-10 ${errors.email ? 'border-red-500' : ''}`}
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

                    {/* Confirm Password */}
                    <div>
                        <div className="relative">
                            <Lock
                                className="absolute top-3.5 left-3 text-gray-400"
                                size={18}
                            />

                            <input
                                type={showPassword ? 'text' : 'password'}
                                placeholder="Password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
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

                    {/* Submit */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn w-full border-primary bg-primary text-white hover:bg-primary-medium"
                    >
                        Daftar
                    </button>

                    {/* Login */}
                    <p className="text-center text-sm text-gray-500">
                        Sudah punya akun?{' '}
                        <Link
                            href="/auth/login"
                            className="font-medium text-secondary"
                        >
                            Masuk di sini
                        </Link>
                    </p>
                </form>
            </AuthCard>
        </>
    );
}
