import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Navbar from '@/components/Navbar';
import OnboardingCard from '@/components/OnboardingCard';

import OnboardingStep1 from './OnboardingStep1';
import OnboardingStep2 from './OnboardingStep2';
import OnboardingStep3 from './OnboardingStep3';
import OnboardingStep4 from './OnboardingStep4';

export default function Onboarding({
    categories = [],
    preview: initialPreview,
}: {
    categories: { id: number; name: string; icon: string; type: string }[];
    preview?: any;
}) {
    const [step, setStep] = useState(1);
    const [preview, setPreview] = useState<any>(initialPreview ?? null);
    const [globalError, setGlobalError] = useState<string | null>(null);

    const form = useForm({
        budgetCycle: 'monthly',
        initialBalance: '',
        flooringLimit: '',
        ceilingLimit: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        fixedCosts: [] as any[],
    });

    const next = () => setStep((s) => Math.min(s + 1, 4));
    const prev = () => setStep((s) => Math.max(s - 1, 1));

    /**
     * STEP 3 → STEP 4 (PREVIEW)
     * On validation error, jump back to the step that owns the failing field.
     */
    const previewSubmit = () => {
        setGlobalError(null);

        form.transform((data) => ({
            ...data,
            initialBalance: Number(data.initialBalance),
            flooringLimit: Number(data.flooringLimit),
            ceilingLimit: Number(data.ceilingLimit),
            fixedCosts: data.fixedCosts.map((c: any) => ({
                ...c,
                amount: Number(c.amount),
                dueDay: Number(c.dueDay),
            })),
        }));

        form.post('/onboarding/preview', {
            preserveScroll: true,
            onSuccess: (page) => {
                setPreview((page.props as any).preview);
                setStep(4);
            },
            onError: (errors) => {
                setGlobalError(
                    'Data onboardingnya ada yang ngga valid nih, coba benerin dulu ya',
                );

                // Jump to the earliest step that has an error
                const keys = Object.keys(errors);
                const hasStep1 = keys.some((k) => k === 'initialBalance');
                const hasStep2 = keys.some(
                    (k) => k === 'flooringLimit' || k === 'ceilingLimit',
                );
                const hasStep3 = keys.some(
                    (k) => k === 'budgetCycle' || k.startsWith('fixedCosts'),
                );

                if (hasStep1) setStep(1);
                else if (hasStep2) setStep(2);
                else if (hasStep3) setStep(3);
            },
        });
    };

    /**
     * FINAL SUBMIT
     */
    const submit = () => {
        form.transform((data) => ({
            ...data,
            initialBalance: Number(data.initialBalance),
            flooringLimit: Number(data.flooringLimit),
            ceilingLimit: Number(data.ceilingLimit),
            fixedCosts: data.fixedCosts.map((c: any) => ({
                ...c,
                amount: Number(c.amount),
                dueDay: Number(c.dueDay),
            })),
        }));

        form.post('/onboarding', {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            onError: (_errors: Record<string, string>) => {
                setGlobalError(
                    'Data onboardingnya ada yang ngga valid nih, coba benerin dulu ya',
                );
                setStep(3);
            },
        });
    };

    return (
        <>
            <Head title="Onboarding" />
            <Navbar />

            <OnboardingCard>
                {globalError && (
                    <div className="mb-4 rounded-xl bg-red-100 p-3 text-sm text-red-600">
                        {globalError}
                    </div>
                )}

                {step === 1 && (
                    <OnboardingStep1 form={form} next={next} prev={prev} />
                )}

                {step === 2 && (
                    <OnboardingStep2 form={form} next={next} prev={prev} />
                )}

                {step === 3 && (
                    <OnboardingStep3
                        form={form}
                        prev={prev}
                        submit={previewSubmit}
                        categories={categories}
                    />
                )}

                {step === 4 && (
                    <OnboardingStep4
                        preview={preview}
                        prev={prev}
                        submit={submit}
                    />
                )}
            </OnboardingCard>
        </>
    );
}
