import Chart from 'react-apexcharts';

export function LineChart({ series = [], categories = [] }) {
  return (
    <Chart
      height={280}
      type="area"
      series={series}
      options={{
        chart: { toolbar: { show: false }, foreColor: '#64748b' },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0e7490', '#10b981', '#f59e0b'],
        xaxis: { categories },
        grid: { borderColor: '#e2e8f0' },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
        tooltip: { theme: 'dark' }
      }}
    />
  );
}
