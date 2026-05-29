import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '../ui/Button';
import { Input } from '../ui/Input';
import { Select } from '../ui/Select';

export function FormBuilder({ schema, fields, defaultValues, onSubmit, submitLabel = 'Guardar' }) {
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    watch,
    setValue
  } = useForm({ resolver: zodResolver(schema), defaultValues });
  const values = watch();

  return (
    <form className="grid gap-4 sm:grid-cols-2" onSubmit={handleSubmit(onSubmit)}>
      {fields
        .filter((field) => !field.show || field.show(values))
        .map((field) => {
          if (field.type === 'select') {
            const options = typeof field.options === 'function' ? field.options(values) : field.options;
            const registerOptions = field.valueAsNumber
              ? { setValueAs: (value) => (value === '' ? '' : Number(value)) }
              : {};
            const registration = register(field.name, registerOptions);

            return (
              <Select
                key={field.name}
                label={field.label}
                options={options}
                error={errors[field.name]?.message}
                {...registration}
                onChange={(event) => {
                  registration.onChange(event);
                  field.clearFieldsOnChange?.forEach((fieldName) => setValue(fieldName, ''));
                }}
              />
            );
          }
          if (field.type === 'checkbox') {
            return (
              <label key={field.name} className="flex items-center gap-3 pt-7 text-sm font-medium text-slate-700 dark:text-slate-200">
                <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-cyan-700" {...register(field.name)} />
                {field.label}
              </label>
            );
          }
          if (field.type === 'file') {
            const registration = register(field.name);

            return (
              <Input
                key={field.name}
                label={field.label}
                type="file"
                accept={field.accept}
                error={errors[field.name]?.message}
                onBlur={registration.onBlur}
                name={registration.name}
                ref={registration.ref}
                onChange={(event) => setValue(field.name, event.target.files?.[0] ?? '')}
              />
            );
          }
          return (
            <Input
              key={field.name}
              label={field.label}
              type={field.type || 'text'}
              error={errors[field.name]?.message}
              {...register(field.name, {
                valueAsNumber: field.valueAsNumber
              })}
            />
          );
        })}
      <div className="sm:col-span-2">
        <Button type="submit" loading={isSubmitting}>
          {submitLabel}
        </Button>
      </div>
    </form>
  );
}
