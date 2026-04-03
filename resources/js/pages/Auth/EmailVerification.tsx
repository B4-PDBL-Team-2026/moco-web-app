import { Head} from '@inertiajs/react';

interface Props {
    status: 'success' | 'error';
    message: string;
}

export default function EmailVerification({ status, message }: Props) {
    const isSuccess = status === 'success';
    const isAlreadyVerified = message === 'Email is already verified.';
    const isResendConfirmation = message === 'Email verification link sent on email.';

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <Head title="Email Verification" />

            <div className="flex items-center gap-5">
                <svg
                    width="151"
                    height="119"
                    viewBox="0 0 151 119"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <g filter="url(#filter0_dd_230_264)">
                        <path
                            d="M134.274 43.6876C132.363 42.054 131.295 41.0578 127.123 40.9843C125.038 40.9475 121.363 41.7982 117.986 45.0095C116.903 46.0395 77.4658 80.7792 73.9898 80.7792C66.5412 81.6026 63.1645 73.0947 59.2912 67.6057C59.2912 67.6057 55.7159 62.9401 51.4453 62.6657C48.4659 62.6657 44.1231 66.7842 43.8973 66.9654C42.9604 67.6591 25.1036 82.6054 23.637 83.7067L23.4095 83.8776C14.3446 90.6835 5.13102 97.6011 13.0102 107.218C19.3664 112.066 32.9726 111.517 32.9726 111.517C38.6336 111.517 40.1469 110.83 42.0103 110.054C49.2603 107.035 50.6507 81.3281 53.1336 81.3281C57.4041 83.7067 60.0235 98.2181 70.911 98.5269C79.092 97.7607 91.4244 86.6744 92.5619 85.6278C92.5495 85.7481 87.8068 106.918 87.7948 107.035C87.7334 108.31 87.0995 109.779 89.0858 111.243C91.966 112.798 112.247 112.321 119.873 109.596C125.506 107.584 136.757 91.6657 129.606 91.6657C123.033 91.6657 112.96 92.8549 108.452 89.1042C101.085 82.9751 104.082 73.4606 108.75 69.7098C111.897 67.1808 114.709 66.325 119.575 65.7761C120.28 65.7728 132.015 65.8698 132.586 65.7761C133.622 65.2272 135.631 57.6377 135.82 51.765C135.913 48.8809 136.523 45.918 134.274 43.6876Z"
                            fill="#2E5AA7"
                        />
                        <path
                            d="M134.274 43.6876C132.363 42.054 131.295 41.0578 127.123 40.9843C125.038 40.9475 121.363 41.7982 117.987 45.0095C116.903 46.0395 77.4658 80.7792 73.9898 80.7792C66.5412 81.6026 63.1645 73.0947 59.2912 67.6057C59.2912 67.6057 55.7159 62.9401 51.4454 62.6657C48.4659 62.6657 44.1232 66.7842 43.8973 66.9654C42.9604 67.6591 28.1414 84.301 26.9144 85.6278C20.0617 93.0378 12.4143 98.5269 20.0617 106.394C26.4179 111.243 32.9727 111.517 32.9727 111.517C38.6336 111.517 40.1469 110.83 42.0104 110.054C49.2603 107.035 50.6507 81.3281 53.1336 81.3281C57.4041 83.7067 60.0235 98.2181 70.911 98.5269C79.092 97.7607 91.4244 86.6744 92.5619 85.6278C92.5495 85.7481 87.8068 106.918 87.7948 107.035C87.7334 108.31 87.0996 109.779 89.0859 111.243C91.966 112.798 112.247 112.321 119.873 109.596C125.506 107.584 136.757 91.6657 129.606 91.6657C123.033 91.6657 112.96 92.8549 108.452 89.1042C101.085 82.9751 104.082 73.4606 108.75 69.7098C111.897 67.1808 114.709 66.325 119.575 65.7761C120.28 65.7728 132.015 65.8698 132.586 65.7761C133.622 65.2272 135.631 57.6377 135.82 51.765C135.913 48.8809 136.523 45.918 134.274 43.6876Z"
                            fill="#2E5AA7"
                        />
                        <path
                            d="M143.51 6.0378C143.908 3.3848 143.312 3.84222 143.014 3.65925C141.774 2.89807 109.247 10.8864 108.849 12.6246C108.452 14.3627 115.441 18.4451 115.503 18.9369C115.323 19.5039 84.7157 42.8027 84.0205 42.3564C80.7431 40.2523 79.9486 37.5993 75.2808 32.2933C68.8253 23.4195 59.1917 23.4195 50.8493 24.3343C40.7192 26.9873 34.4044 34.6116 31.4829 43.2733C31.1143 44.3661 19.0685 77.3028 20.5582 79.4069C21.8493 80.2302 28.3048 74.2839 28.9007 73.735C32.476 70.4416 46.2808 58.2744 46.678 58C49.8884 55.5127 51.761 54.1583 55.9143 54.4321C58.2557 54.7804 61.4915 56.2151 62.8664 57.9085C63.8649 58.8914 68.726 66.6908 73.5924 67.5142C74.7842 67.7886 76.6712 68.063 79.5513 67.1482C84.815 65.9589 121.239 32.7507 122.356 31.8359C122.946 31.3152 126.561 27.7382 127.421 27.7192C127.897 28.0484 133.085 32.647 133.38 32.9337C134.907 34.0317 135.863 33.8485 136.161 33.6655C137.045 33.1226 142.318 10.7949 143.51 6.0378Z"
                            fill="#FFA62B"
                        />
                        <path
                            fillRule="evenodd"
                            clipRule="evenodd"
                            d="M138.941 68.2457C140.275 68.2534 145.595 67.9715 145.993 68.7945C147.979 70.8069 144.107 87.0865 143.411 87.8229C142.716 88.5547 141.722 89.5615 138.941 89.3785C138.755 89.3667 133.955 89.3726 131.195 89.3785H116.596C114.51 89.1955 110.24 88.0977 108.353 83.7066C103.817 73.153 114.814 69.1038 115.888 68.7086L115.9 68.7037C116.894 68.3378 117.887 68.3372 119.773 68.2457H138.941ZM119.675 74.8326C116.877 74.8326 114.609 76.9219 114.609 79.4986C114.61 82.0753 116.878 84.1637 119.675 84.1637C122.472 84.1636 124.739 82.0752 124.739 79.4986C124.739 76.9219 122.472 74.8327 119.675 74.8326Z"
                            fill="#FFA62B"
                        />
                    </g>
                </svg>

                <span className="flex flex-col text-primary">
                    <h1 className="text-6xl font-bold">moco</h1>
                    <p className="text-xl">Money Control</p>
                </span>
            </div>

            {/* Card */}
            <div className="mt-6 w-full overflow-hidden rounded-2xl border border-gray-100 bg-white p-8 shadow-md sm:max-w-lg">

                {/* Icon */}
                <div className="mb-6 flex justify-center">
                    {isSuccess ? (
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                            <svg
                                className="h-8 w-8 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                    ) : (
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                            <svg
                                className="h-8 w-8 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                    )}
                </div>

                {/* Heading */}
                <h2 className="mb-4 text-center text-3xl font-semibold text-gray-800">
                    {isSuccess
                        ? isAlreadyVerified
                            ? 'Udah Terverifikasi!'
                            : isResendConfirmation
                                ? 'Email Terkirim!'
                                : 'Email Terverifikasi!'
                        : 'Link Tidak Valid'}
                </h2>

                {/* Body message */}
                <p className="mb-6 w-full text-center text-gray-600">
                    {isSuccess
                        ? isAlreadyVerified
                            ? 'Email kamu udah terverifikasi sebelumnya. Langsung aja masuk ke aplikasi, ya!'
                            : isResendConfirmation
                                ? 'Link verifikasi udah dikirim ke emailmu. Cek inbox atau folder spam kamu ya, terus klik link-nya buat verifikasi.'
                                : 'Yeay! Email kamu berhasil diverifikasi. Sekarang kamu bisa mulai pake moco dengan bebas!'
                        : 'Link verifikasi yang kamu pake udah nggak berlaku atau salah. Minta link baru lewat aplikasi ya.'}
                </p>

                {/* Status banner */}
                <div
                    className={`mb-6 rounded-lg p-4 text-center text-sm ${
                        isSuccess
                            ? 'bg-green-100 text-green-700'
                            : 'bg-red-100 text-red-700'
                    }`}
                >
                    {message}
                </div>

                {/* CTA — only shown on success (non-resend), directs user back to app */}
                {/*{isSuccess && !isResendConfirmation && (*/}
                {/*    <div className="flex items-center justify-center">*/}
                {/*        <Link*/}
                {/*            href="/"*/}
                {/*            className="btn w-full bg-primary text-center font-medium text-on-primary"*/}
                {/*        >*/}
                {/*            Ke Beranda*/}
                {/*        </Link>*/}
                {/*    </div>*/}
                {/*)}*/}

                {/* Error state: nudge user to re-request from the app */}
                {!isSuccess && (
                    <p className="mt-2 text-center text-sm text-gray-500">
                        Buka aplikasi moco dan minta ulang link verifikasi dari sana.
                    </p>
                )}
            </div>
        </div>
    );
}
