import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { club } = usePage().props;
    const logo = club?.logo ?? '/images/logo.png';
    const clubName = club?.name ?? 'Samba FC';

    return (
        <div className="dark relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-zinc-950 p-6 md:p-10">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(34,197,94,0.18),_transparent_55%)]" />
            <div className="relative w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-3 font-medium"
                        >
                            <img
                                src={logo}
                                alt={clubName}
                                className="size-24 rounded-full object-cover shadow-lg ring-2 shadow-green-900/40 ring-green-500/40"
                            />
                            <span className="text-lg font-semibold tracking-wide text-white">
                                {clubName}
                            </span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium text-white">
                                {title}
                            </h1>
                            <p className="text-center text-sm text-zinc-400">
                                {description}
                            </p>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-zinc-900/80 p-6 shadow-xl backdrop-blur">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
