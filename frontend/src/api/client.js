import axios from 'axios';
import { toast } from 'sonner';
import { tokenStorage, userStorage } from '../utils/storage';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json'
  }
});

api.interceptors.request.use((config) => {
  const token = tokenStorage.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const message = error.response?.data?.message || 'No fue posible completar la solicitud.';

    if (status === 401) {
      tokenStorage.clear();
      userStorage.clear();
      if (!window.location.pathname.includes('/login')) {
        toast.error('Sesion expirada. Ingresa nuevamente.');
        window.location.href = '/login';
      }
    } else if (status >= 500) {
      toast.error('Error del servidor.');
    } else if (message) {
      toast.error(message);
    }

    return Promise.reject(error);
  }
);
