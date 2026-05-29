import { api } from '../api/client';

export const teacherStudentAssignmentService = {
  // Obtener todos los docentes
  getTeachers: async () => {
    const { data } = await api.get('/teacher-student-assignments/teachers');
    return data;
  },

  // Obtener todos los estudiantes
  getStudents: async () => {
    const { data } = await api.get('/teacher-student-assignments/students');
    return data;
  },

  // Obtener estudiantes asignados a un docente
  getStudentsByTeacher: async (teacherId) => {
    const { data } = await api.get(`/teacher-student-assignments/teacher/${teacherId}/students`);
    return data;
  },

  // Obtener docentes asignados a un estudiante
  getTeachersByStudent: async (studentId) => {
    const { data } = await api.get(`/teacher-student-assignments/student/${studentId}/teachers`);
    return data;
  },

  // Asignar un estudiante a un docente
  assign: async (teacherId, studentId) => {
    const { data } = await api.post('/teacher-student-assignments/assign', {
      teacher_id: teacherId,
      student_id: studentId
    });
    return data;
  },

  // Asignar múltiples estudiantes a un docente
  assignMultiple: async (teacherId, studentIds) => {
    const { data } = await api.post('/teacher-student-assignments/assign-multiple', {
      teacher_id: teacherId,
      student_ids: studentIds
    });
    return data;
  },

  // Desasignar un estudiante de un docente
  unassign: async (teacherId, studentId) => {
    const { data } = await api.post('/teacher-student-assignments/unassign', {
      teacher_id: teacherId,
      student_id: studentId
    });
    return data;
  }
};
