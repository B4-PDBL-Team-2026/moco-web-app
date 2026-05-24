import { Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function Navbar() {
    const { auth } = usePage().props as any;

    return (
        <div className="flex w-full items-center justify-between border-b border-gray-200 bg-white px-10 py-5">
            {/* Logo */}
            <div className="flex items-center gap-2">
                <img src="/logo.png" alt="moco" className="h-12" />
                <div className="flex flex-col justify-start text-primary">
                    <h1 className="text-3xl font-black">MOCO</h1>
                    <p className="text-sm font-bold">Money Control</p>
                </div>
            </div>

            {/* Actions */}
            <div className="hidden items-center gap-4 md:flex">
                {!auth.user ? (
                    <>
                        <Link
                            href="/auth/login"
                            className="btn border-primary text-primary hover:bg-primary-light"
                        >
                            Login
                        </Link>

                        <Link
                            href="/auth/register"
                            className="btn border-secondary bg-secondary text-white hover:bg-secondary-medium"
                        >
                            Register
                        </Link>
                    </>
                ) : (
                    <>
                        {/*<Link*/}
                        {/*    href="/dashboard"*/}
                        {/*    className="btn border-primary text-primary hover:bg-primary-light"*/}
                        {/*>*/}
                        {/*    Dashboard*/}
                        {/*</Link>*/}

                        {/*<Link*/}
                        {/*    href="/logout"*/}
                        {/*    method="post"*/}
                        {/*    as="button"*/}
                        {/*    className="btn border-secondary bg-secondary text-white hover:bg-secondary-medium"*/}
                        {/*>*/}
                        {/*    Logout*/}
                        {/*</Link>*/}
                    </>
                )}
            </div>
        </div>
    );
}
