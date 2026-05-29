import { Card, CardHeader } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';

export function PlannedModulePage({ icon: Icon, title, description }) {
  return (
    <Card className="min-h-[420px]">
      <CardHeader title={title} description={description} />
      <div className="grid place-items-center rounded-lg border border-dashed border-slate-300 p-10 text-center dark:border-slate-700">
        <div>
          <div className="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-lg bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">
            <Icon className="h-8 w-8" />
          </div>
          <Badge tone="amber">Endpoint pendiente</Badge>
          <p className="mx-auto mt-4 max-w-xl text-sm text-slate-500 dark:text-slate-400">
            La pantalla ya esta integrada en la arquitectura, navegacion, permisos y estilo visual. Solo falta exponer los endpoints REST correspondientes para activar operaciones reales.
          </p>
        </div>
      </div>
    </Card>
  );
}
