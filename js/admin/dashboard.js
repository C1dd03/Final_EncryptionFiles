(function () {
  'use strict';

  const REFRESH_INTERVAL = 5000;
  const STATS_ENDPOINT = 'get_dashboard_stats.php';

  const statTotal = document.getElementById('stat-total');
  const statActive = document.getElementById('stat-active');
  const statBlocked = document.getElementById('stat-blocked');
  const cardTotal = document.getElementById('card-total');
  const cardActive = document.getElementById('card-active');
  const cardBlocked = document.getElementById('card-blocked');

  function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
  }

  function animateValue(el, start, end, duration) {
    if (!el) return;
    if (start === end) {
      el.textContent = formatNumber(end);
      return;
    }
    const startTime = performance.now();
    const range = end - start;
    const isPositive = range >= 0;

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const easeOut = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(start + range * easeOut);

      el.textContent = formatNumber(current);

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        el.textContent = formatNumber(end);
      }
    }

    requestAnimationFrame(update);
  }

  function parseStatValue(el) {
    if (!el) return 0;
    const raw = el.textContent.trim().replace(/,/g, '');
    const n = parseInt(raw, 10);
    return isNaN(n) ? 0 : n;
  }

  function pulseCard(card) {
    if (!card) return;
    card.animate(
      [
        { boxShadow: '2px 2px 7px 1px #2f3f335e' },
        { boxShadow: '0 0 0 4px rgba(88, 129, 87, 0.25), 2px 2px 7px 1px #2f3f335e' },
        { boxShadow: '2px 2px 7px 1px #2f3f335e' }
      ],
      { duration: 700, easing: 'ease-out' }
    );
  }

  async function fetchStats() {
    try {
      [cardTotal, cardActive, cardBlocked].forEach((c) => c && c.classList.add('stat-updating'));

      const res = await fetch(STATS_ENDPOINT, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });

      if (res.status === 403) {
        window.location.href = '../auth/index.php?action=login';
        return;
      }

      if (!res.ok) throw new Error('HTTP ' + res.status);

      const data = await res.json();
      if (!data || !data.success) throw new Error('Invalid response');

      if (data.restricted) {
        // No "View Users" privilege: do not expose user statistics
        [statTotal, statActive, statBlocked].forEach((el) => {
          if (el && el.textContent.trim() !== 'N/A') el.textContent = 'N/A';
        });
        return;
      }

      const prevTotal = parseStatValue(statTotal);
      const prevActive = parseStatValue(statActive);
      const prevBlocked = parseStatValue(statBlocked);

      const nextTotal = Number(data.total_users) || 0;
      const nextActive = Number(data.active_users) || 0;
      const nextBlocked = Number(data.blocked_users) || 0;

      if (prevTotal !== nextTotal) {
        animateValue(statTotal, prevTotal, nextTotal, 600);
        pulseCard(cardTotal);
      }
      if (prevActive !== nextActive) {
        animateValue(statActive, prevActive, nextActive, 600);
        pulseCard(cardActive);
      }
      if (prevBlocked !== nextBlocked) {
        animateValue(statBlocked, prevBlocked, nextBlocked, 600);
        pulseCard(cardBlocked);
      }
    } catch (err) {
      console.warn('[Dashboard] Stats refresh failed:', err);
    } finally {
      [cardTotal, cardActive, cardBlocked].forEach((c) => c && c.classList.remove('stat-updating'));
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (statTotal && statActive && statBlocked) {
      setInterval(fetchStats, REFRESH_INTERVAL);
    }
  });

  window.AdminDashboard = { refresh: fetchStats };
})();
