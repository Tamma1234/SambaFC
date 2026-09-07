import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardCheck,
    Home,
    LayoutGrid,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as attendancesIndex } from '@/routes/club/attendances';
import { index as fundsIndex } from '@/routes/club/funds';
import { index as matchesIndex } from '@/routes/club/matches';
import { index as membersIndex } from '@/routes/club/members';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const dashboardUrl = slug ? dashboard(slug) : '/';

    const mainNavItems: NavItem[] = slug
        ? [
              {
                  title: 'Bảng điều khiển',
                  href: dashboardUrl,
                  icon: LayoutGrid,
              },
              {
                  title: 'Thành viên',
                  href: membersIndex(slug),
                  icon: Users,
              },
              {
                  title: 'Trận đấu',
                  href: matchesIndex(slug),
                  icon: CalendarDays,
              },
              {
                  title: 'Điểm danh',
                  href: attendancesIndex(slug),
                  icon: ClipboardCheck,
              },
              {
                  title: 'Quỹ đội',
                  href: fundsIndex(slug),
                  icon: Wallet,
              },
          ]
        : [
              {
                  title: 'Bảng điều khiển',
                  href: dashboardUrl,
                  icon: LayoutGrid,
              },
          ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Trang chủ CLB',
            href: home(),
            icon: Home,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Quản lý CLB" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
