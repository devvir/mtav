import { useCurrentPage, useFlashToasts } from '@/components/layout/page';
import { DialogHost } from '@/components/overlays/DialogHost';
import { Logo } from '@/design';
import { route } from '@/lib/route';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

/** Signing in, signing up, invitations: a card in the middle of the page */
export function AuthLayout({ children }: { children: ReactNode }) {
  const { props } = useCurrentPage();
  useFlashToasts();

  return (
    <div className="auth">
      <Link href={route('home')} className="auth-logo">
        <Logo name={props.name} institution={props.institution} size={42} />
      </Link>
      <main className="auth-card ds-surface">{children}</main>
      <DialogHost />
    </div>
  );
}
