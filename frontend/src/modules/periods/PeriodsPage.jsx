import { useMemo } from 'react';
import { toast } from 'sonner';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { useAuth } from '../../contexts/AuthContext';
import { periodsService } from '../../services/resource.service';
import { periodSchema } from '../../validations/schemas';
import { ResourcePage } from '../shared/ResourcePage';
import { useAsync } from '../../hooks/useAsync';

export function PeriodsPage() {
  const { role } = useAuth();
  const { data: periodsData } = useAsync(() => periodsService.list(), []);
  const currentYear = new Date().getFullYear();

  const defaultOrdering = useMemo(() => {
    const periods = Array.isArray(periodsData) ? periodsData : [];
    const sameYearOrders = periods.filter((period) => period.year === currentYear).map((period) => period.ordering);
    return sameYearOrders.length > 0 ? Math.min(Math.max(...sameYearOrders) + 1, 10) : 1;
  }, [periodsData, currentYear]);

  return (
    <ResourcePage
      title="Periodos"
      description="Controla apertura y cierre de periodos academicos."
      service={periodsService}
      canWrite={role === 'admin'}
      columns={[
        { header: 'Nombre', accessorKey: 'name' },
        { header: 'Anio', accessorKey: 'year' },
        { header: 'Orden', accessorKey: 'ordering' },
        { header: 'Notas', accessorKey: 'grades_count' },
        { header: 'Estado', accessorKey: 'is_open', cell: ({ getValue }) => <Badge tone={getValue() ? 'green' : 'amber'}>{getValue() ? 'Abierto' : 'Cerrado'}</Badge> },
        ...(role === 'admin'
          ? [
              {
                header: 'Cambiar estado',
                cell: ({ row }) => (
                  <Button
                    variant="secondary"
                    className="h-8"
                    onClick={async () => {
                      await periodsService.toggle(row.original.id);
                      toast.success('Estado actualizado. Recarga para ver cambios.');
                    }}
                  >
                    Alternar
                  </Button>
                )
              }
            ]
          : [])
      ]}
      form={{
        schema: periodSchema,
        defaultValues: { name: '', year: currentYear, ordering: defaultOrdering, is_open: false },
        fields: [
          { name: 'name', label: 'Nombre' },
          { name: 'year', label: 'Anio', type: 'number' },
          { name: 'ordering', label: 'Orden', type: 'number' },
          { name: 'is_open', label: 'Periodo abierto', type: 'checkbox' }
        ]
      }}
    />
  );
}
