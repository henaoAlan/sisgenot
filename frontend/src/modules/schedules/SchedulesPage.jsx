import { useMemo } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { assignmentsService, coursesService, schedulesService, subjectsService } from '../../services/resource.service';
import { scheduleSchema } from '../../validations/schemas';
import { ResourcePage } from '../shared/ResourcePage';

const dayOptions = [
  { value: '1', label: 'Lunes' },
  { value: '2', label: 'Martes' },
  { value: '3', label: 'Miercoles' },
  { value: '4', label: 'Jueves' },
  { value: '5', label: 'Viernes' },
  { value: '6', label: 'Sabado' },
  { value: '7', label: 'Domingo' }
];

const dayLabels = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miercoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sabado',
  7: 'Domingo'
};

export function SchedulesPage() {
  const { role, user } = useAuth();
  const isStudent = role === 'student';
  const courseId = user?.student_profile?.course_id || user?.student_profile?.course?.id;
  const { data: coursesData } = useAsync(() => coursesService.list(), []);
  const { data: subjectsData } = useAsync(() => subjectsService.list(), []);
  const { data: assignmentsData } = useAsync(
    () => (role === 'admin' ? assignmentsService.list() : Promise.resolve([])),
    [role]
  );
  const listParams = useMemo(() => {
    if (isStudent && courseId) return { course_id: courseId };
    return {};
  }, [isStudent, courseId]);

  const allCourseOptions = useMemo(
    () => (Array.isArray(coursesData) ? coursesData : []).map((course) => ({
      value: course.id.toString(),
      label: `${course.name} - ${course.year}`
    })),
    [coursesData]
  );

  const allSubjectOptions = useMemo(
    () => (Array.isArray(subjectsData) ? subjectsData : []).map((subject) => ({
      value: subject.id.toString(),
      label: subject.name
    })),
    [subjectsData]
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

  const getCourseOptions = (values) => {
    const selectedTeacherId = values?.teacher_id ? Number(values.teacher_id) : null;
    if (!selectedTeacherId) return allCourseOptions;

    const coursesByTeacher = (Array.isArray(assignmentsData) ? assignmentsData : [])
      .filter((assignment) => Number(assignment.teacher_id) === selectedTeacherId)
      .map((assignment) => assignment.course)
      .filter(Boolean);

    return Array.from(new Map(coursesByTeacher.map((course) => [course.id, course])).values())
      .map((course) => ({
        value: course.id.toString(),
        label: `${course.name} - ${course.year}`
      }));
  };

  const getSubjectOptions = (values) => {
    const selectedTeacherId = values?.teacher_id ? Number(values.teacher_id) : null;
    const selectedCourseId = values?.course_id ? Number(values.course_id) : null;

    if (selectedTeacherId && selectedCourseId) {
      const subjectsByAssignment = (Array.isArray(assignmentsData) ? assignmentsData : [])
        .filter((assignment) =>
          Number(assignment.teacher_id) === selectedTeacherId &&
          Number(assignment.course_id ?? assignment.course?.id) === selectedCourseId
        )
        .map((assignment) => assignment.subject)
        .filter(Boolean);

      return Array.from(new Map(subjectsByAssignment.map((subject) => [subject.id, subject])).values())
        .map((subject) => ({
          value: subject.id.toString(),
          label: subject.name
        }));
    }

    if (selectedCourseId) {
      return (Array.isArray(subjectsData) ? subjectsData : [])
        .filter((subject) => Number(subject.course_id ?? subject.course?.id) === selectedCourseId)
        .map((subject) => ({
          value: subject.id.toString(),
          label: subject.name
        }));
    }

    return allSubjectOptions;
  };

  return (
    <ResourcePage
      title="Horarios"
      description="Consulta y administra horarios por curso, asignatura y docente."
      service={schedulesService}
      listParams={listParams}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Dia', accessorFn: (row) => dayLabels[row.day_of_week] || row.day_of_week },
        { header: 'Inicio', accessorKey: 'starts_at' },
        { header: 'Fin', accessorKey: 'ends_at' },
        { header: 'Curso', accessorFn: (row) => row.course?.name || row.course_id },
        { header: 'Asignatura', accessorFn: (row) => row.subject?.name || row.subject_id },
        { header: 'Docente', accessorFn: (row) => row.teacher?.user?.full_name || '-' },
        { header: 'Aula', accessorKey: 'classroom' }
      ]}
      form={{
        schema: scheduleSchema,
        defaultValues: { teacher_id: '', course_id: '', subject_id: '', day_of_week: 1, starts_at: '', ends_at: '', classroom: '' },
        fields: [
          { name: 'teacher_id', label: 'Docente', type: 'select', options: teacherOptions, valueAsNumber: true, clearFieldsOnChange: ['course_id', 'subject_id'] },
          { name: 'course_id', label: 'Curso', type: 'select', options: getCourseOptions, valueAsNumber: true, clearFieldsOnChange: ['subject_id'] },
          { name: 'subject_id', label: 'Asignatura', type: 'select', options: getSubjectOptions, valueAsNumber: true },
          { name: 'day_of_week', label: 'Dia', type: 'select', options: dayOptions, valueAsNumber: true },
          { name: 'starts_at', label: 'Hora inicio', type: 'time' },
          { name: 'ends_at', label: 'Hora fin', type: 'time' },
          { name: 'classroom', label: 'Aula' }
        ]
      }}
      createTitle="Nuevo horario"
    />
  );
}
