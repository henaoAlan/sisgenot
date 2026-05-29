import { motion } from 'framer-motion';
import { BookOpen, GraduationCap, Library, TrendingUp, Users } from 'lucide-react';
import { Card, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import { LineChart } from '../../components/charts/LineChart';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { auditService, assignmentsService, coursesService, gradesService, subjectsService, usersService } from '../../services/resource.service';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';
import { Skeleton } from '../../components/common/Skeleton';
import { useUiStore } from '../../store/uiStore';

const normalizeText = (value) =>
  String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const matchesSearch = (item, search) => {
  if (!search) return true;

  const visit = (value) => {
    if (value === null || value === undefined) return false;
    if (['string', 'number', 'boolean'].includes(typeof value)) {
      return normalizeText(value).includes(search);
    }
    if (Array.isArray(value)) return value.some(visit);
    if (typeof value === 'object') return Object.values(value).some(visit);
    return false;
  };

  return visit(item);
};

function StatCard({ icon: Icon, label, value, tone = 'bg-cyan-600' }) {
  return (
    <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="panel p-5">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-slate-500 dark:text-slate-400">{label}</p>
          <p className="mt-2 text-3xl font-bold">{value ?? '-'}</p>
        </div>
        <div className={`grid h-12 w-12 place-items-center rounded-lg text-white ${tone}`}>
          <Icon className="h-5 w-5" />
        </div>
      </div>
    </motion.div>
  );
}

export function DashboardPage() {
  const { user, role } = useAuth();
  const globalSearch = useUiStore((state) => state.globalSearch);
  const teacherId = user?.teacher?.id || user?.teacher_profile?.id;
  const { data, loading } = useAsync(async () => {
    const [courses, subjects, grades] = await Promise.all([coursesService.list(), subjectsService.list(), gradesService.list()]);
    let users = null;
    let assignedStudents = null;
    let teacherAssignments = null;
    let audit = null;
    if (role === 'admin') users = await usersService.list({ per_page: 100 });
    if (role === 'teacher' && teacherId) {
      assignedStudents = await teacherStudentAssignmentService.getStudentsByTeacher(teacherId);
      teacherAssignments = await assignmentsService.list();
    }
    if (role !== 'student') audit = await auditService.list({ per_page: 5 });
    return { courses, subjects, grades, users, assignedStudents, teacherAssignments, audit };
  }, [role, teacherId]);

  if (loading) {
    return <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} />)}</div>;
  }

  const users = Array.isArray(data?.users) ? data.users : data?.users?.data || [];
  const assignedStudents = Array.isArray(data?.assignedStudents) ? data.assignedStudents : [];
  const teacherAssignments = Array.isArray(data?.teacherAssignments) ? data.teacherAssignments : data?.teacherAssignments?.data || [];
  const grades = Array.isArray(data?.grades) ? data.grades : data?.grades?.data || [];
  const audit = data?.audit?.data || [];
  const searchTerm = normalizeText(globalSearch).trim();
  const activityItems = (role === 'student' ? grades : audit).filter((item) => matchesSearch(item, searchTerm)).slice(0, 6);
  const average = grades.length ? (grades.reduce((acc, item) => acc + Number(item.grade || 0), 0) / grades.length).toFixed(2) : '0.00';
  const statsGridClass = role === 'student' ? 'grid gap-4 md:grid-cols-2 xl:grid-cols-[1.8fr_1fr_1fr_1fr]' : 'grid gap-4 md:grid-cols-2 xl:grid-cols-4';
  const courseName = user?.student_profile?.course?.name || user?.student_profile?.course_name || 'Sin curso';
  const courseSubjectsCount = Array.isArray(data?.subjects)
    ? data.subjects.filter((subject) => Number(subject.course_id ?? subject.course?.id) === Number(user?.student_profile?.course?.id)).length
    : 0;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2">
        <Badge tone="cyan">{role}</Badge>
        <h1 className="text-2xl font-bold">Hola, {user?.full_name}</h1>
        <p className="text-slate-500 dark:text-slate-400">Resumen operativo de SISGENOT segun tus permisos.</p>
      </div>

      <div className={statsGridClass}>
        <StatCard
          icon={role === 'student' ? BookOpen : role === 'teacher' ? Library : Users}
          label={
            role === 'student'
              ? 'Curso'
              : role === 'teacher'
              ? 'Materias asignadas'
              : 'Usuarios'
          }
          value={
            role === 'student'
              ? courseName
              : role === 'teacher'
              ? Array.from(new Map(teacherAssignments
                  .filter((assignment) => assignment.teacher_id === teacherId)
                  .map((assignment) => [assignment.subject?.id, assignment.subject]))
                  .values()).length
              : users.length
          }
          tone={
            role === 'student'
              ? 'bg-cyan-600'
              : role === 'teacher'
              ? 'bg-violet-600'
              : 'bg-cyan-600'
          }
        />
        <StatCard
          icon={role === 'student' ? Library : GraduationCap}
          label={role === 'student' ? 'Asignaturas' : 'Estudiantes'}
          value={
            role === 'student'
              ? courseSubjectsCount
              : role === 'admin'
              ? users.filter((u) => u.role === 'student').length
              : assignedStudents.length
          }
          tone="bg-emerald-600"
        />
        <StatCard icon={Library} label="Cursos" value={data?.courses?.length || 0} tone="bg-violet-600" />
        <StatCard icon={TrendingUp} label="Promedio notas" value={average} tone="bg-amber-600" />
      </div>

      <div className="grid gap-4 xl:grid-cols-[1.4fr_0.8fr]">
        <Card>
          <CardHeader title="Rendimiento academico" description="Tendencia visual de indicadores principales." />
          <LineChart
            categories={['P1', 'P2', 'P3', 'P4']}
            series={[
              { name: 'Promedio', data: [3.8, 4.1, Number(average), 4.3] },
              { name: 'Actividades', data: [12, 14, 10, 16] }
            ]}
          />
        </Card>
        <Card>
          <CardHeader title={role === 'student' ? 'Ultimas notas' : 'Actividad reciente'} description="Cambios y registros relevantes." />
          <div className="space-y-3">
            {activityItems.map((item) => (
              <div key={item.id} className="rounded-md border border-slate-200 p-3 dark:border-slate-800">
                <p className="font-medium">{item.subject?.name || item.action || 'Registro'}</p>
                <p className="text-sm text-slate-500">{item.grade || item.new_grade || item.competency || item.created_at}</p>
              </div>
            ))}
            {!activityItems.length && (
              <p className="rounded-md border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-800">
                No hay resultados para la busqueda actual.
              </p>
            )}
          </div>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card><CardHeader title="Cursos activos" description={`${data?.courses?.length || 0} registros disponibles`} /><BookOpen className="h-8 w-8 text-cyan-700" /></Card>
        <Card><CardHeader title="Asignaturas" description={`${data?.subjects?.length || 0} materias catalogadas`} /><Library className="h-8 w-8 text-emerald-700" /></Card>
        <Card><CardHeader title="Notas" description={`${grades.length} notas en tu alcance`} /><TrendingUp className="h-8 w-8 text-amber-700" /></Card>
      </div>
    </div>
  );
}
