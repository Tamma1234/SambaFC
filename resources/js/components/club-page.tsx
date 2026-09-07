import type { ReactNode } from 'react';

export default function ClubPage({ children }: { children: ReactNode }) {
    return (
        <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
            {children}
        </div>
    );
}
