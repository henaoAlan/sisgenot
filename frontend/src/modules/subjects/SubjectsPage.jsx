import { useMemo } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { api } from '../../api/client';
import { coursesService, subjectsService } from '../../services/resource.service';
import { useAsync } from '../../hooks/useAsync';
import { subjectSchema } from '../../validations/schemas';
import { ResourcePage } from '../shared/ResourcePage';

const teacherSubjectsService = {
  list: async () => {
    const { data } = await api.get('/teacher-assignments');
    const assignments = data?.data || [];
    const subjects = assignments
      .map((assignment) => assignment.subject)
      .filter(Boolean);

    const uniqueSubjects = Array.from(
      new Map(subjects.map((subject) => [subject.id, subject])).values()
    );

    return { data: uniqueSubjects };
  }
};

export function SubjectsPage() {
  const { role, user } = useAuth();
  const courseId = user?.student_profile?.course_id || user?.student_profile?.course?.id;
  const isStudent = role === 'student';
  const { data: coursesData } = useAsync(
    () => (role === 'admin' ? coursesService.list() : Promise.resolve([])),
    [role]
  );
  const service = useMemo(
    () => (role === 'teacher' ? teacherSubjectsService : subjectsService),
    [role]
  );
  const listParams = useMemo(() => {
    if (!isStudent) return {};
    return courseId ? { course_id: courseId } : { course_id: -1 };
  }, [isStudent, courseId]);
  const courseOptions = useMemo(() => {
    const courses = Array.isArray(coursesData)
      ? coursesData
      : coursesData?.data && Array.isArray(coursesData.data)
      ? coursesData.data
      : [];

    return courses.map((course) => ({
      value: course.id,
      label: `${course.name} - Año ${course.year}`
    }));
  }, [coursesData]);

  return (
    <ResourcePage
      title="Asignaturas"
      description={
        role === 'teacher'
          ? 'Asignaturas que dictas.'
          : role === 'student'
          ? 'Asignaturas de tu curso.'
          : 'Catalogo academico de materias.'
      }
      service={service}
      listParams={listParams}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Nombre', accessorKey: 'name' },
        { header: 'Codigo', accessorKey: 'code' },
        { header: 'Curso', accessorFn: (row) => row.course?.name || row.course_id || '-' }
      ]}
      form={{
        schema: subjectSchema,
        defaultValues: { name: '', code: '', course_id: '' },
        fields: [
          { name: 'name', label: 'Nombre' },
          { name: 'code', label: 'Codigo' },
          { name: 'course_id', label: 'Curso', type: 'select', options: courseOptions, valueAsNumber: true }
        ]
      }}
    />
  );
}
