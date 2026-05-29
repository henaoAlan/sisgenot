import { useMemo } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { usersService, coursesService } from '../../services/resource.service';
import { userSchema } from '../../validations/schemas';
import { ResourcePage, activeCell, roleCell } from '../shared/ResourcePage';
import { useAsync } from '../../hooks/useAsync';

export function UsersPage({ filterRole = '', title = 'Usuarios', description = 'Administra identidades, roles y estado de acceso.' }) {
  const { role } = useAuth();
  const { data: coursesData } = useAsync(() => coursesService.list(), []);

  const courseOptions = useMemo(
    () =>
      (Array.isArray(coursesData) ? coursesData : []).map((course) => ({
        value: course.id.toString(),
        label: `${course.name} - Año ${course.year}`
      })),
    [coursesData]
  );

  const service = useMemo(
    () => ({
      ...usersService,
      list: async (params = {}) => usersService.list({
        per_page: 1000,
        ...params,
        ...(filterRole ? { role: filterRole } : {})
      })
    }),
    [filterRole]
  );

  const form = useMemo(
    () => ({
      schema: userSchema,
      defaultValues: {
        full_name: '',
        email: '',
        password: '',
        role: filterRole || 'student',
        document_id: '',
        enrollment_code: '',
        course_id: '',
        is_active: true
      },
      fields: [
        { name: 'full_name', label: 'Nombre completo' },
        { name: 'email', label: 'Correo', type: 'email' },
        { name: 'password', label: 'Contraseña', type: 'password' },
        {
          name: 'role',
          label: 'Rol',
          type: 'select',
          options: [
            { value: 'admin', label: 'Admin' },
            { value: 'teacher', label: 'Docente' },
            { value: 'student', label: 'Estudiante' }
          ],
          show: !filterRole ? (v) => true : undefined
        },
        { name: 'document_id', label: 'Documento', show: (v) => v.role !== 'admin' },
        { name: 'enrollment_code', label: 'Matrícula', show: (v) => v.role === 'student' },
        { name: 'course_id', label: 'Curso', type: 'select', options: courseOptions, show: (v) => v.role === 'student', valueAsNumber: true },
        { name: 'is_active', label: 'Cuenta activa', type: 'checkbox' }
      ]
    }),
    [courseOptions, filterRole]
  );

  return (
    <ResourcePage
      title={title}
      description={description}
      service={service}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Nombre', accessorKey: 'full_name' },
        { header: 'Correo', accessorKey: 'email' },
        { header: 'Rol', accessorKey: 'role', cell: roleCell },
        { header: 'Estado', accessorKey: 'is_active', cell: activeCell }
      ]}
      form={form}
    />
  );
}
