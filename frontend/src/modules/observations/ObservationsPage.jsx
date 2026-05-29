import { useMemo } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { assignmentsService, observationsService, periodsService, studentsService, subjectsService } from '../../services/resource.service';
import { observationSchema } from '../../validations/schemas';
import { ResourcePage } from '../shared/ResourcePage';

export function ObservationsPage() {
  const { role, user } = useAuth();
  const canWrite = ['admin', 'teacher'].includes(role);
  const studentId = user?.student_profile?.id;
  const { data: studentsData } = useAsync(
    () => (canWrite ? studentsService.list() : Promise.resolve([])),
    [canWrite]
  );
  const { data: assignmentsData } = useAsync(
    () => (role === 'admin' ? assignmentsService.list() : Promise.resolve([])),
    [role]
  );
  const { data: subjectsData } = useAsync(() => subjectsService.list(), []);
  const { data: periodsData } = useAsync(() => periodsService.list(), []);

  const listParams = useMemo(() => {
    if (role !== 'student') return {};
    return studentId ? { student_id: studentId } : { student_id: -1 };
  }, [role, studentId]);

  const studentOptions = useMemo(
    () => (Array.isArray(studentsData) ? studentsData : []).map((student) => ({
      value: student.id.toString(),
      label: `${student.user?.full_name || `Estudiante ${student.id}`} - ${student.course?.name || 'Sin curso'}`
    })),
    [studentsData]
  );

  const teacherOptions = useMemo(() => {
    const seen = new Set();
    return (Array.isArray(assignmentsData) ? assignmentsData : [])
      .filter((assignment) => {
        if (!assignment.teacher_id || seen.has(assignment.teacher_id)) return false;
        seen.add(assignment.teacher_id);
        return true;
      })
      .map((assignment) => ({
        value: assignment.teacher_id.toString(),
        label: assignment.teacher?.user?.full_name || `Docente ${assignment.teacher_id}`
      }));
  }, [assignmentsData]);

  const subjectOptions = useMemo(
    () => (Array.isArray(subjectsData) ? subjectsData : []).map((subject) => ({
      value: subject.id.toString(),
      label: subject.name
    })),
    [subjectsData]
  );

  const periodOptions = useMemo(
    () => (Array.isArray(periodsData) ? periodsData : []).map((period) => ({
      value: period.id.toString(),
      label: `${period.name} - ${period.year}`
    })),
    [periodsData]
  );

  return (
    <ResourcePage
      title="Observaciones"
      description={
        role === 'student'
          ? 'Revisa las observaciones que te ha realizado tu docente.'
          : 'Registra y consulta observaciones academicas de estudiantes.'
      }
      service={observationsService}
      listParams={listParams}
      canWrite={canWrite}
      columns={[
        { header: 'Estudiante', accessorFn: (row) => row.student?.user?.full_name || row.student_id },
        { header: 'Curso', accessorFn: (row) => row.student?.course?.name || '-' },
        { header: 'Docente', accessorFn: (row) => row.teacher?.user?.full_name || '-' },
        { header: 'Asignatura', accessorFn: (row) => row.subject?.name || '-' },
        { header: 'Periodo', accessorFn: (row) => row.period?.name || '-' },
        { header: 'Observacion', accessorKey: 'observation' },
        { header: 'Fecha', accessorKey: 'created_at' }
      ]}
      form={{
        schema: observationSchema,
        defaultValues: { student_id: '', teacher_id: '', subject_id: '', period_id: '', observation: '' },
        fields: [
          ...(canWrite ? [{ name: 'student_id', label: 'Estudiante', type: 'select', options: studentOptions, valueAsNumber: true }] : []),
          ...(role === 'admin' ? [{ name: 'teacher_id', label: 'Docente', type: 'select', options: teacherOptions, valueAsNumber: true }] : []),
          { name: 'subject_id', label: 'Asignatura', type: 'select', options: subjectOptions, valueAsNumber: true },
          { name: 'period_id', label: 'Periodo', type: 'select', options: periodOptions, valueAsNumber: true },
          { name: 'observation', label: 'Observacion' }
        ]
      }}
      createTitle="Nueva observacion"
    />
  );
}
