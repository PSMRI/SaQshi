(function (window, document) {
    "use strict";
    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    async function loadTrend() {
        const target = document.getElementById("trendRows");
        const response = await SQ.api.get("/performance/v1/trend.php", {}, { loader: false, showError: false });
        const rows = response?.data?.series || [];
        target.innerHTML = rows.length ? rows.map(row => `<div class="sq-performance-row"><strong>${row.period || "-"} | ${row.indicator_type || "-"}</strong><span>Average ${row.average_result || 0} from ${row.entries || 0} entries</span></div>`).join("") : "No trend data available.";
    }

    function init() {
        document.getElementById("btnTrendRefresh")?.addEventListener("click", loadTrend);
        loadTrend().catch(console.error);
    }

    SQ.performanceTrend = { init };
})(window, document);
