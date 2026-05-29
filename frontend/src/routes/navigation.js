import {
  Activity,
  BarChart3,
  BookOpen,
  CalendarDays,
  ClipboardList,
  FileClock,
  GraduationCap,
  LayoutDashboard,
  Library,
  ListChecks,
  School,
  Settings2,
  Users
} from 'lucide-react';

export const navItems = [
  { label: 'Dashboard', path: '/app/dashboard', icon: LayoutDashboard, roles: ['admin', 'teacher', 'student'] },
  { label: 'Usuarios', path: '/app/users', icon: Users, roles: ['admin'] },
  { label: 'Docentes', path: '/app/teachers', icon: GraduationCap, roles: ['admin'] },
  { label: 'Estudiantes', path: '/app/students', icon: School, roles: ['admin', 'teacher'] },
  { label: 'Cursos', path: '/app/courses', icon: Library, roles: ['admin', 'teacher', 'student'] },
  { label: 'Asignaturas', path: '/app/subjects', icon: BookOpen, roles: ['admin', 'teacher', 'student'] },
  { label: 'Periodos', path: '/app/periods', icon: CalendarDays, roles: ['admin', 'teacher', 'student'] },
  { label: 'Asignaciones', path: '/app/assignments', icon: Settings2, roles: ['admin', 'teacher'] },
  { label: 'Notas', path: '/app/grades', icon: ListChecks, roles: ['admin', 'teacher', 'student'] },
  { label: 'Reportes', path: '/app/reports', icon: BarChart3, roles: ['admin', 'teacher', 'student'] },
  { label: 'Auditoria', path: '/app/audit', icon: FileClock, roles: ['admin', 'teacher'] },

  { label: 'Observaciones', path: '/app/observations', icon: Activity, roles: ['admin', 'teacher', 'student'] },
  { label: 'Horarios', path: '/app/schedules', icon: CalendarDays, roles: ['admin', 'teacher', 'student'] }
];

export const canAccess = (item, role) => Boolean(role && item.roles.includes(role));

export const routePermissions = Object.fromEntries(
  navItems.map((item) => [item.path.replace('/app/', ''), item.roles])
);
