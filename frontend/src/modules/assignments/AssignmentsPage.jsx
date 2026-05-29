import { useMemo } from 'react';
import { z } from 'zod';
import { useAuth } from '../../contexts/AuthContext';
import { assignmentsService, coursesService, subjectsService, usersService } from '../../services/resource.service';
import { ResourcePage } from '../shared/ResourcePage';
import { useAsync } from '../../hooks/useAsync';

const assignmentSchema = z.object({
  teacher_id: z.coerce.number().min(1),
  course_id: z.coerce.number().min(1),
  subject_id: z.coerce.number().min(1)
});

export function AssignmentsPage() {
  const { role } = useAuth();
  const { data: teachersData } = useAsync(
    () => (role === 'admin' ? usersService.list({ role: 'teacher', per_page: 100 }) : Promise.resolve([])),
    [role]
  );
  const { data: coursesData } = useAsync(() => coursesService.list(), []);
  const { data: subjectsData } = useAsync(() => subjectsService.list(), []);

  const teacherOptions = useMemo(
    () =>
      (Array.isArray(teachersData) ? teachersData : []).map((user) => ({
        value: user.teacher?.id ?? '',
        label: user.full_name
      })),
    [teachersData]
  );

  const courseOptions = useMemo(
    () =>
      (Array.isArray(coursesData) ? coursesData : []).map((course) => ({
        value: course.id,
        label: `${course.name} - Año ${course.year}`
      })),
    [coursesData]
  );

  const getSubjectOptions = (values) => {
    const selectedCourseId = values?.course_id ? Number(values.course_id) : null;
    return (Array.isArray(subjectsData) ? subjectsData : [])
      .filter((subject) => !selectedCourseId || Number(subject.course_id ?? subject.course?.id) === selectedCourseId)
      .map((subject) => ({
        value: subject.id,
        label: subject.course?.name ? `${subject.name} - ${subject.course.name}` : subject.name
      }));
  };

  return (
    <ResourcePage
      title="Asignaciones docentes"
      description="Vincula docentes con cursos y asignaturas."
      service={assignmentsService}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Docente', accessorFn: (row) => row.teacher?.user?.full_name || row.teacher_id },
        { header: 'Curso', accessorFn: (row) => row.course?.name || row.course_id },
        { header: 'Asignatura', accessorFn: (row) => row.subject?.name || row.subject_id }
      ]}
      form={{
        schema: assignmentSchema,
        defaultValues: { teacher_id: '', course_id: '', subject_id: '' },
        fields: [
          { name: 'teacher_id', label: 'Docente', type: 'select', options: teacherOptions, valueAsNumber: true },
          { name: 'course_id', label: 'Curso', type: 'select', options: courseOptions, valueAsNumber: true, clearFieldsOnChange: ['subject_id'] },
          { name: 'subject_id', label: 'Asignatura', type: 'select', options: getSubjectOptions, valueAsNumber: true }
        ]
      }}
      createTitle="Nueva asignación"
    />
  );
}
