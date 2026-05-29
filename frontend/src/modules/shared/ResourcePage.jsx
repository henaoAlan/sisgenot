import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '../../components/ui/Button';
import { Card, CardHeader } from '../../components/ui/Card';
import { Modal } from '../../components/ui/Modal';
import { Badge } from '../../components/ui/Badge';
import { FormBuilder } from '../../components/forms/FormBuilder';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { EmptyState } from '../../components/common/EmptyState';
import { useAsync } from '../../hooks/useAsync';

export function ResourcePage({ title, description, service, columns, form, mapRows = (rows) => rows, getList = (data) => data?.data || data || [], listParams, canWrite = false, createTitle = 'Nuevo registro' }) {
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const params = useMemo(() => {
    const resolved = typeof listParams === 'function' ? listParams() : listParams;
    return resolved ?? {};
  }, [listParams]);
  const { data, loading, error, refresh } = useAsync(() => service.list(params), [service, params]);
  const rows = useMemo(() => mapRows(getList(data)), [data, getList, mapRows]);
  const errorMessage = error?.response?.data?.message || error?.message || 'No fue posible cargar la informacion.';

  const tableColumns = [
    ...columns,
    ...(canWrite
      ? [
          {
            header: 'Acciones',
            cell: ({ row }) => (
              <div className="flex gap-2">
                <Button variant="secondary" className="h-8 w-8 px-0" onClick={() => { setEditing(row.original); setModalOpen(true); }}>
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button
                  variant="danger"
                  className="h-8 w-8 px-0"
                  onClick={async () => {
                    await service.remove(row.original.id);
                    toast.success('Registro eliminado.');
                    refresh();
                  }}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            )
          }
        ]
      : [])
  ];

  const submit = async (payload) => {
    try {
      // Filtrar valores vacíos, excepto password en creación (que es obligatorio)
      const clean = Object.fromEntries(
        Object.entries(payload).filter(([key, value]) => {
          // En creación nueva: incluir password aunque esté vacío (para que el backend valide)
          // En edición: filtrar password vacío
          if (key === 'password' && !editing?.id) {
            return true; // Incluir password en creación para validación del backend
          }
          return value !== '' && value !== undefined;
        })
      );

      // Convertir course_id a número si existe y es string
      if (clean.course_id && typeof clean.course_id === 'string') {
        clean.course_id = parseInt(clean.course_id, 10);
      }

      if (editing?.id) {
        await service.update(editing.id, clean);
        toast.success('Registro actualizado.');
      } else {
        await service.create(clean);
        toast.success('Registro creado.');
      }
      setModalOpen(false);
      setEditing(null);
      refresh();
    } catch (error) {
      const validationErrors = error.response?.data?.errors;
      const errorMessage = validationErrors
        ? Object.values(validationErrors).flat().join(' ')
        : error.response?.data?.message || error.message || 'Error al guardar.';
      toast.error(errorMessage);
    }
  };

  return (
    <Card>
      <CardHeader
        title={title}
        description={description}
        action={
          canWrite && (
            <Button onClick={() => { setEditing(null); setModalOpen(true); }}>
              <Plus className="h-4 w-4" />
              Crear
            </Button>
          )
        }
      />
      {loading ? (
        <Skeleton className="h-80" />
      ) : error ? (
        <EmptyState title="No se pudieron cargar los datos" description={errorMessage} />
      ) : (
        <DataTable data={rows} columns={tableColumns} filename={`${title.toLowerCase()}.csv`} />
      )}
      <Modal open={modalOpen} title={editing ? 'Editar registro' : createTitle} onClose={() => setModalOpen(false)}>
        {form && <FormBuilder {...form} defaultValues={{ ...form.defaultValues, ...editing }} onSubmit={submit} />}
      </Modal>
    </Card>
  );
}

export const roleCell = ({ getValue }) => <Badge tone={getValue() === 'admin' ? 'rose' : getValue() === 'teacher' ? 'cyan' : 'green'}>{getValue()}</Badge>;
export const activeCell = ({ getValue }) => <Badge tone={getValue() ? 'green' : 'slate'}>{getValue() ? 'Activo' : 'Inactivo'}</Badge>;
