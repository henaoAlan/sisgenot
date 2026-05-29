import { useMemo, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardHeader } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { useAsync } from '../../hooks/useAsync';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { EmptyState } from '../../components/common/EmptyState';

export function TeacherStudentAssignmentPage() {
  const [selectedTeacher, setSelectedTeacher] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState(null);

  // Cargar docentes
  const { data: teachers = [], loading: loadingTeachers, refresh: refreshTeachers } = useAsync(
    () => teacherStudentAssignmentService.getTeachers(),
    []
  );

  // Cargar estudiantes
  const { data: allStudents = [], loading: loadingStudents } = useAsync(
    () => teacherStudentAssignmentService.getStudents(),
    []
  );

  // Cargar estudiantes asignados al docente seleccionado
  const { data: assignedStudents = [], loading: loadingAssigned, refresh: refreshAssigned } = useAsync(
    () => (selectedTeacher ? teacherStudentAssignmentService.getStudentsByTeacher(selectedTeacher.id) : Promise.resolve([])),
    [selectedTeacher]
  );

  // Estudiantes disponibles (no asignados al docente actual)
  const availableStudents = useMemo(() => {
    if (!selectedTeacher) return [];
    const assignedIds = assignedStudents.map(s => s.id);
    return allStudents.filter(s => !assignedIds.includes(s.id));
  }, [selectedTeacher, assignedStudents, allStudents]);

  const handleAssign = async () => {
    if (!selectedTeacher || !selectedStudent) {
      toast.error('Selecciona un docente y un estudiante');
      return;
    }

    try {
      await teacherStudentAssignmentService.assign(selectedTeacher.id, selectedStudent);
      toast.success('Estudiante asignado exitosamente');
      setSelectedStudent(null);
      setModalOpen(false);
      refreshAssigned();
    } catch (error) {
      const errorMessage = error.response?.data?.message || error.message || 'Error al asignar';
      toast.error(errorMessage);
    }
  };

  const handleUnassign = async (studentId) => {
    if (!selectedTeacher) return;

    try {
      await teacherStudentAssignmentService.unassign(selectedTeacher.id, studentId);
      toast.success('Estudiante desasignado');
      refreshAssigned();
    } catch (error) {
      toast.error('Error al desasignar');
    }
  };

  const columns = [
    {
      accessorKey: 'user.full_name',
      header: 'Nombre',
      cell: ({ row }) => row.original.user?.full_name || 'N/A'
    },
    {
      accessorKey: 'user.email',
      header: 'Correo',
      cell: ({ row }) => row.original.user?.email || 'N/A'
    },
    {
      accessorKey: 'document_id',
      header: 'Documento',
    },
    {
      accessorKey: 'enrollment_code',
      header: 'Matrícula',
    },
    {
      accessorKey: 'course.name',
      header: 'Curso',
      cell: ({ row }) => row.original.course?.name || 'Sin curso'
    },
    {
      header: 'Acciones',
      cell: ({ row }) => (
        <Button
          variant="danger"
          className="h-8 w-8 px-0"
          onClick={() => handleUnassign(row.original.id)}
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      )
    }
  ];

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader
          title="Asignar Estudiantes a Docentes"
          description="Gestiona qué estudiantes son asignados a cada docente"
        />

        <div className="p-6 border-t space-y-6">
          {/* Selector de docente */}
          <div>
            <label className="text-sm font-medium text-slate-700 dark:text-slate-200 block mb-2">
              Selecciona un Docente
            </label>
            {loadingTeachers ? (
              <Skeleton className="h-10" />
            ) : (
              <select
                value={selectedTeacher?.id || ''}
                onChange={(e) => {
                  const teacherId = parseInt(e.target.value);
                  const teacher = teachers.find(t => t.id === teacherId);
                  setSelectedTeacher(teacher || null);
                }}
                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
              >
                <option value="">-- Selecciona un docente --</option>
                {teachers.map(teacher => (
                  <option key={teacher.id} value={teacher.id}>
                    {teacher.user?.full_name} ({teacher.user?.email})
                  </option>
                ))}
              </select>
            )}
          </div>

          {/* Lista de estudiantes asignados */}
          {selectedTeacher && (
            <>
              <div className="flex items-center justify-between">
                <h3 className="font-semibold text-slate-900 dark:text-white">
                  Estudiantes Asignados a {selectedTeacher.user?.full_name}
                </h3>
                <Button onClick={() => setModalOpen(true)}>
                  <Plus className="h-4 w-4" />
                  Asignar Estudiante
                </Button>
              </div>

              {loadingAssigned ? (
                <Skeleton className="h-80" />
              ) : assignedStudents.length === 0 ? (
                <EmptyState
                  title="Sin estudiantes asignados"
                  description="Este docente no tiene estudiantes asignados aún"
                />
              ) : (
                <DataTable data={assignedStudents} columns={columns} />
              )}
            </>
          )}
        </div>
      </Card>

      {/* Modal para asignar estudiante */}
      <Modal
        open={modalOpen}
        title="Asignar Estudiante"
        onClose={() => {
          setModalOpen(false);
          setSelectedStudent(null);
        }}
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-slate-700 dark:text-slate-200 block mb-2">
              Selecciona un Estudiante
            </label>
            {loadingStudents ? (
              <Skeleton className="h-10" />
            ) : (
              <select
                value={selectedStudent || ''}
                onChange={(e) => setSelectedStudent(parseInt(e.target.value))}
                className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
              >
                <option value="">-- Selecciona un estudiante --</option>
                {availableStudents.map(student => (
                  <option key={student.id} value={student.id}>
                    {student.user?.full_name} - {student.enrollment_code}
                  </option>
                ))}
              </select>
            )}
            {availableStudents.length === 0 && (
              <p className="text-sm text-slate-500 mt-2">
                Todos los estudiantes ya están asignados a este docente
              </p>
            )}
          </div>

          <div className="flex gap-3 justify-end">
            <Button
              variant="secondary"
              onClick={() => {
                setModalOpen(false);
                setSelectedStudent(null);
              }}
            >
              Cancelar
            </Button>
            <Button onClick={handleAssign} disabled={!selectedStudent || availableStudents.length === 0}>
              Asignar
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
