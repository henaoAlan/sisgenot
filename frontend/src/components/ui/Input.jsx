import { cn } from '../../utils/cn';

export function Input({ label, error, className, ...props }) {
  return (
    <label className="space-y-1.5">
      {label && <span className="label">{label}</span>}
      <input className={cn('field', error && 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/10', className)} {...props} />
      {error && <span className="text-xs font-medium text-rose-600">{error}</span>}
    </label>
  );
}
