import ApexCharts from 'apexcharts/line';  // registers: line + area
import 'apexcharts/bar';                     // registers: bar + column
import 'apexcharts/pie';                     // registers: pie + donut

// Optional features — required for legend to render in tree-shaken builds.
// Without these, legend.show: true is silently ignored.
import 'apexcharts/features/legend';

window.ApexCharts = ApexCharts;

import './charts/teacher-dashboard-charts';
