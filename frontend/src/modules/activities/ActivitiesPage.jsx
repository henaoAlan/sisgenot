import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '../../components/ui/Button';
import { Card, CardHeader } from '../../components/ui/Card';
import { Modal } from '../../components/ui/Modal';
import { DataTable } from '../../components/tables/DataTable';
import { Skeleton } from '../../components/common/Skeleton';
import { EmptyState } from '../../components/common/EmptyState';
import { useAuth } from '../../contexts/AuthContext';
import { useAsync } from '../../hooks/useAsync';
import { activitiesService, assignmentsService, periodsService, studentsService } from '../../services/resource.service';
import { teacherStudentAssignmentService } from '../../services/teacherStudentAssignment.service';
import { activitySchema } from '../../validations/schemas';
import { ResourcePage } from '../shared/ResourcePage';

const assignmentLabel = (assignment) => {
  const course = assignment.course?.name || `Curso ${assignment.course_id}`;
  const subject = assignment.subject?.name || `Asignatura ${assignment.subject_id}`;
  const teacher = assignment.teacher?.user?.full_name || `Docente ${assignment.teacher_id}`;
  return `${course} - ${subject} - ${teacher}`;
};

function SubmissionFeedbackCell({ activityId, submission, onSaved }) {
  const [feedback, setFeedback] = useState(submission.teacher_feedback || '');
  const [saving, setSaving] = useState(false);

  const saveFeedback = async () => {
    if (!feedback.trim()) {
      toast.error('Escribe la correccion para guardar.');
      return;
    }

    try {
      setSaving(true);
      await activitiesService.saveSubmissionFeedback(activityId, submission.id, feedback.trim());
      toast.success('Correccion guardada.');
      onSaved?.();
    } catch (error) {
      toast.error(error.response?.data?.message || 'No fue posible guardar la correccion.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="flex min-w-72 gap-2">
      <textarea
        value={feedback}
        onChange={(event) => setFeedback(event.target.value)}
        className="min-h-20 flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
        placeholder="Correccion para el estudiante"
      />
      <Button className="h-9 self-start" loading={saving} onClick={saveFeedback}>
        Guardar
      </Button>
    </div>
  );
}

export function ActivitiesPage() {
  const { role, user } = useAuth();
  const teacherId = user?.teacher?.id || user?.teacher_profile?.id;
  const [selectedActivity, setSelectedActivity] = useState(null);
  const [selectedActivityReview, setSelectedActivityReview] = useState(null);
  const [submissions, setSubmissions] = useState([]);
  const [submissionsLoading, setSubmissionsLoading] = useState(false);
  const [file, setFile] = useState(null);
  const [comment, setComment] = useState('');
  const [uploading, setUploading] = useState(false);

  const { data: activitiesData, loading, error, refresh } = useAsync(() => activitiesService.list(), []);
  const { data: assignmentsData } = useAsync(
    () => (['admin', 'teacher'].includes(role) ? assignmentsService.list() : Promise.resolve([])),
    [role]
  );
  const { data: periodsData } = useAsync(() => periodsService.list(), []);
  const { data: studentsData } = useAsync(
    async () => {
      if (!['admin', 'teacher'].includes(role)) return Promise.resolve([]);
      if (role === 'teacher') {
        const [courseStudents, assignedStudents] = await Promise.all([
          studentsService.list({ per_page: 100 }).catch(() => []),
          teacherId ? teacherStudentAssignmentService.getStudentsByTeacher(teacherId).catch(() => []) : Promise.resolve([])
        ]);

        return Array.from(
          new Map(
            [...courseStudents, ...assignedStudents].map((student) => [student.id, student])
          ).values()
        );
      }
      return studentsService.list({ per_page: 100 });
    },
    [role, teacherId]
  );

  const assignmentOptions = useMemo(
    () => (Array.isArray(assignmentsData) ? assignmentsData : []).map((assignment) => ({
      value: assignment.id.toString(),
      label: assignmentLabel(assignment)
    })),
    [assignmentsData]
  );

  const periodOptions = useMemo(
    () => (Array.isArray(periodsData) ? periodsData : []).map((period) => ({
      value: period.id.toString(),
      label: `${period.name} - ${period.year}`
    })),
    [periodsData]
  );

  const studentOptions = useMemo(
    () => (Array.isArray(studentsData) ? studentsData : []).map((student) => ({
      value: student.id.toString(),
      label: `${student.user?.full_name || student.full_name || student.enrollment_code || `Estudiante ${student.id}`}${student.course?.name ? ` - ${student.course.name}` : ''}`,
      course_id: student.course?.id ?? student.course_id
    })),
    [studentsData]
  );

  const getStudentOptions = (values) => {
    const assignmentId = values?.teacher_assignment_id ? Number(values.teacher_assignment_id) : null;
    const selectedAssignment = Array.isArray(assignmentsData)
      ? assignmentsData.find((assignment) => Number(assignment.id) === assignmentId)
      : null;
    const selectedCourseId = selectedAssignment?.course_id ?? selectedAssignment?.course?.id;
    const courseStudents = selectedAssignment?.course?.students;

    const studentsFromAssignment = Array.isArray(courseStudents)
      ? courseStudents.map((student) => ({
          value: student.id.toString(),
          label: `${student.user?.full_name || student.full_name || student.enrollment_code || `Estudiante ${student.id}`}${selectedAssignment?.course?.name ? ` - ${selectedAssignment.course.name}` : ''}`,
          course_id: selectedCourseId
        }))
      : [];

    const studentsFromList = selectedCourseId
      ? studentOptions.filter((student) => Number(student.course_id) === Number(selectedCourseId))
      : [];

    const merged = Array.from(
      new Map(
        [...studentsFromAssignment, ...studentsFromList].map((student) => [student.value, student])
      ).values()
    );

    return merged.map(({ value, label }) => ({ value, label }));
  };

  const activities = useMemo(() => (Array.isArray(activitiesData) ? activitiesData : []), [activitiesData]);

  const loadSubmissions = async (activity) => {
    setSelectedActivityReview(activity);
    setSubmissions([]);
    setSubmissionsLoading(true);

    try {
      const result = await activitiesService.submissions(activity.id);
      setSubmissions(Array.isArray(result) ? result : result.data || []);
    } catch (error) {
      toast.error(error.response?.data?.message || 'No fue posible cargar las entregas.');
      setSelectedActivityReview(null);
    } finally {
      setSubmissionsLoading(false);
    }
  };

  const downloadSubmission = async (submission) => {
    try {
      const blob = await activitiesService.downloadSubmission(selectedActivityReview.id, submission.id);
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = submission.file_path?.split('/').pop() || `entrega-${submission.id}`;
      link.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error(error.response?.data?.message || 'No fue posible descargar el archivo.');
    }
  };

  const downloadActivityFile = async (activity) => {
    try {
      const blob = await activitiesService.downloadFile(activity.id);
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = activity.file_path?.split('/').pop() || `actividad-${activity.id}`;
      link.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error(error.response?.data?.message || 'No fue posible descargar el archivo.');
    }
  };

  const handleReviewActivity = async (activity) => {
    await loadSubmissions(activity);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!file) {
      toast.error('Selecciona un archivo para subir.');
      return;
    }

    if (!selectedActivity) {
      toast.error('Selecciona una actividad válida.');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);
    if (comment.trim()) {
      formData.append('comment', comment.trim());
    }

    try {
      setUploading(true);
      await activitiesService.submit(selectedActivity.id, formData);
      toast.success('Entrega enviada correctamente.');
      setSelectedActivity(null);
      setFile(null);
      setComment('');
      refresh();
    } catch (error) {
      toast.error(error.response?.data?.message || 'No fue posible subir la entrega.');
    } finally {
      setUploading(false);
    }
  };

  if (role === 'student') {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader
            title="Actividades"
            description="Consulta actividades asignadas a tu curso y sube tu entrega." 
          />
          {loading ? (
            <Skeleton className="h-80" />
          ) : error ? (
            <EmptyState title="No se pudieron cargar las actividades" description={error?.response?.data?.message || error?.message || 'Intenta recargar la pagina.'} />
          ) : (
            <DataTable
              data={activities}
              filename="actividades.csv"
              columns={[
                { header: 'Titulo', accessorKey: 'title' },
                { header: 'Asignacion', accessorFn: (row) => row.assignment ? assignmentLabel(row.assignment) : row.teacher_assignment_id },
                { header: 'Periodo', accessorFn: (row) => row.period?.name || row.period_id },
                { header: 'Momento', accessorKey: 'moment' },
                { header: 'Numero', accessorKey: 'activity_number' },
                { header: 'Entrega', accessorKey: 'due_date' },
                {
                  header: 'Archivo',
                  cell: ({ row }) => (
                    row.original.file_path ? (
                      <Button variant="secondary" className="h-8" onClick={() => downloadActivityFile(row.original)}>
                        Descargar
                      </Button>
                    ) : 'Sin archivo'
                  )
                },
                { header: 'Entregado', accessorFn: (row) => (row.submissions?.length ? 'Sí' : 'No') },
                {
                  header: 'Correccion',
                  accessorFn: (row) => row.submissions?.[0]?.teacher_feedback || 'Sin correccion'
                },
                {
                  header: 'Accion',
                  cell: ({ row }) => (
                    <Button
                      className="h-8"
                      onClick={() => setSelectedActivity(row.original)}
                    >
                      {row.original.submissions?.length ? 'Reenviar' : 'Subir entrega'}
                    </Button>
                  )
                }
              ]}
            />
          )}
        </Card>

        <Modal open={Boolean(selectedActivity)} title={selectedActivity ? `Subir entrega: ${selectedActivity.title}` : 'Subir entrega'} onClose={() => setSelectedActivity(null)}>
          {selectedActivity && (
            <form className="space-y-4" onSubmit={handleSubmit}>
              <div>
                <label className="block text-sm font-medium text-slate-700">Archivo</label>
                <input
                  type="file"
                  className="mt-2 block w-full rounded border border-slate-300 bg-white px-3 py-2"
                  onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">Comentario (opcional)</label>
                <textarea
                  value={comment}
                  onChange={(event) => setComment(event.target.value)}
                  className="mt-2 block w-full rounded border border-slate-300 bg-white px-3 py-2"
                  rows={4}
                />
              </div>
              <div className="flex justify-end gap-2">
                <Button variant="secondary" type="button" onClick={() => setSelectedActivity(null)}>
                  Cancelar
                </Button>
                <Button type="submit" loading={uploading}>
                  Subir entrega
                </Button>
              </div>
            </form>
          )}
        </Modal>
      </div>
    );
  }

  return (
    <>
      <ResourcePage
      title="Actividades"
      description="Crea y consulta actividades academicas por asignacion y periodo."
      service={activitiesService}
      canWrite={['admin', 'teacher'].includes(role)}
      columns={[
        { header: 'Titulo', accessorKey: 'title' },
        { header: 'Asignacion', accessorFn: (row) => row.assignment ? assignmentLabel(row.assignment) : row.teacher_assignment_id },
        { header: 'Estudiante', accessorFn: (row) => row.student ? row.student.user?.full_name : 'Todos' },
        { header: 'Periodo', accessorFn: (row) => row.period?.name || row.period_id },
        { header: 'Momento', accessorKey: 'moment' },
        { header: 'Numero', accessorKey: 'activity_number' },
        { header: 'Entrega', accessorKey: 'due_date' },
        {
          header: 'Archivo',
          cell: ({ row }) => (
            row.original.file_path ? (
              <Button variant="secondary" className="h-8" onClick={() => downloadActivityFile(row.original)}>
                Descargar
              </Button>
            ) : 'Sin archivo'
          )
        },
        { header: 'Calificaciones', accessorKey: 'grades_count' },
        { header: 'Entregas', accessorKey: 'submissions_count' },
        {
          header: 'Revisar',
          cell: ({ row }) => (
            <Button variant="secondary" className="h-8" onClick={() => handleReviewActivity(row.original)}>
              Ver entregas
            </Button>
          )
        }
      ]}
      form={{
        schema: activitySchema,
        defaultValues: {
          teacher_assignment_id: '',
          period_id: '',
          student_id: '',
          moment: 1,
          activity_number: 1,
          title: '',
          description: '',
          due_date: '',
          file: '',
          is_recovery: false
        },
        fields: [
          { name: 'teacher_assignment_id', label: 'Asignacion', type: 'select', options: assignmentOptions, valueAsNumber: true, clearFieldsOnChange: ['student_id'] },
          { name: 'period_id', label: 'Periodo', type: 'select', options: periodOptions, valueAsNumber: true },
          { name: 'student_id', label: 'Estudiante (opcional)', type: 'select', options: getStudentOptions, valueAsNumber: true, show: () => ['admin', 'teacher'].includes(role) },
          { name: 'moment', label: 'Momento', type: 'select', options: [
              { value: 1, label: '1' },
              { value: 2, label: '2' },
              { value: 3, label: '3' }
            ], valueAsNumber: true },
          { name: 'activity_number', label: 'Actividad numero', type: 'number', valueAsNumber: true },
          { name: 'title', label: 'Titulo' },
          { name: 'description', label: 'Descripcion' },
          { name: 'due_date', label: 'Fecha limite', type: 'date' },
          { name: 'file', label: 'Archivo de la actividad', type: 'file' },
          { name: 'is_recovery', label: 'Actividad de recuperacion', type: 'checkbox' }
        ]
      }}
      createTitle="Nueva actividad"
    />

      <Modal
        open={Boolean(selectedActivityReview)}
        title={selectedActivityReview ? `Entregas de ${selectedActivityReview.title}` : 'Entregas'}
        onClose={() => setSelectedActivityReview(null)}
      >
        {submissionsLoading ? (
          <Skeleton className="h-80" />
        ) : submissions.length ? (
          <DataTable
            data={submissions}
            filename="entregas.csv"
            columns={[
              {
                header: 'Estudiante',
                accessorFn: (row) => row.student?.user?.full_name || row.student?.enrollment_code || `Estudiante ${row.student_id}`
              },
              { header: 'Comentario', accessorKey: 'comment' },
              {
                header: 'Fecha',
                accessorFn: (row) => row.submitted_at ? new Date(row.submitted_at).toLocaleString() : ''
              },
              {
                header: 'Correccion',
                cell: ({ row }) => (
                  <SubmissionFeedbackCell
                    activityId={selectedActivityReview.id}
                    submission={row.original}
                    onSaved={() => loadSubmissions(selectedActivityReview)}
                  />
                )
              },
              {
                header: 'Archivo',
                accessorFn: (row) => row.file_path?.split('/').pop() || 'Sin archivo'
              },
              {
                header: 'Accion',
                cell: ({ row }) => (
                  row.original.file_path ? (
                    <Button className="h-8" onClick={() => downloadSubmission(row.original)}>
                      Descargar
                    </Button>
                  ) : null
                )
              }
            ]}
          />
        ) : (
          <EmptyState title="No hay entregas" description="Esta actividad no tiene entregas registradas." />
        )}
      </Modal>
    </>
  );
}
