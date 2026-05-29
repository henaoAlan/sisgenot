import { useMemo, useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { toast } from 'sonner';
import { Download } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Card, CardHeader } from '../../components/ui/Card';
import { Select } from '../../components/ui/Select';
import { DataTable } from '../../components/tables/DataTable';
import { EmptyState } from '../../components/common/EmptyState';
import { gradesService, assignmentsService, coursesService, periodsService, subjectsService } from '../../services/resource.service';
import { useAsync } from '../../hooks/useAsync';

export function ReportsPage() {
  const { role } = useAuth();
  const [filters, setFilters] = useState({ courseId: '', periodId: '', subjectId: '' });
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(false);
  const { data: coursesData } = useAsync(() => coursesService.list(), []);
  const { data: periodsData } = useAsync(() => periodsService.list(), []);
  const { data: subjectsData } = useAsync(() => subjectsService.list(), []);
  const { data: assignmentsData } = useAsync(
    () => (role === 'teacher' && filters.courseId ? assignmentsService.list({ course_id: filters.courseId }) : Promise.resolve([])),
    [filters.courseId, role]
  );

  const courseOptions = useMemo(
    () =>
      (Array.isArray(coursesData) ? coursesData : []).map((course) => ({
        value: course.id,
        label: course.year ? `${course.name} - Año ${course.year}` : course.name
      })),
    [coursesData]
  );

  const periodOptions = useMemo(
    () =>
      (Array.isArray(periodsData) ? periodsData : []).map((period) => ({
        value: period.id,
        label: period.year ? `${period.name} - ${period.year}` : period.name
      })),
    [periodsData]
  );

  const subjectOptions = useMemo(() => {
    if (!filters.courseId) {
      return (Array.isArray(subjectsData) ? subjectsData : []).map((subject) => ({
        value: subject.id,
        label: subject.name
      }));
    }

    if (role === 'teacher') {
      const assignments = Array.isArray(assignmentsData) ? assignmentsData : [];
      const subjects = assignments
        .map((assignment) => assignment.subject)
        .filter(Boolean);

      const uniqueSubjects = Array.from(
        new Map(subjects.map((subject) => [subject.id, subject])).values()
      );

      return uniqueSubjects.map((subject) => ({
        value: subject.id,
        label: subject.name
      }));
    }

    return (Array.isArray(subjectsData) ? subjectsData : [])
      .filter((subject) => Number(subject.course_id ?? subject.course?.id) === Number(filters.courseId))
      .map((subject) => ({
        value: subject.id,
        label: subject.name
      }));
  }, [subjectsData, filters.courseId, assignmentsData, role]);

  const generate = async () => {
    setLoading(true);
    try {
      const data = await gradesService.report(filters.courseId, filters.periodId, filters.subjectId ? { subject_id: filters.subjectId } : {});
      setReport(data.report);
      toast.success('Reporte generado.');
    } finally {
      setLoading(false);
    }
  };

  const rows =
    report?.flatMap((studentRow) =>
      studentRow.subjects.map((subject) => ({
        student: studentRow.student.full_name,
        enrollment_code: studentRow.student.enrollment_code,
        subject: subject.subject.name,
        ser: subject.ser,
        saber: subject.saber,
        hacer: subject.hacer,
        average: subject.average
      }))
    ) || [];

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader title="Reportes academicos" description="Genera boletines y planillas desde el endpoint de reportes." />
        <div className="grid gap-4 md:grid-cols-4">
          <Select
            label="Curso"
            options={courseOptions}
            value={filters.courseId}
            onChange={(e) => setFilters((s) => ({ ...s, courseId: e.target.value, subjectId: '' }))}
          />
          <Select
            label="Periodo"
            options={periodOptions}
            value={filters.periodId}
            onChange={(e) => setFilters((s) => ({ ...s, periodId: e.target.value }))}
          />
          <Select
            label="Asignatura (opcional)"
            options={subjectOptions}
            value={filters.subjectId}
            onChange={(e) => setFilters((s) => ({ ...s, subjectId: e.target.value }))}
          />
          <div className="pt-7">
            <Button loading={loading} disabled={!filters.courseId || !filters.periodId} onClick={generate} className="w-full">
              <Download className="h-4 w-4" />
              Generar
            </Button>
          </div>
        </div>
      </Card>
      <Card>
        {rows.length ? (
          <DataTable
            data={rows}
            filename="reporte-academico.csv"
            columns={[
              { header: 'Estudiante', accessorKey: 'student' },
              { header: 'Matricula', accessorKey: 'enrollment_code' },
              { header: 'Asignatura', accessorKey: 'subject' },
              { header: 'Ser', accessorKey: 'ser' },
              { header: 'Saber', accessorKey: 'saber' },
              { header: 'Hacer', accessorKey: 'hacer' },
              { header: 'Promedio', accessorKey: 'average' }
            ]}
          />
        ) : (
          <EmptyState title="Sin reporte generado" description="Ingresa curso y periodo para construir la planilla." />
        )}
      </Card>
    </div>
  );
}
