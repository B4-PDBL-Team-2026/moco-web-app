import AppLayout from '@/layouts/AppLayout';

interface Props {
    status?: 'stabil' | 'defisit' | 'kritis' | 'surplus';
}

export default function Dashboard({ status }: Props ) {
    return (
        <AppLayout status={status}>
            <h1>This should be dashboard of user</h1>
        </AppLayout>
    )
}
