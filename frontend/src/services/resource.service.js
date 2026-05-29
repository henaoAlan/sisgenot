import { api } from '../api/client';

const normalizeList = (payload) => payload.data || payload;

export function createResourceService(basePath, entityKey) {
  return {
    list: async (params = {}) => {
      const { data } = await api.get(basePath, { params });
      return normalizeList(data);
    },
    get: async (id) => {
      const { data } = await api.get(`${basePath}/${id}`);
      return entityKey ? data[entityKey] : data;
    },
    create: async (payload) => {
      const { data } = await api.post(basePath, payload);
      return data;
    },
    update: async (id, payload) => {
      const { data } = await api.put(`${basePath}/${id}`, payload);
      return data;
    },
    remove: async (id) => {
      const { data } = await api.delete(`${basePath}/${id}`);
      return data;
    }
  };
}

export const usersService = createResourceService('/users', 'user');
export const coursesService = createResourceService('/courses', 'course');
coursesService.students = async (courseId) => {
  const { data } = await api.get(`/courses/${courseId}/students`);
  return data.students || [];
};
export const subjectsService = createResourceService('/subjects', 'subject');
export const periodsService = {
  ...createResourceService('/periods', 'period'),
  toggle: async (id) => {
    const { data } = await api.patch(`/periods/${id}/toggle`);
    return data;
  }
};
export const assignmentsService = createResourceService('/teacher-assignments', 'assignment');
export const studentsService = createResourceService('/students', 'student');

const isFileLike = (value) => {
  if (!value) return false;
  if (value instanceof File) return true;
  if (typeof FileList !== 'undefined' && value instanceof FileList) return value.length > 0;
  if (value instanceof Blob) return true;
  if (Array.isArray(value) && value.some((v) => v instanceof File)) return true;
  return false;
};

const hasFile = (payload) => Object.values(payload || {}).some((value) => isFileLike(value));
const toFormData = (payload) => {
  const formData = new FormData();
  Object.entries(payload || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;

    if (value instanceof File) {
      formData.append(key, value);
      return;
    }

    if (typeof FileList !== 'undefined' && value instanceof FileList) {
      if (value.length === 1) {
        formData.append(key, value[0]);
      } else {
        Array.from(value).forEach((file) => formData.append(`${key}[]`, file));
      }
      return;
    }

    if (Array.isArray(value) && value.some((v) => v instanceof File)) {
      value.forEach((file) => formData.append(`${key}[]`, file));
      return;
    }

    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0');
      return;
    }

    if (typeof value === 'object') {
      formData.append(key, JSON.stringify(value));
      return;
    }

    formData.append(key, value);
  });
  return formData;
};
export const activitiesService = {
  ...createResourceService('/activities', 'activity'),
  create: async (payload) => {
    const body = hasFile(payload) ? toFormData(payload) : payload;
    const { data } = await api.post('/activities', body);
    return data;
  },
  update: async (id, payload) => {
    if (hasFile(payload)) {
      const formData = toFormData(payload);
      formData.append('_method', 'PUT');
      const { data } = await api.post(`/activities/${id}`, formData);
      return data;
    }

    const { data } = await api.put(`/activities/${id}`, payload);
    return data;
  },
  submit: async (activityId, payload) => {
    const { data } = await api.post(`/activities/${activityId}/submissions`, payload);
    return data;
  },
  submissions: async (activityId) => {
    const { data } = await api.get(`/activities/${activityId}/submissions`);
    return data;
  },
  downloadSubmission: async (activityId, submissionId) => {
    const response = await api.get(`/activities/${activityId}/submissions/${submissionId}/download`, {
      responseType: 'blob'
    });
    return response.data;
  },
  saveSubmissionFeedback: async (activityId, submissionId, teacherFeedback) => {
    const { data } = await api.put(`/activities/${activityId}/submissions/${submissionId}/feedback`, {
      teacher_feedback: teacherFeedback
    });
    return data;
  },
  downloadFile: async (activityId) => {
    const response = await api.get(`/activities/${activityId}/file`, {
      responseType: 'blob'
    });
    return response.data;
  }
};
export const observationsService = createResourceService('/observations', 'observation');
export const schedulesService = createResourceService('/schedules', 'schedule');
export const gradesService = {
  ...createResourceService('/grades', 'grade'),
  byStudent: async (studentId, params = {}) => {
    const { data } = await api.get(`/grades/student/${studentId}`, { params });
    return data;
  },
  report: async (courseId, periodId, params = {}) => {
    const { data } = await api.get(`/grades/report/${courseId}/${periodId}`, { params });
    return data;
  }
};
export const auditService = {
  list: async (params = {}) => {
    const { data } = await api.get('/audit-log', { params });
    return data;
  }
};
