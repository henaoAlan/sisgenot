const TOKEN_KEY = 'sisgenot.token';
const USER_KEY = 'sisgenot.user';
const THEME_KEY = 'sisgenot.theme';

export const tokenStorage = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY)
};

export const userStorage = {
  get: () => {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY));
    } catch {
      return null;
    }
  },
  set: (user) => localStorage.setItem(USER_KEY, JSON.stringify(user)),
  clear: () => localStorage.removeItem(USER_KEY)
};

export const themeStorage = {
  get: () => localStorage.getItem(THEME_KEY) || 'light',
  set: (theme) => localStorage.setItem(THEME_KEY, theme)
};
