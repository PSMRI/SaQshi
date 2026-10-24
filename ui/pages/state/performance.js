/*!
 * ==========================================================
 * SaQshi Open Source
 * State Performance Monitoring
 * performance.js
 * Version 1.1.0 | Updated 2026-07-13
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { pager: null };

    function domainLabel(key, fallback) {
        return SQ.deployment && typeof SQ.deployment.label === "function"
            ? SQ.deployment.label(key, fallback)
            : fallback;
    }

    function esc(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderSummary(summary) {
        const summaryElement = document.getElementById("statePerfSummary");
        summaryElement.innerHTML = `
            ${summaryCard("submitted", "Facilities with submissions", summary.facilities || 0, "Facilities with at least one performance entry")}
            ${summaryCard("kpi_only", "Facilities with KPI only", summary.facilities_with_kpi || 0, "Facilities with KPI submissions and no Outcome submissions")}
            ${summaryCard("outcome_only", "Facilities with Outcome only", summary.facilities_with_outcome || 0, "Facilities with Outcome submissions and no KPI submissions")}
            ${summaryCard("both", "Facilities with KPI &amp; Outcome", summary.facilities_with_both || 0, "Facilities with both KPI and Outcome submissions")}
            ${summaryCard("not_submitted", "Not submitted facilities", summary.not_submitted || 0, "Facilities in the selected scope with no performance entries", "sq-state-perf-not-submitted", "background:linear-gradient(135deg,#991b1b,#ef4444)")}
        `;
        adjustSummaryCards();
    }

    function summaryCard(category, label, value, title, className = "", style = "") {
        return `<div class="${esc(className)}"${style ? ` style="${esc(style)}"` : ""} title="${esc(title)}"><span>${esc(label)}</span><strong>${esc(value)}</strong><button type="button" data-state-performance-download-list="${esc(category)}" aria-label="Download ${esc(label)} list" title="Download facility list" style="position:relative;z-index:1;margin-top:7px;width:28px;height:26px;padding:0;border:1px solid rgba(255,255,255,.65);border-radius:4px;background:rgba(255,255,255,.16);color:#fff;font-size:18px;font-weight:700;line-height:1;cursor:pointer">⇩</button></div>`;
    }

    function adjustSummaryCards() {
        const summaryElement = document.getElementById("statePerfSummary");
        if (!summaryElement) return;

        summaryElement.style.gridTemplateColumns = window.innerWidth <= 720
            ? "1fr"
            : window.innerWidth <= 1100
                ? "repeat(3, minmax(0, 1fr))"
                : "repeat(5, minmax(0, 1fr))";
    }

    function renderRows(rows) {
        document.getElementById("statePerfRows").innerHTML = rows.length
            ? `<table class="sq-state-table sq-state-performance-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>${esc(domainLabel("facility", "Facility"))}</th>
                        <th>District</th>
                        <th>Block</th>
                        <th>Type</th>
                        <th>Months</th>
                        <th>Status</th>
                        <th>Latest Month</th>
                    </tr>
                </thead>
                <tbody>${rows.map((row, index) => renderFacilityRow(row, index)).join("")}</tbody>
            </table>`
            : `<div class="sq-state-empty">No performance submissions available.</div>`;
    }

    function renderFacilityRow(row, index) {
        const targetId = `statePerfDetails${index}`;
        const details = Array.isArray(row.details) ? row.details : [];

        return `
            <tr>
                <td class="sq-state-toggle-cell">
                    <button class="sq-state-plus" type="button" data-state-performance-toggle="${esc(targetId)}" aria-expanded="false">+</button>
                </td>
                <td><strong>${esc(row.fac_name || "-")}</strong><br><small>${esc(domainLabel("facility_code", "NIN"))} ${esc(row.fac_nin || row.NIN_no || "-")}</small></td>
                <td>${esc(row.district || "-")}</td>
                <td>${esc(row.block || "-")}</td>
                <td>${esc(row.facility_type || "-")}<br><small>${esc(row.effective_indicator_label || row.effective_indicator_type || "KPI")}</small></td>
                <td>${esc(row.submitted_months || row.months_submitted || 0)}</td>
                <td><span class="sq-state-badge">${esc(row.completion_status || "IN_PROGRESS")}</span></td>
                <td>${esc(row.latest_month || "-")}</td>
            </tr>
            <tr id="${esc(targetId)}" class="sq-state-district-detail" hidden>
                <td></td>
                <td colspan="7">${renderDetails(details, row.fac_name || "facility", targetId, row.fac_id)}</td>
            </tr>
        `;
    }

    function renderDetails(details, facilityName, targetId, facilityId) {
        if (!details.length) return `<div class="sq-state-empty">No KPI or Outcome detail found for this facility.</div>`;
        const departments = new Map();
        details.forEach(detail => {
            const id = String(detail.department_id || 0);
            const type = String(detail.indicator_type || "KPI").toUpperCase() === "OUTCOME" ? "OUTCOME" : "KPI";
            const key = `${type}:${id}`;
            if (!departments.has(key)) {
                departments.set(key, {
                    id,
                    type,
                    name: id === "0" ? `Facility-level ${type}` : (detail.department_name || `Department ${id}`),
                    rows: []
                });
            }
            departments.get(key).rows.push(detail);
        });
        return `<div class="sq-state-block-list">${[...departments.values()].map(department => {
            const departmentTarget = `${targetId}${department.type}Dept${department.id}`;
            return `<div class="sq-state-block-row sq-state-performance-detail">
                <button class="sq-state-plus" type="button" data-state-performance-toggle="${esc(departmentTarget)}" aria-expanded="false">+</button>
                <div><strong>${esc(department.name)}</strong><small>${esc(department.rows.length)} ${esc(department.type)} entries</small></div>
                <div id="${esc(departmentTarget)}" class="sq-state-performance-report" hidden>${renderDepartmentReport(department.rows, facilityName, facilityId, department.id, department.type)}</div>
            </div>`;
        }).join("")}</div>`;
    }

    function renderDepartmentReport(details, facilityName, facilityId, departmentId, indicatorType) {
        if (!details.length) return `<div class="sq-state-empty">No KPI or Outcome detail found for this facility.</div>`;

        const months = [...new Set(details.map(detail => detail.period).filter(Boolean))].sort();
        const indicators = new Map();
        details.forEach(detail => {
            const key = [detail.indicator_type, detail.department_id, detail.indicator_code || detail.indicator_name].join("|");
            if (!indicators.has(key)) {
                indicators.set(key, { ...detail, values: {} });
            }
            const item = indicators.get(key);
            item.values[detail.period] = {
                numerator: detail.numerator_value,
                denominator: detail.denominator_value,
                result: detail.result_value
            };
        });
        const monthLabel = period => {
            const [year, month] = String(period).split("-").map(Number);
            return Number.isFinite(year) && Number.isFinite(month)
                ? new Intl.DateTimeFormat("en-IN", { month: "short", year: "2-digit" }).format(new Date(year, month - 1, 1))
                : period;
        };
        const inputLabels = item => {
            const label = value => esc(value || "-").replace(/\n/g, "<br>");
            return `<strong>Numerator</strong><br>${label(item.numerator_label)}<hr><strong>Denominator</strong><br>${label(item.denominator_label)}`;
        };

        return `<div class="sq-state-performance-detail-actions"><a class="sq-btn sq-btn-light" href="/api/state/v1/performance_report_excel_v2.php?fac_id=${encodeURIComponent(facilityId)}&dept_id=${encodeURIComponent(departmentId)}&indicator_type=${encodeURIComponent(indicatorType)}">Download Excel</a></div><div class="sq-state-table-wrap"><table class="sq-state-table sq-state-performance-detail-table" data-facility-name="${esc(facilityName)}">
            <thead>
                <tr><th rowspan="3">Indicator</th><th rowspan="3">Numerator / denominator</th><th colspan="${months.length * 3}">${esc(indicatorType)} reports</th></tr>
                <tr>${months.map(month => `<th colspan="3">${esc(monthLabel(month))}</th>`).join("")}</tr>
                <tr>${months.map(() => "<th>Numerator</th><th>Denominator</th><th>Value</th>").join("")}</tr>
            </thead>
            <tbody>${[...indicators.values()].map(item => `<tr>
                <td>${esc(item.indicator_name || item.indicator_code || "-")}</td>
                <td>${inputLabels(item)}</td>
                ${months.map(month => {
                    const value = item.values[month];
                    return `<td>${esc(value?.numerator ?? "-")}</td><td>${esc(value?.denominator ?? "-")}</td><td>${esc(value?.result ?? "-")}</td>`;
                }).join("")}
            </tr>`).join("")}</tbody>
        </table></div>`;
    }

    function queryParams() {
        return state.pager.params({
            search: document.getElementById("statePerfSearch")?.value || ""
        });
    }

    async function downloadFacilityList(category) {
        const names = {
            submitted: "facilities-with-submissions",
            kpi_only: "facilities-with-kpi-only",
            outcome_only: "facilities-with-outcome-only",
            both: "facilities-with-kpi-and-outcome",
            not_submitted: "facilities-not-submitted"
        };
        await SQ.api.download("/state/v1/performance_facilities_export.php", {
            category,
            search: document.getElementById("statePerfSearch")?.value || ""
        }, `${names[category] || "performance-facilities"}.csv`);
    }

    function bindSearch(inputId) {
        let timer = null;
        document.getElementById(inputId)?.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                state.pager.reset();
                load();
            }, 300);
        });
    }

    function bindToggle() {
        document.getElementById("statePerfSummary")?.addEventListener("click", function (event) {
            const button = event.target.closest("[data-state-performance-download-list]");
            if (!button) return;
            downloadFacilityList(button.dataset.statePerformanceDownloadList).catch(error => {
                SQ.notification?.error(error?.message || "Unable to download the facility list.");
            });
        });
        document.getElementById("statePerfRows")?.addEventListener("click", function (event) {
            const downloadButton = event.target.closest("[data-state-performance-download]");
            if (downloadButton) {
                const table = downloadButton.parentElement?.nextElementSibling?.querySelector("table");
                if (!table) return;
                const quote = value => `"${String(value ?? "").replace(/"/g, '""').replace(/\n+/g, " ").trim()}"`;
                const headerRows = table.querySelectorAll("thead tr");
                const months = headerRows[1] ? [...headerRows[1].cells].map(cell => cell.innerText.trim()) : [];
                const headers = ["Indicator", "Formula", ...months.flatMap(month => [month + " Numerator", month + " Denominator", month + " Value"])];
                const rows = [headers.map(quote).join(",")];
                [...table.tBodies].flatMap(body => [...body.rows]).filter(row => row.querySelector("td")).forEach(row => {
                    rows.push([...row.cells].map(cell => quote(cell.innerText)).join(","));
                });
                const blob = new Blob(["\uFEFF" + rows.join("\r\n")], { type: "text/csv;charset=utf-8" });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = `${String(table.dataset.facilityName || "facility").replace(/[^a-z0-9]+/gi, "_")}_outcome_report.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
                return;
            }
            const button = event.target.closest("[data-state-performance-toggle]");
            if (!button) return;

            const target = document.getElementById(button.dataset.statePerformanceToggle);
            if (!target) return;

            const willOpen = target.hidden;
            target.hidden = !willOpen;
            button.textContent = willOpen ? "-" : "+";
            button.setAttribute("aria-expanded", willOpen ? "true" : "false");
        });
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/performance_summary.php", queryParams(), {
                loader: false,
                showError: false
            });
            const data = response.data || {};
            renderSummary(data.summary || {});
            renderRows(data.rows || []);
            state.pager.set(data.pagination || {}).render("statePerfPager", "Showing");
        } catch (error) {
            document.getElementById("statePerfSummary").innerHTML = "";
            document.getElementById("statePerfRows").innerHTML =
                `<div class="sq-state-empty">${esc(error.message || "Unable to load performance monitoring data.")}</div>`;
            state.pager.set({ page: 1, total_pages: 1, total_rows: 0 }).render("statePerfPager", "Showing");
        }
    }

    async function init() {
        if (SQ.deployment && typeof SQ.deployment.load === "function") {
            // Deployment labels are optional; they must not prevent the performance data request.
            Promise.resolve(SQ.deployment.load())
                .then(() => SQ.deployment.applyLabels(document))
                .catch(error => console.warn("[State Performance] Deployment labels unavailable", error));
        }
        state.pager = SQ.pagination.create({ page: 1, perPage: 50, onChange: load });
        bindToggle();
        window.addEventListener("resize", adjustSummaryCards);
        document.getElementById("statePerfRefresh")?.addEventListener("click", function () {
            state.pager.reset();
            load();
        });
        bindSearch("statePerfSearch");
        await load();
    }

    SQ.statePerformance = { init };
})(window, document);
