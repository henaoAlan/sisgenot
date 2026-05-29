import { useAuth } from '../../contexts/AuthContext';
import { coursesService } from '../../services/resource.service';
import { courseSchema } from '../../validations/schemas';
import { ResourcePage, activeCell } from '../shared/ResourcePage';

export function CoursesPage() {
  const { role } = useAuth();
  return (
    <ResourcePage
      title="Cursos"
      description="Gestiona grupos academicos, anio lectivo y estado."
      service={coursesService}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Nombre', accessorKey: 'name' },
        { header: 'Grado', accessorKey: 'grade' },
        { header: 'Anio', accessorKey: 'year' },
        { header: 'Estudiantes', accessorKey: 'students_count' },
        { header: 'Estado', accessorKey: 'is_active', cell: activeCell }
      ]}
      form={{
        schema: courseSchema,
        defaultValues: { name: '', grade: '', year: new Date().getFullYear(), is_active: true },
        fields: [
          { name: 'name', label: 'Nombre' },
          { name: 'grade', label: 'Grado' },
          { name: 'year', label: 'Anio', type: 'number' },
          { name: 'is_active', label: 'Curso activo', type: 'checkbox' }
        ]
      }}
    />
  );
}
