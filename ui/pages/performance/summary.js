(function (window, document) {
    "use strict";
    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    async function loadSummary() {
        const target = document.getElementById("summaryRows");
        const response = await SQ.api.get("/performance/v1/summary.php", {}, { loader: false, showError: false });
        const summary = response?.data || {};
        target.innerHTML = Object.keys(summary).map(key => `<div class="sq-performance-row"><strong>${key}</strong><span>${summary[key] ?? "-"}</span></div>`).join("");
    }

    function init() {
        document.getElementById("btnSummaryRefresh")?.addEventListener("click", loadSummary);
        loadSummary().catch(console.error);
    }

    SQ.performanceSummary = { init };
})(window, document);
