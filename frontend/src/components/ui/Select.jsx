import { cn } from '../../utils/cn';

export function Select({ label, error, options = [], className, placeholder = 'Seleccionar', ...props }) {
  return (
    <label className="space-y-1.5">
      {label && <span className="label">{label}</span>}
      <select className={cn('field', error && 'border-rose-500', className)} {...props}>
        <option value="">{placeholder}</option>
        {!options.length && <option value="" disabled>Sin opciones disponibles</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error && <span className="text-xs font-medium text-rose-600">{error}</span>}
    </label>
  );
}
