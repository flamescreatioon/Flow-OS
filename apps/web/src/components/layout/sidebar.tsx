'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  BarChart3,
  Boxes,
  Building2,
  LayoutDashboard,
  LogOut,
  Package,
  PanelLeftClose,
  PanelLeftOpen,
  ReceiptText,
  Settings,
  ShoppingCart,
  Wallet,
  Waves,
  X,
  type LucideIcon,
} from 'lucide-react';
import { useSession } from '@/features/auth/session-context';
import { useActiveOrganization } from '@/features/organizations/active-organization-context';
import { useSidebarCollapsed } from '@/hooks/use-sidebar-collapsed';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/utils/cn';

interface NavLink {
  label: string;
  href: string;
  icon: LucideIcon;
}

interface UpcomingLink {
  label: string;
  icon: LucideIcon;
}

const workspaceLinks: NavLink[] = [
  { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
  { label: 'Catalogue', href: '/catalogue', icon: Package },
  { label: 'Inventory', href: '/inventory', icon: Boxes },
  { label: 'Organizations', href: '/organizations', icon: Building2 },
];

const upcomingLinks: UpcomingLink[] = [
  { label: 'Sales', icon: ShoppingCart },
  { label: 'Invoicing', icon: ReceiptText },
  { label: 'Expenses', icon: Wallet },
  { label: 'Reporting', icon: BarChart3 },
];

export interface SidebarProps {
  /** Whether the mobile navigation drawer is open. */
  mobileOpen: boolean;
  /** Closes the mobile navigation drawer. */
  onMobileClose: () => void;
}

function BrandMark() {
  return (
    <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary">
      <Waves className="h-5 w-5" aria-hidden="true" />
    </span>
  );
}

interface SidebarLinkProps {
  item: NavLink;
  active: boolean;
  collapsed: boolean;
  onNavigate?: () => void;
}

function SidebarLink({ item, active, collapsed, onNavigate }: SidebarLinkProps) {
  const Icon = item.icon;
  return (
    <Link
      href={item.href}
      onClick={onNavigate}
      aria-current={active ? 'page' : undefined}
      title={collapsed ? item.label : undefined}
      className={cn(
        'group relative flex h-12 items-center rounded-full text-sm font-medium transition-colors',
        collapsed ? 'w-12 justify-center' : 'gap-3 px-4',
        active
          ? 'bg-primary-container text-on-primary-container'
          : 'text-on-surface-variant hover:bg-secondary-container',
      )}
    >
      <Icon className="h-5 w-5 shrink-0" aria-hidden="true" />
      {!collapsed && <span className="truncate">{item.label}</span>}
      {collapsed && (
        <span
          aria-hidden="true"
          className="pointer-events-none absolute left-full z-50 ml-3 whitespace-nowrap rounded-lg bg-on-surface px-2.5 py-1.5 text-sm font-medium text-surface opacity-0 transition-opacity group-hover:opacity-100"
        >
          {item.label}
        </span>
      )}
    </Link>
  );
}

interface UpcomingItemProps {
  item: UpcomingLink;
  collapsed: boolean;
}

function UpcomingItem({ item, collapsed }: UpcomingItemProps) {
  const Icon = item.icon;
  return (
    <span
      aria-disabled="true"
      title={collapsed ? `${item.label} (coming soon)` : undefined}
      className={cn(
        'group relative flex h-12 items-center text-sm font-medium text-on-surface-variant/60',
        collapsed ? 'w-12 justify-center' : 'gap-3 px-4',
      )}
    >
      <Icon className="h-5 w-5 shrink-0" aria-hidden="true" />
      {!collapsed && <span className="truncate">{item.label}</span>}
      {!collapsed && <Badge className="ml-auto">Soon</Badge>}
      {collapsed && (
        <span
          aria-hidden="true"
          className="pointer-events-none absolute left-full z-50 ml-3 whitespace-nowrap rounded-lg bg-on-surface px-2.5 py-1.5 text-sm font-medium text-surface opacity-0 transition-opacity group-hover:opacity-100"
        >
          {item.label} (coming soon)
        </span>
      )}
    </span>
  );
}

interface SidebarContentProps {
  collapsed: boolean;
  onNavigate?: () => void;
}

function SidebarContent({ collapsed, onNavigate }: SidebarContentProps) {
  const pathname = usePathname();
  const { user, signOut } = useSession();
  const { activeOrganization } = useActiveOrganization();

  return (
    <>
      <nav aria-label="Main navigation" className="flex-1 space-y-6 px-4 pt-2">
        <div className="space-y-1">
          {workspaceLinks.map((item) => (
            <SidebarLink
              key={item.href}
              item={item}
              active={pathname === item.href}
              collapsed={collapsed}
              onNavigate={onNavigate}
            />
          ))}
        </div>
        <div className="space-y-1">
          {!collapsed && (
            <p className="px-4 pb-1 text-sm font-medium text-on-surface-variant">
              Coming soon
            </p>
          )}
          {upcomingLinks.map((item) => (
            <UpcomingItem key={item.label} item={item} collapsed={collapsed} />
          ))}
        </div>
      </nav>
      <div className="space-y-3 px-4 pb-4 pt-2">
        <div className="space-y-1">
          <UpcomingItem item={{ label: 'Settings', icon: Settings }} collapsed={collapsed} />
        </div>
        {collapsed ? (
          <div className="flex flex-col items-center gap-2 pb-1">
            <span title={user?.name ?? ''}>
              <Avatar name={user?.name ?? ''} />
            </span>
            <button
              type="button"
              onClick={() => void signOut()}
              aria-label="Sign out"
              title="Sign out"
              className="inline-flex h-9 w-9 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-secondary-container"
            >
              <LogOut className="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        ) : (
          <div className="flex items-center gap-3 rounded-2xl bg-secondary-container p-3">
            <Avatar name={user?.name ?? ''} className="h-9 w-9" />
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-on-surface">
                {user?.name ?? '—'}
              </p>
              <p className="truncate text-xs text-on-surface-variant">
                {activeOrganization?.name ?? 'No organization'}
              </p>
            </div>
            <button
              type="button"
              onClick={() => void signOut()}
              aria-label="Sign out"
              title="Sign out"
              className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-surface-container-high"
            >
              <LogOut className="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        )}
      </div>
    </>
  );
}

export function Sidebar({ mobileOpen, onMobileClose }: SidebarProps) {
  const { collapsed, toggleCollapsed } = useSidebarCollapsed();

  useEffect(() => {
    if (!mobileOpen) return;
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onMobileClose();
    };
    window.addEventListener('keydown', onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      window.removeEventListener('keydown', onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [mobileOpen, onMobileClose]);

  return (
    <>
      <aside
        data-collapsed={collapsed || undefined}
        className={cn(
          'sticky top-0 hidden h-dvh shrink-0 flex-col bg-surface transition-[width] duration-200 ease-out md:flex',
          collapsed ? 'w-20' : 'w-[264px]',
        )}
      >
        {collapsed ? (
          <div className="flex flex-col items-center gap-1 pb-1 pt-3">
            <button
              type="button"
              onClick={toggleCollapsed}
              aria-label="Expand sidebar"
              className="inline-flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-secondary-container"
            >
              <PanelLeftOpen className="h-5 w-5" aria-hidden="true" />
            </button>
            <BrandMark />
          </div>
        ) : (
          <div className="flex h-16 items-center gap-3 px-5">
            <BrandMark />
            <span className="text-lg font-semibold text-on-surface">flowOS</span>
            <button
              type="button"
              onClick={toggleCollapsed}
              aria-label="Collapse sidebar"
              className="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-secondary-container"
            >
              <PanelLeftClose className="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        )}
        <SidebarContent collapsed={collapsed} />
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 md:hidden">
          <div
            aria-hidden="true"
            onClick={onMobileClose}
            className="scrim-in absolute inset-0 bg-scrim/50"
          />
          <div
            role="dialog"
            aria-modal="true"
            aria-label="Navigation"
            className="drawer-in absolute inset-y-0 left-0 flex w-[280px] flex-col bg-surface"
          >
            <div className="flex h-16 items-center gap-3 px-5">
              <BrandMark />
              <span className="text-lg font-semibold text-on-surface">flowOS</span>
              <button
                type="button"
                onClick={onMobileClose}
                aria-label="Close navigation"
                className="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-secondary-container"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
            <SidebarContent collapsed={false} onNavigate={onMobileClose} />
          </div>
        </div>
      )}
    </>
  );
}
