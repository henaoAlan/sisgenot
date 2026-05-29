import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './layouts/AppLayout';
import { ProtectedRoute } from './routes/ProtectedRoute';
import { LoginPage } from './modules/auth/LoginPage';
import { DashboardPage } from './modules/dashboard/DashboardPage';
import { UsersPage } from './modules/users/UsersPage';
import { TeachersPage } from './modules/teachers/TeachersPage';
import { TeacherDetailPage } from './modules/teachers/TeacherDetailPage';
import { StudentsPage } from './modules/students/StudentsPage';
import { CoursesPage } from './modules/courses/CoursesPage';
import { SubjectsPage } from './modules/subjects/SubjectsPage';
import { PeriodsPage } from './modules/periods/PeriodsPage';
import { AssignmentsPage } from './modules/assignments/AssignmentsPage';
import { GradesPage } from './modules/grades/GradesPage';
import { ReportsPage } from './modules/reports/ReportsPage';
import { AuditPage } from './modules/audit/AuditPage';
import { ObservationsPage } from './modules/observations/ObservationsPage';
import { SchedulesPage } from './modules/schedules/SchedulesPage';
import { routePermissions } from './routes/navigation';

export function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/app/dashboard" replace />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/reset-password" element={<LoginPage />} />
      <Route element={<ProtectedRoute />}>
        <Route path="/app" element={<AppLayout />}>
          <Route index element={<Navigate to="/app/dashboard" replace />} />
          <Route element={<ProtectedRoute roles={routePermissions.dashboard} />}>
            <Route path="dashboard" element={<DashboardPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.users} />}>
            <Route path="users" element={<UsersPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.teachers} />}>
            <Route path="teachers" element={<TeachersPage />} />
            <Route path="teachers/:userId" element={<TeacherDetailPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.students} />}>
            <Route path="students" element={<StudentsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.assignments} />}>
            <Route path="assignments" element={<AssignmentsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.audit} />}>
            <Route path="audit" element={<AuditPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.courses} />}>
            <Route path="courses" element={<CoursesPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.subjects} />}>
            <Route path="subjects" element={<SubjectsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.periods} />}>
            <Route path="periods" element={<PeriodsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.grades} />}>
            <Route path="grades" element={<GradesPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.reports} />}>
            <Route path="reports" element={<ReportsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.observations} />}>
            <Route path="observations" element={<ObservationsPage />} />
          </Route>
          <Route element={<ProtectedRoute roles={routePermissions.schedules} />}>
            <Route path="schedules" element={<SchedulesPage />} />
          </Route>
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/app/dashboard" replace />} />
    </Routes>
  );
}
