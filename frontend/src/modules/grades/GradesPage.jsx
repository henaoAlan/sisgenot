import { Pencil, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '../../components/ui/Button';
import { Card, CardHeader } from '../../components/ui/Card';
import { Badge } from '../../components/ui/Badge';
import { Modal } from '../../components/ui/Modal';
import { FormBuilder } from '../../components/forms/FormBuilder';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { gradesService, assignmentsService, coursesService, subjectsService, periodsService, studentsService } from '../../services/resource.service';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';
import { gradeSchema } from '../../validations/schemas';

const competencyTone = { ser: 'green', saber: 'cyan', hacer: 'amber' };

export function GradesPage() {
  const { role, user } = useAuth();
  const canWrite = ['admin', 'teacher'].includes(role);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const { data, loading, refresh } = useAsync(async () => {
    if (role === 'student' && user?.student_profile?.id) {
      const result = await gradesService.byStudent(user.student_profile.id);
      return { data: result.grades, summary: result.summary };
    }
    return gradesService.list();
  }, [role, user?.student_profile?.id]);

  const rows = useMemo(() => data?.data || data || [], [data]);
  const average = useMemo(() => (rows.length ? (rows.reduce((acc, item) => acc + Number(item.grade || 0), 0) / rows.length).toFixed(2) : '0.00'), [rows]);

  const submit = async (payload) => {
    if (editing?.id) {
      await gradesService.update(editing.id, payload);
      toast.success('Nota actualizada.');
    } else {
      await gradesService.create(payload);
      toast.success('Nota registrada.');
    }
    setModalOpen(false);
    setEditing(null);
    refresh();
  };

  const teacherId = user?.teacher?.id || user?.teacher_profile?.id;

  const { data: studentsData } = useAsync(
    () => {
      if (!canWrite) return Promise.resolve([]);
      if (role === 'teacher' && teacherId) return teacherStudentAssignmentService.getStudentsByTeacher(teacherId);
      return studentsService.list({ per_page: 100 });
    },
    [canWrite, role, teacherId]
  );

  const { data: assignmentsData } = useAsync(
    () => (role === 'teacher' ? assignmentsService.list() : Promise.resolve([])),
    [role]
  );

  const { data: coursesData } = useAsync(() => coursesService.list(), []);
  const { data: subjectsData } = useAsync(() => subjectsService.list(), []);
  const { data: periodsData } = useAsync(() => periodsService.list(), []);

  const studentOptions = useMemo(
    () =>
      (Array.isArray(studentsData) ? studentsData : []).map((student) => ({
        value: student.id,
        label: `${student.user?.full_name || `Estudiante ${student.id}`} - ${student.course?.name || 'Sin curso'}`
      })),
    [studentsData]
  );

  const courseOptions = useMemo(() => {
    if (role === 'teacher' && Array.isArray(assignmentsData)) {
      const uniqueCourses = Array.from(
        new Map(
          assignmentsData
            .map((assignment) => assignment.course)
            .filter(Boolean)
            .map((course) => [course.id, course])
        ).values()
      );
      return uniqueCourses.map((course) => ({
        value: course.id,
        label: `${course.name} - Año ${course.year}`
      }));
    }

    return (Array.isArray(coursesData) ? coursesData : []).map((course) => ({
      value: course.id,
      label: `${course.name} - Año ${course.year}`
    }));
  }, [role, assignmentsData, coursesData]);

  const subjectOptions = useMemo(() => {
    if (role === 'teacher' && Array.isArray(assignmentsData)) {
      const uniqueSubjects = Array.from(
        new Map(
          assignmentsData
            .map((assignment) => assignment.subject)
            .filter(Boolean)
            .map((subject) => [subject.id, subject])
        ).values()
      );
      return uniqueSubjects.map((subject) => ({
        value: subject.id,
        label: subject.name
      }));
    }

    return (Array.isArray(subjectsData) ? subjectsData : []).map((subject) => ({
      value: subject.id,
      label: subject.name
    }));
  }, [role, assignmentsData, subjectsData]);

  const periodOptions = useMemo(
    () =>
      (Array.isArray(periodsData) ? periodsData : []).map((period) => ({
        value: period.id,
        label: `${period.name} - ${period.year}`
      })),
    [periodsData]
  );

  const columns = [
    { header: 'Estudiante', accessorFn: (row) => row.student?.user?.full_name || row.student_id },
    { header: 'Curso', accessorFn: (row) => row.course?.name || row.course_id },
    { header: 'Asignatura', accessorFn: (row) => row.subject?.name || row.subject_id },
    { header: 'Periodo', accessorFn: (row) => row.period?.name || row.period_id },
    { header: 'Competencia', accessorKey: 'competency', cell: ({ getValue }) => <Badge tone={competencyTone[getValue()] || 'slate'}>{getValue()}</Badge> },
    { header: 'Nota', accessorKey: 'grade', cell: ({ getValue }) => <span className="font-semibold">{Number(getValue()).toFixed(2)}</span> },
    ...(canWrite
      ? [
          {
            header: 'Editar',
            cell: ({ row }) => (
              <Button className="h-8 w-8 px-0" variant="secondary" onClick={() => { setEditing(row.original); setModalOpen(true); }}>
                <Pencil className="h-4 w-4" />
              </Button>
            )
          }
        ]
      : [])
  ];

  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-3">
        <Card><CardHeader title="Promedio visible" description={average} /></Card>
        <Card><CardHeader title="Registros" description={`${rows.length} notas`} /></Card>
        <Card><CardHeader title="Competencias" description="Ser, Saber, Hacer" /></Card>
      </div>
      <Card>
        <CardHeader
          title="Notas"
          description="Registro, edicion y consulta de calificaciones."
          action={canWrite && <Button onClick={() => { setEditing(null); setModalOpen(true); }}><Plus className="h-4 w-4" />Registrar</Button>}
        />
        {loading ? <Skeleton className="h-96" /> : <DataTable data={rows} columns={columns} filename="notas.csv" />}
      </Card>
      <Modal open={modalOpen} title={editing ? 'Editar nota' : 'Registrar nota'} onClose={() => setModalOpen(false)}>
        <FormBuilder
          schema={gradeSchema}
          defaultValues={{
            student_id: editing?.student_id || '',
            course_id: editing?.course_id || '',
            subject_id: editing?.subject_id || '',
            period_id: editing?.period_id || '',
            competency: editing?.competency || 'ser',
            grade: editing?.grade || ''
          }}
          fields={[
            { name: 'student_id', label: 'Estudiante', type: 'select', options: studentOptions, valueAsNumber: true },
            { name: 'course_id', label: 'Curso', type: 'select', options: courseOptions, valueAsNumber: true },
            { name: 'subject_id', label: 'Asignatura', type: 'select', options: subjectOptions, valueAsNumber: true },
            { name: 'period_id', label: 'Periodo', type: 'select', options: periodOptions, valueAsNumber: true },
            { name: 'competency', label: 'Competencia', type: 'select', options: [{ value: 'ser', label: 'Ser' }, { value: 'saber', label: 'Saber' }, { value: 'hacer', label: 'Hacer' }] },
            {
              name: 'grade',
              label: 'Nota',
              type: 'text',
              inputMode: 'decimal',
              pattern: '^[0-9]+(\.[0-9]+)?$',
              placeholder: '3.5'
            }
          ]}
          onSubmit={submit}
        />
      </Modal>
    </div>
  );
}
