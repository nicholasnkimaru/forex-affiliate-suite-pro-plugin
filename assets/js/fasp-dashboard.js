// fasp-dashboard.js - renders Chart.js chart and avatars
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    try {
      var ctx = document.getElementById('faspProgressChart');
      if (ctx && typeof Chart !== 'undefined') {
        var labels = [];
        var values = [];
        if ( window.fasp_dashboard_data && fasp_dashboard_data.chart ) {
          labels = fasp_dashboard_data.chart.labels || [];
          values = fasp_dashboard_data.chart.values || [];
        }
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Progress',
              data: values,
              borderColor: '#06b6d4',
              backgroundColor: 'rgba(6,182,212,0.08)',
              fill: true,
              tension: 0.2,
              pointRadius: 4,
              pointBackgroundColor: '#0ea5a4'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f3f4f6' } } }
          }
        });
      }

      var avatars = document.querySelectorAll('.fasp-avatar[data-initials]');
      avatars.forEach(function (el) {
        var initials = el.getAttribute('data-initials') || '';
        initials = initials.trim().substring(0, 2).toUpperCase();
        if (!initials) initials = '?';
        var hash = 0;
        for (var i = 0; i < initials.length; i++) {
          hash = initials.charCodeAt(i) + ((hash << 5) - hash);
        }
        var hue = Math.abs(hash) % 360;
        el.style.backgroundColor = 'hsl(' + hue + ', 65%, 50%)';
        el.style.color = '#fff';
        el.style.display = 'inline-flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
        el.style.fontWeight = '600';
        el.style.borderRadius = '50%';
        el.style.width = el.style.height = '44px';
        el.textContent = initials;
      });

      var platformCards = document.querySelectorAll('.fasp-platform[data-href]');
      platformCards.forEach(function (card) {
        var url = card.getAttribute('data-href');
        card.style.cursor = 'pointer';
        card.addEventListener('click', function (e) {
          if (e.target && (e.target.tagName === 'A' || e.target.closest('a'))) return;
          window.open(url, '_blank', 'noopener');
        });
      });
    } catch (err) {
      if ( window.console && console.error ) {
        console.error('FASP dashboard error:', err);
      }
    }
  });
})();
