import { LogOut, Menu, Moon, Search, Sun, X } from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { useAuth } from '../../contexts/AuthContext';
import { useUiStore } from '../../store/uiStore';
import { navItems } from '../../routes/navigation';
import { Button } from '../ui/Button';
import { Badge } from '../ui/Badge';

export function Topbar() {
  const { user, logout } = useAuth();
  const { pathname } = useLocation();
  const navigate = useNavigate();
  const { toggleSidebar, theme, setTheme, globalSearch, setGlobalSearch } = useUiStore();
  const current = navItems.find((item) => pathname.startsWith(item.path));

  const handleLogout = async () => {
    await logout();
    toast.success('Sesion cerrada.');
    navigate('/login', { replace: true });
  };

  const handleSearchSubmit = (event) => {
    event.preventDefault();
    const term = globalSearch.trim().toLowerCase();
    if (!term) return;

    const match = navItems.find(
      (item) => item.roles.includes(user?.role) && item.label.toLowerCase().includes(term)
    );

    if (match && match.path !== pathname) {
      navigate(match.path);
    }
  };

  return (
    <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/85 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/80">
      <div className="flex h-16 items-center gap-3 px-4 lg:px-6">
        <Button variant="ghost" className="h-9 w-9 px-0 lg:hidden" onClick={toggleSidebar}>
          <Menu className="h-5 w-5" />
        </Button>
        <div className="min-w-0 flex-1">
          <p className="text-xs text-slate-500 dark:text-slate-400">Inicio / {current?.label || 'Panel'}</p>
          <h1 className="truncate text-lg font-semibold">{current?.label || 'SISGENOT'}</h1>
        </div>
        <form onSubmit={handleSearchSubmit} className="relative hidden w-72 md:block">
          <input
            className="h-10 w-full rounded-md border border-slate-200 bg-white pl-9 pr-9 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-slate-800 dark:bg-slate-950 dark:focus:ring-cyan-500/20"
            value={globalSearch}
            placeholder="Busqueda global"
            onChange={(event) => setGlobalSearch(event.target.value)}
          />
          <Search className="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-400" />
          {globalSearch && (
            <button
              type="button"
              className="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
              onClick={() => setGlobalSearch('')}
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </form>
        <Button variant="ghost" className="h-9 w-9 px-0" onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}>
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </Button>
        <div className="hidden text-right sm:block">
          <p className="text-sm font-semibold">{user?.full_name}</p>
          <Badge tone="cyan">{user?.role}</Badge>
        </div>
        <Button variant="secondary" className="h-9 w-9 px-0" onClick={handleLogout}>
          <LogOut className="h-4 w-4" />
        </Button>
      </div>
    </header>
  );
}
