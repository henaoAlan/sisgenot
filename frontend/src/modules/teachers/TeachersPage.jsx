import { useNavigate } from 'react-router-dom';
import { Eye } from 'lucide-react';
import { usersService } from '../../services/resource.service';
import { userSchema } from '../../validations/schemas';
import { ResourcePage, roleCell, activeCell } from '../shared/ResourcePage';
import { Button } from '../../components/ui/Button';

export function TeachersPage() {
  const navigate = useNavigate();
  const columns = [
    {
      accessorKey: 'full_name',
      header: 'Nombre',
    },
    {
      accessorKey: 'email',
      header: 'Correo',
    },
    {
      accessorKey: 'role',
      header: 'Rol',
      cell: roleCell,
    },
    {
      accessorKey: 'is_active',
      header: 'Estado',
      cell: activeCell,
    },
    {
      header: 'Estudiantes',
      cell: ({ row }) => (
        <Button
          variant="secondary"
          className="h-8 px-3 text-xs"
          onClick={() => navigate(`/app/teachers/${row.original.id}`)}
        >
          <Eye className="h-4 w-4 mr-1" />
          Ver
        </Button>
      ),
    },
  ];

  return (
    <ResourcePage
      title="Docentes"
      description="Lista de docentes registrados en el sistema."
      service={{
        ...usersService,
        list: async (params = {}) => usersService.list({ per_page: 1000, ...params, role: 'teacher' })
      }}
      mapRows={(rows) => rows.map((user) => ({
        ...user,
        document_id: user.teacher?.document_id || user.document_id || '',
        role: 'teacher'
      }))}
      columns={columns}
      canWrite={true}
      form={{
        schema: userSchema,
        defaultValues: {
          full_name: '',
          email: '',
          password: '',
          role: 'teacher',
          document_id: '',
          enrollment_code: '',
          course_id: '',
          is_active: true
        },
        fields: [
          { name: 'full_name', label: 'Nombre completo' },
          { name: 'email', label: 'Correo', type: 'email' },
          { name: 'password', label: 'Contrasena', type: 'password' },
          { name: 'role', label: 'Rol', type: 'select', options: [{ value: 'teacher', label: 'Docente' }] },
          { name: 'document_id', label: 'Documento' },
          { name: 'is_active', label: 'Cuenta activa', type: 'checkbox' }
        ]
      }}
    />
  );
}
