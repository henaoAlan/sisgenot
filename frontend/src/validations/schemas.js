import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().email('Correo invalido'),
  password: z.string().min(6, 'Minimo 6 caracteres')
});

const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/;

export const forgotPasswordSchema = z.object({
  email: z.string().email('Correo invalido')
});

export const resetPasswordSchema = z
  .object({
    email: z.string().email('Correo invalido'),
    token: z.string().min(1, 'Token requerido'),
    password: z.string().min(8, 'Minimo 8 caracteres').refine(
      (val) => passwordRegex.test(val),
      'Contrasena debe incluir mayusculas, minusculas y numeros'
    ),
    password_confirmation: z.string().min(1, 'Confirma la contrasena')
  })
  .refine((value) => value.password === value.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Las contrasenas no coinciden'
  });

export const userSchema = z
  .object({
    full_name: z.string().min(3, 'Nombre requerido'),
    email: z.string().email('Correo invalido'),
    password: z.string().min(8, 'Mínimo 8 caracteres').optional().or(z.literal(''))
      .refine(
        (val) => !val || passwordRegex.test(val),
        'Contraseña debe incluir mayúsculas, minúsculas y números'
      ),
    role: z.enum(['admin', 'teacher', 'student']),
    document_id: z.string().optional(),
    enrollment_code: z.string().optional(),
    course_id: z.union([z.coerce.number().min(1), z.literal('')]).optional(),
    is_active: z.boolean().default(true)
  })
  .superRefine((value, ctx) => {
    if (['teacher', 'student'].includes(value.role) && !value.document_id) {
      ctx.addIssue({ code: 'custom', path: ['document_id'], message: 'Documento requerido' });
    }
    if (value.role === 'student' && !value.enrollment_code) {
      ctx.addIssue({ code: 'custom', path: ['enrollment_code'], message: 'Matricula requerida' });
    }
  });

export const courseSchema = z.object({
  name: z.string().min(2, 'Nombre requerido'),
  grade: z.string().min(1, 'Grado requerido'),
  year: z.coerce.number().min(2000).max(2100),
  is_active: z.boolean().default(true)
});

export const subjectSchema = z.object({
  name: z.string().min(2, 'Nombre requerido'),
  code: z.string().min(2, 'Codigo requerido'),
  course_id: z.coerce.number().min(1, 'Curso requerido')
});

export const periodSchema = z.object({
  name: z.string().min(2, 'Nombre requerido'),
  year: z.coerce.number().min(2000).max(2100),
  ordering: z.coerce.number().min(1).max(10),
  is_open: z.boolean().default(false)
});

export const gradeSchema = z.object({
  student_id: z.coerce.number().min(1),
  course_id: z.coerce.number().min(1),
  subject_id: z.coerce.number().min(1),
  period_id: z.coerce.number().min(1),
  competency: z.enum(['ser', 'saber', 'hacer']),
  grade: z.coerce.number().min(1).max(5)
});

export const activitySchema = z.object({
  teacher_assignment_id: z.coerce.number().min(1, 'Asignacion requerida'),
  period_id: z.coerce.number().min(1, 'Periodo requerido'),
  student_id: z.union([z.coerce.number().min(1), z.literal('')]).optional(),
  moment: z.coerce.number().min(1).max(3),
  activity_number: z.coerce.number().min(1).max(10),
  title: z.string().min(3, 'Titulo requerido'),
  description: z.string().optional(),
  due_date: z.string().optional(),
  file: z.any().optional(),
  is_recovery: z.boolean().default(false)
});

export const observationSchema = z.object({
  student_id: z.coerce.number().min(1, 'Estudiante requerido'),
  teacher_id: z.union([z.coerce.number().min(1), z.literal('')]).optional(),
  subject_id: z.coerce.number().min(1, 'Asignatura requerida'),
  period_id: z.union([z.coerce.number().min(1), z.literal('')]).optional(),
  observation: z.string().min(3, 'Observacion requerida')
});

export const scheduleSchema = z.object({
  course_id: z.coerce.number().min(1, 'Curso requerido'),
  subject_id: z.coerce.number().min(1, 'Asignatura requerida'),
  teacher_id: z.coerce.number().min(1, 'Docente requerido'),
  day_of_week: z.coerce.number().min(1).max(7),
  starts_at: z.string().min(1, 'Hora inicial requerida'),
  ends_at: z.string().min(1, 'Hora final requerida'),
  classroom: z.string().optional()
});
