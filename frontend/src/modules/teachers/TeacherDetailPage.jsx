import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardHeader } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Modal } from '../../components/ui/Modal';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { EmptyState } from '../../components/common/EmptyState';
import { useAsync } from '../../hooks/useAsync';
import { assignmentsService, coursesService, subjectsService, usersService } from '../../services/resource.service';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';
import { useMemo, useState } from 'react';

export function TeacherDetailPage() {
  const { userId } = useParams();
  const navigate = useNavigate();
  const [modalOpen, setModalOpen] = useState(false);
  const [assignmentModalOpen, setAssignmentModalOpen] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [selectedCourse, setSelectedCourse] = useState('');
  const [selectedSubject, setSelectedSubject] = useState('');

  // Cargar datos del docente
  const { data: user, loading: loadingTeacher } = useAsync(
    () => usersService.get(userId),
    [userId]
  );

  const teacherId = user?.teacher?.id;

  const { data: teacherAssignments = [], loading: loadingAssignments, refresh: refreshAssignments } = useAsync(
    () => (teacherId ? assignmentsService.list({ teacher_id: teacherId }) : Promise.resolve([])),
    [teacherId]
  );

  const { data: coursesData = [] } = useAsync(() => coursesService.list(), []);
  const { data: subjectsData = [] } = useAsync(() => subjectsService.list(), []);

  // Cargar estudiantes del docente
  const { data: assignedStudents = [], loading: loadingStudents, refresh: refreshStudents } = useAsync(
    () => (teacherId ? teacherStudentAssignmentService.getStudentsByTeacher(teacherId) : Promise.resolve([])),
    [teacherId]
  );

  // Cargar todos los estudiantes para el modal
  const { data: allStudents = [], loading: loadingAllStudents } = useAsync(
    () => teacherStudentAssignmentService.getStudents(),
    []
  );

  // Estudiantes disponibles para asignar
  const availableStudents = (allStudents || []).filter(
    s => !(assignedStudents || []).map(a => a.id).includes(s.id)
  );

  const availableSubjects = useMemo(
    () =>
      (Array.isArray(subjectsData) ? subjectsData : []).filter((subject) => {
        if (!selectedCourse) return false;
        return Number(subject.course_id ?? subject.course?.id) === Number(selectedCourse);
      }),
    [subjectsData, selectedCourse]
  );

  const handleAssignCourse = async () => {
    if (!teacherId || !selectedCourse || !selectedSubject) {
      toast.error('Selecciona curso y asignatura');
      return;
    }

    try {
      await assignmentsService.create({
        teacher_id: teacherId,
        course_id: Number(selectedCourse),
        subject_id: Number(selectedSubject)
      });
      toast.success('Curso asignado al docente');
      setSelectedCourse('');
      setSelectedSubject('');
      setAssignmentModalOpen(false);
      refreshAssignments();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Error al asignar curso');
    }
  };

  const handleRemoveAssignment = async (assignmentId) => {
    try {
      await assignmentsService.remove(assignmentId);
      toast.success('Asignacion eliminada');
      refreshAssignments();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Error al eliminar asignacion');
    }
  };

  const handleAssign = async () => {
    if (!selectedStudent || !teacherId) {
      toast.error('Selecciona un estudiante');
      return;
    }

    try {
      await teacherStudentAssignmentService.assign(teacherId, selectedStudent);
      toast.success('Estudiante asignado exitosamente');
      setSelectedStudent(null);
      setModalOpen(false);
      refreshStudents();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Error al asignar');
    }
  };

  const handleUnassign = async (studentId) => {
    if (!teacherId) return;

    try {
      await teacherStudentAssignmentService.unassign(teacherId, studentId);
      toast.success('Estudiante desasignado');
      refreshStudents();
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

  const assignmentColumns = [
    {
      header: 'Curso',
      accessorFn: (row) => row.course?.name || row.course_id
    },
    {
      header: 'Asignatura',
      accessorFn: (row) => row.subject?.name || row.subject_id
    },
    {
      header: 'Acciones',
      cell: ({ row }) => (
        <Button
          variant="danger"
          className="h-8 w-8 px-0"
          onClick={() => handleRemoveAssignment(row.original.id)}
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      )
    }
  ];

  if (loadingTeacher) {
    return <Skeleton className="h-screen" />;
  }

  if (!user) {
    return (
      <EmptyState
        title="Docente no encontrado"
        description="El docente solicitado no existe"
      />
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button
          variant="secondary"
          className="h-10 w-10 px-0"
          onClick={() => navigate(-1)}
        >
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div>
          <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
            {user.full_name}
          </h1>
          <p className="text-slate-600 dark:text-slate-400">{user.email}</p>
        </div>
      </div>

      <Card>
        <CardHeader
          title="Cursos que imparte"
          description="Cursos y asignaturas asignados a este docente"
          action={
            <Button onClick={() => setAssignmentModalOpen(true)}>
              <Plus className="h-4 w-4" />
              Asignar Curso
            </Button>
          }
        />

        <div className="border-t">
          {loadingAssignments ? (
            <Skeleton className="h-64" />
          ) : teacherAssignments.length === 0 ? (
            <EmptyState
              title="Sin cursos asignados"
              description="Este docente no tiene cursos ni asignaturas asignadas"
            />
          ) : (
            <DataTable data={teacherAssignments} columns={assignmentColumns} filename="asignaciones-docente.csv" />
          )}
        </div>
      </Card>

      {/* Card de estudiantes asignados */}
      <Card>
        <CardHeader
          title="Estudiantes Asignados"
          description="Lista de estudiantes asignados a este docente"
          action={
            <Button onClick={() => setModalOpen(true)}>
              <Plus className="h-4 w-4" />
              Asignar Estudiante
            </Button>
          }
        />

        <div className="border-t">
          {loadingStudents ? (
            <Skeleton className="h-80" />
          ) : assignedStudents.length === 0 ? (
            <EmptyState
              title="Sin estudiantes asignados"
              description="Este docente no tiene estudiantes asignados aún"
            />
          ) : (
            <DataTable data={assignedStudents} columns={columns} />
          )}
        </div>
      </Card>

      {/* Modal para asignar */}
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
            {loadingAllStudents ? (
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

      <Modal
        open={assignmentModalOpen}
        title="Asignar curso al docente"
        onClose={() => {
          setAssignmentModalOpen(false);
          setSelectedCourse('');
          setSelectedSubject('');
        }}
      >
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-slate-700 dark:text-slate-200 block mb-2">
              Curso
            </label>
            <select
              value={selectedCourse}
              onChange={(e) => {
                setSelectedCourse(e.target.value);
                setSelectedSubject('');
              }}
              className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
            >
              <option value="">-- Selecciona un curso --</option>
              {(Array.isArray(coursesData) ? coursesData : []).map((course) => (
                <option key={course.id} value={course.id}>
                  {course.name} - Ano {course.year}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-sm font-medium text-slate-700 dark:text-slate-200 block mb-2">
              Asignatura
            </label>
            <select
              value={selectedSubject}
              onChange={(e) => setSelectedSubject(e.target.value)}
              disabled={!selectedCourse}
              className="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-100 dark:border-slate-700 dark:bg-slate-900"
            >
              <option value="">-- Selecciona una asignatura --</option>
              {availableSubjects.map((subject) => (
                <option key={subject.id} value={subject.id}>
                  {subject.name}
                </option>
              ))}
            </select>
            {selectedCourse && availableSubjects.length === 0 && (
              <p className="text-sm text-slate-500 mt-2">
                Este curso no tiene asignaturas vinculadas.
              </p>
            )}
          </div>

          <div className="flex gap-3 justify-end">
            <Button
              variant="secondary"
              onClick={() => {
                setAssignmentModalOpen(false);
                setSelectedCourse('');
                setSelectedSubject('');
              }}
            >
              Cancelar
            </Button>
            <Button onClick={handleAssignCourse} disabled={!selectedCourse || !selectedSubject}>
              Asignar
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
