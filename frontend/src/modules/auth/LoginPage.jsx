import { zodResolver } from '@hookform/resolvers/zod';
import { motion } from 'framer-motion';
import { BookOpenCheck } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Navigate, useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from 'sonner';
import { useAuth } from '../../contexts/AuthContext';
import { forgotPasswordSchema, loginSchema, resetPasswordSchema } from '../../validations/schemas';
import { authService } from '../../services/auth.service';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import loginHero from '../../assets/login-hero.png';
import cesdeLogo from '../../assets/logo-cesde.png';

export function LoginPage() {
  const { login, isAuthenticated } = useAuth();
  const [searchParams] = useSearchParams();
  const initialResetToken = searchParams.get('token') || '';
  const initialResetEmail = searchParams.get('email') || '';
  const [mode, setMode] = useState(initialResetToken ? 'reset' : 'login');
  const navigate = useNavigate();
  const location = useLocation();
  const loginForm = useForm({ resolver: zodResolver(loginSchema), defaultValues: { email: 'admin@sisgenot.edu', password: 'Admin1234' } });
  const forgotForm = useForm({ resolver: zodResolver(forgotPasswordSchema), defaultValues: { email: '' } });
  const resetForm = useForm({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { email: initialResetEmail, token: initialResetToken, password: '', password_confirmation: '' }
  });
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting }
  } = loginForm;

  if (isAuthenticated) return <Navigate to="/app/dashboard" replace />;

  const onSubmit = async (payload) => {
    await login(payload);
    toast.success('Bienvenido a SISGENOT');
    navigate(location.state?.from?.pathname || '/app/dashboard', { replace: true });
  };

  const requestRecovery = async (payload) => {
    const response = await authService.forgotPassword(payload);
    toast.success(response.message);
    resetForm.setValue('email', payload.email);
    if (response.reset_token) {
      resetForm.setValue('token', response.reset_token);
    }
    setMode('reset');
  };

  const resetPassword = async (payload) => {
    const response = await authService.resetPassword(payload);
    toast.success(response.message);
    resetForm.reset();
    setMode('login');
  };

  return (
    <main className="grid min-h-screen bg-slate-50 dark:bg-slate-950 lg:grid-cols-[1.15fr_0.85fr]">
      <section
        className="relative hidden overflow-hidden p-10 text-white lg:flex lg:flex-col lg:justify-between"
        style={{
          backgroundImage: `linear-gradient(120deg, rgba(17, 24, 39, 0.32), rgba(190, 24, 93, 0.58)), url(${loginHero})`,
          backgroundPosition: 'center',
          backgroundSize: 'cover'
        }}
      >
        <div className="absolute inset-0 bg-slate-950/10" />
        <div className="relative z-10 flex items-center gap-3">
          <div className="grid h-11 w-11 place-items-center rounded-lg bg-white/15 backdrop-blur">
            <BookOpenCheck className="h-6 w-6" />
          </div>
          <div>
            <p className="font-bold">SISGENOT</p>
            <p className="text-sm text-white/75">Sistema de Gestion Academico</p>
          </div>
        </div>
        <motion.div initial={{ opacity: 0, y: 22 }} animate={{ opacity: 1, y: 0 }} className="relative z-10 max-w-xl">
          <p className="mb-5 inline-flex rounded-full bg-white/15 px-3 py-1 text-sm text-white backdrop-blur">Notas, reportes y auditoria en un solo lugar</p>
          <h1 className="text-5xl font-semibold tracking-tight">Gestion academica clara, segura y lista para crecer.</h1>
          <p className="mt-5 text-lg text-white/80">Administra estudiantes, docentes, cursos, periodos y calificaciones con una experiencia moderna conectada a tu API Laravel.</p>
        </motion.div>
      </section>
      <section className="grid place-items-center p-6">
        {mode === 'login' && (
        <motion.form initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} className="panel w-full max-w-md p-6" onSubmit={handleSubmit(onSubmit)}>
          <div className="mb-6">
            <img src={cesdeLogo} alt="CESDE" className="mb-6 h-9 w-auto" />
            <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">Acceso seguro</p>
            <h2 className="mt-1 text-2xl font-bold">Iniciar sesion</h2>
          </div>
          <div className="space-y-4">
            <Input label="Correo institucional" type="email" error={errors.email?.message} {...register('email')} />
            <Input label="Contrasena" type="password" error={errors.password?.message} {...register('password')} />
            <Button type="submit" className="w-full" loading={isSubmitting}>
              Entrar al sistema
            </Button>
          </div>
          <button type="button" className="mt-5 w-full text-center text-sm font-medium text-cyan-700 hover:text-cyan-900" onClick={() => setMode('forgot')}>
            Olvide mi contrasena
          </button>
        </motion.form>
        )}

        {mode === 'forgot' && (
        <motion.form initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} className="panel w-full max-w-md p-6" onSubmit={forgotForm.handleSubmit(requestRecovery)}>
          <div className="mb-6">
            <img src={cesdeLogo} alt="CESDE" className="mb-6 h-9 w-auto" />
            <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">Recuperacion</p>
            <h2 className="mt-1 text-2xl font-bold">Recuperar contrasena</h2>
            <p className="mt-2 text-sm text-slate-500">Ingresa tu correo institucional y enviaremos un token de recuperacion.</p>
          </div>
          <div className="space-y-4">
            <Input label="Correo institucional" type="email" error={forgotForm.formState.errors.email?.message} {...forgotForm.register('email')} />
            <Button type="submit" className="w-full" loading={forgotForm.formState.isSubmitting}>
              Enviar instrucciones
            </Button>
          </div>
          <button type="button" className="mt-5 w-full text-center text-sm font-medium text-slate-500 hover:text-slate-800" onClick={() => setMode('login')}>
            Volver al inicio de sesion
          </button>
        </motion.form>
        )}

        {mode === 'reset' && (
        <motion.form initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} className="panel w-full max-w-md p-6" onSubmit={resetForm.handleSubmit(resetPassword)}>
          <div className="mb-6">
            <img src={cesdeLogo} alt="CESDE" className="mb-6 h-9 w-auto" />
            <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">Nueva contrasena</p>
            <h2 className="mt-1 text-2xl font-bold">Restablecer acceso</h2>
            <p className="mt-2 text-sm text-slate-500">Usa el token recibido por correo y define una contrasena segura.</p>
          </div>
          <div className="space-y-4">
            <Input label="Correo institucional" type="email" error={resetForm.formState.errors.email?.message} {...resetForm.register('email')} />
            <Input label="Token de recuperacion" error={resetForm.formState.errors.token?.message} {...resetForm.register('token')} />
            <Input label="Nueva contrasena" type="password" error={resetForm.formState.errors.password?.message} {...resetForm.register('password')} />
            <Input label="Confirmar contrasena" type="password" error={resetForm.formState.errors.password_confirmation?.message} {...resetForm.register('password_confirmation')} />
            <Button type="submit" className="w-full" loading={resetForm.formState.isSubmitting}>
              Cambiar contrasena
            </Button>
          </div>
          <button type="button" className="mt-5 w-full text-center text-sm font-medium text-slate-500 hover:text-slate-800" onClick={() => setMode('login')}>
            Volver al inicio de sesion
          </button>
        </motion.form>
        )}
      </section>
    </main>
  );
}
