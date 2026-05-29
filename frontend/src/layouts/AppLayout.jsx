import { Outlet } from 'react-router-dom';
import { Sidebar } from '../components/layouts/Sidebar';
import { Topbar } from '../components/layouts/Topbar';
import { useUiStore } from '../store/uiStore';
import { cn } from '../utils/cn';

export function AppLayout() {
  const sidebarCollapsed = useUiStore((state) => state.sidebarCollapsed);

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      <Sidebar />
      <div
        className={cn(
          'min-h-screen transition-all duration-300 ease-out',
          sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'
        )}
      >
        <Topbar />
        <main className="p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
