(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function esc(value) {
        return String(value || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    async function loadDashboard() {
        const response = await SQ.api.get("/performance/v1/dashboard.php", {}, { loader: false, showError: false });
        const summary = response?.data?.summary || {};
        const trend = response?.data?.trend || [];
        setText("perfTotalEntries", summary.total_entries || 0);
        setText("perfKpiIndicators", summary.kpi_indicators || 0);
        setText("perfOutcomeIndicators", summary.outcome_indicators || 0);
        setText("perfAverageResult", summary.average_result || 0);
        const target = document.getElementById("perfTrendRows");
        if (target) {
            target.innerHTML = trend.length
                ? trend.slice(-8).map(row => `<div class="sq-performance-row"><strong>${esc(row.period)} | ${esc(row.indicator_type)}</strong><span>Average ${esc(row.average_result)} from ${esc(row.entries)} entries</span></div>`).join("")
                : "No trend data available.";
        }
    }

    function init() {
        document.getElementById("btnPerformanceRefresh")?.addEventListener("click", loadDashboard);
        loadDashboard().catch(console.error);
    }

    SQ.performanceDashboard = { init };
})(window, document);
