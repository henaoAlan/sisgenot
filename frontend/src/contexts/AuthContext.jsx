import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { authService } from '../services/auth.service';
import { tokenStorage, userStorage } from '../utils/storage';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => tokenStorage.get());
  const [user, setUser] = useState(() => userStorage.get());
  const [booting, setBooting] = useState(Boolean(tokenStorage.get()));

  const login = useCallback(async (payload) => {
    const data = await authService.login(payload);
    tokenStorage.set(data.token);
    userStorage.set(data.user);
    setToken(data.token);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      if (tokenStorage.get()) await authService.logout();
    } finally {
      tokenStorage.clear();
      userStorage.clear();
      setToken(null);
      setUser(null);
    }
  }, []);

  const refreshUser = useCallback(async () => {
    if (!tokenStorage.get()) return null;
    const profile = await authService.me();
    userStorage.set(profile);
    setUser(profile);
    return profile;
  }, []);

  useEffect(() => {
    let mounted = true;

    async function init() {
      try {
        await refreshUser();
      } finally {
        if (mounted) setBooting(false);
      }
    }

    init();

    return () => {
      mounted = false;
    };
  }, [refreshUser]);

  const value = useMemo(
    () => ({
      token,
      user,
      role: user?.role,
      booting,
      isAuthenticated: Boolean(token && user),
      login,
      logout,
      refreshUser
    }),
    [token, user, booting, login, logout, refreshUser]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth debe usarse dentro de AuthProvider');
  return context;
}
