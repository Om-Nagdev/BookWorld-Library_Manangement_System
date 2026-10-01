/**
 * BookWorld - Chart.js theme helper
 * Page-specific data is passed in; this just applies a consistent look.
 */

const BW_CHART_COLORS = {
  navy: '#0F2A3F',
  green: '#2F5233',
  brass: '#C9A24B',
  danger: '#A93F35',
  slate: '#5B6472',
  line: '#E2D9C3'
};

function bwCreateLineChart(ctx, labels, dataPoints, label) {
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: label,
        data: dataPoints,
        borderColor: BW_CHART_COLORS.navy,
        backgroundColor: 'rgba(15,42,63,0.08)',
        borderWidth: 2,
        tension: 0.35,
        fill: true,
        pointBackgroundColor: BW_CHART_COLORS.brass,
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: BW_CHART_COLORS.line }, beginAtZero: true }
      }
    }
  });
}

function bwCreateBarChart(ctx, labels, dataPoints, label) {
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: label,
        data: dataPoints,
        backgroundColor: BW_CHART_COLORS.green,
        borderRadius: 6,
        maxBarThickness: 38
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: BW_CHART_COLORS.line }, beginAtZero: true }
      }
    }
  });
}

function bwCreateDoughnutChart(ctx, labels, dataPoints) {
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: dataPoints,
        backgroundColor: [BW_CHART_COLORS.navy, BW_CHART_COLORS.brass, BW_CHART_COLORS.green, BW_CHART_COLORS.danger, BW_CHART_COLORS.slate],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      cutout: '65%',
      plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } }
    }
  });
}
