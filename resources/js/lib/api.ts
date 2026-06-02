const BASE = '/api';

function getCsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));
    if (!match) return '';
    return decodeURIComponent(match.split('=')[1]);
}

async function request<T>(url: string, options?: RequestInit): Promise<T> {
    const res = await fetch(`${BASE}${url}`, {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
            ...options?.headers,
        },
        ...options,
    });

    if (!res.ok) {
        const body = await res.json().catch(() => null);
        throw new ApiError(res.status, body?.message || 'Something went wrong', body?.errors);
    }

    if (res.status === 204) return undefined as T;

    return res.json();
}

export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errors?: Record<string, string[]>,
    ) {
        super(message);
    }
}

export const api = {
    get: <T>(url: string) => request<T>(url),
    post: <T>(url: string, data?: unknown) =>
        request<T>(url, { method: 'POST', body: data ? JSON.stringify(data) : undefined }),
    put: <T>(url: string, data?: unknown) =>
        request<T>(url, { method: 'PUT', body: data ? JSON.stringify(data) : undefined }),
    patch: <T>(url: string, data?: unknown) =>
        request<T>(url, { method: 'PATCH', body: data ? JSON.stringify(data) : undefined }),
    delete: <T>(url: string) => request<T>(url, { method: 'DELETE' }),
};
