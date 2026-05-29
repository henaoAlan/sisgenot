import { Card, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { useAsync } from '../../hooks/useAsync';
import { auditService } from '../../services/resource.service';

export function AuditPage() {
  const { data, loading } = useAsync(() => auditService.list({ per_page: 100 }), []);
  const rows = data?.data || [];

  return (
    <Card>
      <CardHeader title="Auditoria de notas" description="Historial de cambios con trazabilidad academica." />
      {loading ? (
        <Skeleton className="h-96" />
      ) : (
        <DataTable
          data={rows}
          filename="auditoria.csv"
          columns={[
            { header: 'Accion', accessorKey: 'action', cell: ({ getValue }) => <Badge tone={getValue() === 'deleted' ? 'rose' : getValue() === 'created' ? 'green' : 'amber'}>{getValue()}</Badge> },
            { header: 'Estudiante', accessorFn: (row) => row.student?.user?.full_name || row.student_id },
            { header: 'Curso', accessorFn: (row) => row.course?.name || row.course_id },
            { header: 'Asignatura', accessorFn: (row) => row.subject?.name || row.subject_id },
            { header: 'Competencia', accessorKey: 'competency' },
            { header: 'Anterior', accessorKey: 'previous_grade' },
            { header: 'Nueva', accessorKey: 'new_grade' },
            { header: 'Fecha', accessorKey: 'created_at' }
          ]}
        />
      )}
    </Card>
  );
}
