import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name, club } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md">
                <img
                    src={club?.logo ?? '/images/logo.png'}
                    alt={club?.name ?? name}
                    className="size-8 object-cover"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {club?.name ?? name}
                </span>
            </div>
        </>
    );
}
