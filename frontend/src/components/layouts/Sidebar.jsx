import { NavLink } from 'react-router-dom';
import { ChevronLeft, Menu, X } from 'lucide-react';
import { navItems, canAccess } from '../../routes/navigation';
import { useAuth } from '../../contexts/AuthContext';
import { useUiStore } from '../../store/uiStore';
import { cn } from '../../utils/cn';
import { Button } from '../ui/Button';

export function Sidebar() {
  const { role } = useAuth();
  const { sidebarOpen, sidebarCollapsed, closeSidebar, toggleCollapse } = useUiStore();
  const items = navItems.filter((item) => canAccess(item, role));

  return (
    <>
      <div className={cn('fixed inset-0 z-30 bg-slate-950/40 backdrop-blur-sm lg:hidden', sidebarOpen ? 'block' : 'hidden')} onClick={closeSidebar} />
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-40 flex flex-col border-r border-slate-200 bg-white transition-all duration-300 dark:border-slate-800 dark:bg-slate-950',
          sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
          sidebarOpen ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:translate-x-0'
        )}
      >
        {/* Header */}
        <div className="flex h-16 items-center justify-between border-b border-slate-200 px-4 dark:border-slate-800">
          <div className="flex items-center gap-3">
            <div className="grid h-10 w-10 place-items-center rounded-lg bg-cyan-700 text-white">
              <Menu className="h-5 w-5" />
            </div>
            {!sidebarCollapsed && (
              <div className="min-w-0">
                <p className="truncate font-bold leading-tight">SISGENOT</p>
                <p className="truncate text-xs text-slate-500">Gestion academica</p>
              </div>
            )}
          </div>
          <Button variant="ghost" className="h-9 w-9 px-0 lg:hidden" onClick={closeSidebar}>
            <X className="h-4 w-4" />
          </Button>
        </div>

        {/* Navigation */}
        <nav className={cn('flex-1 space-y-1 p-3', sidebarCollapsed ? 'overflow-visible' : 'overflow-y-auto')}>
          {items.map((item) => (
            <div
              key={item.path}
              className="group relative"
              title={sidebarCollapsed ? item.label : undefined}
            >
              <NavLink
                to={item.path}
                onClick={closeSidebar}
                className={({ isActive }) =>
                  cn(
                    'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition',
                    isActive
                      ? 'bg-cyan-50 text-cyan-800 dark:bg-cyan-500/10 dark:text-cyan-200'
                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white',
                    sidebarCollapsed && 'lg:justify-center'
                  )
                }
              >
                <item.icon className="h-5 w-5 shrink-0" />
                {!sidebarCollapsed && <span className="truncate">{item.label}</span>}
              </NavLink>
              
              {/* Tooltip para sidebar colapsado */}
              {sidebarCollapsed && (
                <div className="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-950 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity duration-200 dark:bg-slate-800 group-hover:opacity-100 lg:block hidden">
                  {item.label}
                  <div className="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-950 dark:border-r-slate-800" />
                </div>
              )}
            </div>
          ))}
        </nav>

        {/* Footer - Collapse Button */}
        <div className="border-t border-slate-200 p-3 dark:border-slate-800 lg:block hidden">
          <Button
            variant={sidebarCollapsed ? 'secondary' : 'ghost'}
            className={cn('w-full transition-all duration-300', sidebarCollapsed && 'justify-center')}
            onClick={toggleCollapse}
            title={sidebarCollapsed ? 'Expandir' : 'Contraer'}
          >
            <ChevronLeft
              className={cn('h-4 w-4 transition-transform duration-300', sidebarCollapsed && 'rotate-180')}
            />
            {!sidebarCollapsed && <span className="ml-2">Contraer</span>}
          </Button>
        </div>
      </aside>
    </>
  );
}
