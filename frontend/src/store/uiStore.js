import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { themeStorage } from '../utils/storage';

const getSidebarCollapsed = () => {
  try {
    return localStorage.getItem('sidebarCollapsed') === 'true';
  } catch {
    return false;
  }
};

export const useUiStore = create(
  persist(
    (set) => ({
      sidebarOpen: false,
      sidebarCollapsed: getSidebarCollapsed(),
      theme: themeStorage.get(),
      globalSearch: '',
      toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
      closeSidebar: () => set({ sidebarOpen: false }),
      setGlobalSearch: (globalSearch) => set({ globalSearch }),
      toggleCollapse: () =>
        set((state) => {
          const newValue = !state.sidebarCollapsed;
          localStorage.setItem('sidebarCollapsed', newValue);
          return { sidebarCollapsed: newValue };
        }),
      setTheme: (theme) => {
        themeStorage.set(theme);
        document.documentElement.classList.toggle('dark', theme === 'dark');
        set({ theme });
      }
    }),
    {
      name: 'ui-store',
      partialize: (state) => ({ sidebarCollapsed: state.sidebarCollapsed })
    }
  )
);
