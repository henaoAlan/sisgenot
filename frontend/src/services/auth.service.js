import { api } from '../api/client';

export const authService = {
  login: async (payload) => {
    const { data } = await api.post('/auth/login', payload);
    return data;
  },
  forgotPassword: async (payload) => {
    const { data } = await api.post('/auth/forgot-password', payload);
    return data;
  },
  resetPassword: async (payload) => {
    const { data } = await api.post('/auth/reset-password', payload);
    return data;
  },
  me: async () => {
    const { data } = await api.get('/auth/me');
    return data.user;
  },
  logout: async () => {
    const { data } = await api.post('/auth/logout');
    return data;
  }
};
