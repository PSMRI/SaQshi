/*!
 * ==========================================================
 * SaQshi Open Source
 * State Reports
 * reports.js
 * Version 1.2.0 | Updated 2026-07-13
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    function domainLabel(key, fallback) {
        return SQ.deployment && typeof SQ.deployment.label === "function"
            ? SQ.deployment.label(key, fallback)
            : fallback;
    }

    function moduleEnabled(key) {
        return !SQ.deployment || typeof SQ.deployment.moduleEnabled !== "function" || SQ.deployment.moduleEnabled(key);
    }

    function esc(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function filenameFor(key) {
        return `saqshi-state-${String(key || "summary").replace(/_/g, "-")}-${new Date().toISOString().slice(0, 10)}.csv`;
    }

    function setDownloadBusy(button, busy) {
        if (!button) return;
        if (busy) {
            button.dataset.downloadIcon = button.innerHTML;
            button.disabled = true;
            button.classList.add("is-downloading");
            button.setAttribute("aria-busy", "true");
            button.setAttribute("title", "Preparing download…");
            button.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i><span class="sq-sr-only">Preparing download</span>';
            return;
        }
        button.disabled = false;
        button.classList.remove("is-downloading");
        button.removeAttribute("aria-busy");
        button.innerHTML = button.dataset.downloadIcon || '<i class="bi bi-download"></i>';
    }

    function renderExports(exports) {
        const list = Array.isArray(exports) && exports.length ? exports : [
            { key: "summary", title: "State Summary", description: "Summary counts for all state monitoring sections." },
            { key: "facilities", title: `All ${domainLabel("facility", "Facility")} List`, description: `${domainLabel("facility", "Facility")} master details.` },
            { key: "assessments", title: "Assessment Details", description: "Assessment status, score and action plan summary." },
            { key: "assessor_activity", title: `${domainLabel("assessor", "Assessor")} Activity`, description: `Completed assessments and assessment details by ${domainLabel("assessor", "Assessor")}.` },
            ...(moduleEnabled("cqi") ? [{ key: "cqi", title: "CQI Details", description: "Action plan and gap closure details." }] : []),
            ...(moduleEnabled("performance") ? [{ key: "performance", title: "Performance Details", description: "Performance entries." }] : []),
            ...(moduleEnabled("certification") ? [{ key: "certification", title: "Certification History", description: `${domainLabel("facility", "Facility")} certification history.` }] : [])
        ];

        document.getElementById("stateReportExports").innerHTML = list.map(function (item) {
            return `
                <div class="sq-state-export-item">
                    <div>
                        <b>${esc(item.title)}</b>
                        <span>${esc(item.description)}</span>
                    </div>
                    <button class="sq-btn sq-report-download-icon" type="button" data-state-report-download="${esc(item.key)}" title="Download ${esc(item.title)}" aria-label="Download ${esc(item.title)}">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            `;
        }).join("");
    }

    function renderCurrentMonthActivity(activity) {
        const month = Number(activity?.month || 0);
        const year = Number(activity?.year || 0);
        const monthLabel = month >= 1 && month <= 12 && year > 0
            ? new Date(year, month - 1, 1).toLocaleDateString(undefined, { month: "long", year: "numeric" })
            : "Current month";
        const assessment = activity?.assessment || {};
        const performance = activity?.performance || {};
        const actionPlans = activity?.action_plans || {};
        const items = [
            ["assessment_started", "Assessments started", assessment.started],
            ["assessment_completed", "Assessments completed", assessment.completed],
            ["assessment_in_progress", "Assessments in progress", assessment.in_progress],
            ["kpi_submitted", "Facilities with KPI submitted", performance.kpi_filled],
            ["outcome_submitted", "Facilities with Outcome submitted", performance.outcome_filled],
            ["action_plans_updated", "Action plans updated", actionPlans.updated]
        ];

        const monthNode = document.getElementById("stateReportsMonth");
        const listNode = document.getElementById("stateReportActivity");
        if (monthNode) monthNode.textContent = monthLabel;
        if (listNode) {
            listNode.innerHTML = items.map(function ([key, label, value]) {
                const count = Number(value) || 0;
                const available = count > 0;
                const availabilityText = available ? `Download ${label} facility details` : `No ${label.toLowerCase()} records for this month`;
                return `<div class="sq-state-report-activity-item"><span>${esc(label)}</span><div class="sq-state-report-activity-value"><b>${esc(count)}</b><button class="sq-btn sq-report-download-icon" type="button" data-state-activity-download="${esc(key)}" data-month="${esc(month)}" data-year="${esc(year)}" title="${esc(availabilityText)}" aria-label="${esc(availabilityText)}" ${available ? "" : "disabled"}><i class="bi bi-download"></i></button></div></div>`;
            }).join("");
        }
    }

    async function load() {
        const reportsRequest = SQ.api.get("/state/v1/reports.php", {}, {
            loader: false,
            showError: false
        });
        const dashboardRequest = SQ.api.get("/state/v1/dashboard.php", {}, {
            loader: false,
            showError: false,
            redirectOnUnauthorized: false
        });

        // Downloads have already been rendered from the local fallback list.
        // Update them only when the optional server configuration is ready.
        try {
            const response = await reportsRequest;
            renderExports((response.data || {}).exports || []);
        } catch (error) {
            // The fallback list remains available when this metadata request fails.
        }

        try {
            const dashboardResponse = await dashboardRequest;
            renderCurrentMonthActivity((dashboardResponse.data || {}).current_month_status || {});
        } catch (error) {
            const monthNode = document.getElementById("stateReportsMonth");
            if (monthNode) monthNode.textContent = "Unavailable";
        }
    }

    async function downloadReport(key, button) {
        setDownloadBusy(button, true);
        try {
            await SQ.api.download("/state/v1/reports.php", {
                download: key
            }, filenameFor(key));
        } catch (error) {
            if (SQ.toast) {
                SQ.toast("Report download failed. Please try again.", "danger");
            }
        } finally {
            setDownloadBusy(button, false);
        }
    }

    async function downloadActivity(key, month, year, button) {
        setDownloadBusy(button, true);
        try {
            await SQ.api.download("/state/v1/current_month_activity_export.php", {
                activity: key,
                month,
                year
            }, filenameFor(`current-month-${key}`));
        } catch (error) {
            if (SQ.toast) {
                SQ.toast("Activity download failed. Please try again.", "danger");
            }
        } finally {
            setDownloadBusy(button, false);
        }
    }

    async function init() {
        // Show the standard downloads before any API request starts.
        renderExports();
        if (SQ.deployment && typeof SQ.deployment.load === "function") {
            await SQ.deployment.load();
            SQ.deployment.applyLabels(document);
        }
        // Re-apply the fallback list after deployment settings are available.
        renderExports();
        document.getElementById("stateReportExports")?.addEventListener("click", function (event) {
            const button = event.target.closest("[data-state-report-download]");
            if (!button) {
                return;
            }
            if (!button.disabled) {
                downloadReport(button.getAttribute("data-state-report-download"), button);
            }
        });
        document.getElementById("stateReportActivity")?.addEventListener("click", function (event) {
            const button = event.target.closest("[data-state-activity-download]");
            if (!button) {
                return;
            }
            if (!button.disabled) {
                downloadActivity(
                    button.getAttribute("data-state-activity-download"),
                    button.getAttribute("data-month"),
                    button.getAttribute("data-year"),
                    button
                );
            }
        });
        load();
    }

    SQ.stateReports = { init };
})(window, document);
