import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable
} from '@tanstack/react-table';
import { ArrowDownUp, ChevronLeft, ChevronRight, Download, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '../ui/Button';
import { EmptyState } from '../common/EmptyState';
import { useUiStore } from '../../store/uiStore';

const normalizeText = (value) =>
  String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const rowMatchesSearch = (row, search) => {
  if (!search) return true;

  const visit = (value) => {
    if (value === null || value === undefined) return false;
    if (['string', 'number', 'boolean'].includes(typeof value)) {
      return normalizeText(value).includes(search);
    }
    if (Array.isArray(value)) return value.some(visit);
    if (typeof value === 'object') return Object.values(value).some(visit);
    return false;
  };

  return visit(row);
};

function exportCsv(rows, columns, filename) {
  const headers = columns.map((col) => col.header).filter(Boolean);
  const keys = columns.map((col) => col.accessorKey).filter(Boolean);
  const csvRows = [headers.join(',')].concat(
    rows.map((row) =>
      keys
        .map((key) => {
          const value = String(row.original[key] ?? '').replaceAll('"', '""');
          return `"${value}"`;
        })
        .join(',')
    )
  );
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export function DataTable({ data = [], columns = [], searchPlaceholder = 'Buscar...', filename = 'sisgenot.csv', actions }) {
  const [globalFilter, setGlobalFilter] = useState('');
  const globalSearch = useUiStore((state) => state.globalSearch);
  const tableColumns = useMemo(() => columns, [columns]);
  const globalSearchTerm = normalizeText(globalSearch).trim();
  const filteredData = useMemo(
    () => data.filter((row) => rowMatchesSearch(row, globalSearchTerm)),
    [data, globalSearchTerm]
  );
  const table = useReactTable({
    data: filteredData,
    columns: tableColumns,
    state: { globalFilter },
    onGlobalFilterChange: setGlobalFilter,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel()
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div className="relative md:w-80">
          <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <input className="field pl-9" value={globalFilter} placeholder={searchPlaceholder} onChange={(event) => setGlobalFilter(event.target.value)} />
        </div>
        <div className="flex flex-wrap gap-2">
          {actions}
          <Button variant="secondary" onClick={() => exportCsv(table.getFilteredRowModel().rows, columns, filename)}>
            <Download className="h-4 w-4" />
            CSV
          </Button>
        </div>
      </div>

      <div className="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead className="bg-slate-50 dark:bg-slate-900/60">
              {table.getHeaderGroups().map((headerGroup) => (
                <tr key={headerGroup.id}>
                  {headerGroup.headers.map((header) => (
                    <th key={header.id} className="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">
                      <button className="inline-flex items-center gap-2" onClick={header.column.getToggleSortingHandler()}>
                        {flexRender(header.column.columnDef.header, header.getContext())}
                        {header.column.getCanSort() && <ArrowDownUp className="h-3.5 w-3.5" />}
                      </button>
                    </th>
                  ))}
                </tr>
              ))}
            </thead>
            <tbody className="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-950/30">
              {table.getRowModel().rows.map((row) => (
                <tr key={row.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900">
                  {row.getVisibleCells().map((cell) => (
                    <td key={cell.id} className="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-200">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!filteredData.length && <EmptyState title="No hay registros" description="Crea o sincroniza informacion para poblar esta tabla." />}
      </div>

      <div className="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
        <span>
          Pagina {table.getState().pagination.pageIndex + 1} de {table.getPageCount() || 1}
        </span>
        <div className="flex gap-2">
          <Button variant="secondary" className="h-9 w-9 px-0" disabled={!table.getCanPreviousPage()} onClick={() => table.previousPage()}>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button variant="secondary" className="h-9 w-9 px-0" disabled={!table.getCanNextPage()} onClick={() => table.nextPage()}>
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
