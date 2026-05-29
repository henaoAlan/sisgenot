import { Inbox } from 'lucide-react';

export function EmptyState({ title = 'Sin datos', description = 'Aun no hay informacion para mostrar.', action }) {
  return (
    <div className="grid min-h-56 place-items-center rounded-lg border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
      <div>
        <div className="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800">
          <Inbox className="h-5 w-5" />
        </div>
        <h3 className="font-semibold text-slate-900 dark:text-white">{title}</h3>
        <p className="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{description}</p>
        {action && <div className="mt-4">{action}</div>}
      </div>
    </div>
  );
}
