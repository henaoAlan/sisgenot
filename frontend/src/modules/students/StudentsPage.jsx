import { useMemo } from 'react';
import { studentsService } from '../../services/resource.service';
import { ResourcePage } from '../shared/ResourcePage';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';

export function StudentsPage() {
  const { user } = useAuth();

  // Si es docente, cargar solo sus estudiantes asignados
  const teacherId = user?.teacher?.id || user?.teacher_profile?.id;

  const { data: assignedStudents = [] } = useAsync(
    () => {
      if (teacherId) {
        return teacherStudentAssignmentService.getStudentsByTeacher(teacherId);
      }
      return Promise.resolve([]);
    },
    [teacherId]
  );

  const columns = useMemo(
    () => [
      { header: 'Nombre', accessorFn: (row) => row.user?.full_name || '-' },
      { header: 'Correo', accessorFn: (row) => row.user?.email || '-' },
      { header: 'Curso', accessorFn: (row) => row.course?.name || '-' },
      { header: 'Matrícula', accessorKey: 'enrollment_code' },
      { header: 'Estado', accessorFn: (row) => (row.user?.is_active ? 'Activo' : 'Inactivo') }
    ],
    []
  );

  const service = useMemo(
    () => {
      if (user?.role === 'teacher') {
        return {
          list: async () => assignedStudents,
        };
      }
      return studentsService;
    },
    [user?.role, assignedStudents]
  );

  return (
    <ResourcePage
      title="Estudiantes"
      description={
        user?.role === 'teacher'
          ? 'Tus estudiantes asignados.'
          : 'Lista de estudiantes asignados al docente o registrados en el sistema.'
      }
      service={service}
      columns={columns}
      canWrite={false}
    />
  );
}
