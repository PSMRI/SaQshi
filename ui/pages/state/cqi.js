/*!
 * ==========================================================
 * SaQshi Open Source
 * State CQI Monitoring
 * cqi.js
 * Version 1.1.0 | Updated 2026-07-13
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { pager: null };

    function esc(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function num(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function progress(done, total) {
        const safeDone = num(done);
        const safeTotal = num(total);
        const pct = safeTotal > 0 ? Math.round((safeDone / safeTotal) * 100) : 0;

        return `
            <div class="sq-state-mini-progress">
                <div><span style="width:${Math.max(0, Math.min(100, pct))}%"></span></div>
                <small>${esc(safeDone)} / ${esc(safeTotal)}</small>
            </div>
        `;
    }

    function statusBadge(row) {
        if (num(row.total_action_plans) === 0) {
            return `<span class="sq-state-badge sq-state-warning">Action Plan Not Created</span>`;
        }
        if (row.cqi_completed === true || String(row.cqi_completed) === "1") {
            return `<span class="sq-state-badge sq-state-latest">CQI Completed</span>`;
        }
        if (num(row.overdue) > 0) {
            return `<span class="sq-state-badge sq-state-danger">Overdue</span>`;
        }
        if (num(row.due_soon) > 0) {
            return `<span class="sq-state-badge sq-state-warning">Due within 7 days</span>`;
        }
        if (num(row.high_priority_pending) > 0) {
            return `<span class="sq-state-badge sq-state-danger">High priority</span>`;
        }
        if (num(row.unassigned_pending) > 0) {
            return `<span class="sq-state-badge sq-state-warning">Owner missing</span>`;
        }
        if (num(row.pending) > 0) {
            return `<span class="sq-state-badge sq-state-warning">Pending</span>`;
        }
        if (num(row.achievability_updated) < num(row.total_action_plans)) {
            return `<span class="sq-state-badge sq-state-warning">Achievability Pending</span>`;
        }
        if (num(row.revised_score_updated) < num(row.total_action_plans)) {
            return `<span class="sq-state-badge sq-state-warning">Latest Score Pending</span>`;
        }
        return `<span class="sq-state-badge sq-state-warning">CQI Details Pending</span>`;
    }

    function cqiStatus(row) {
        if (num(row.total_action_plans) === 0) return "Action Plan Not Created";
        if (row.cqi_completed === true || String(row.cqi_completed) === "1") return "CQI Completed";
        if (num(row.overdue) > 0) return "Overdue";
        if (num(row.due_soon) > 0) return "Due within 7 days";
        if (num(row.high_priority_pending) > 0) return "High priority";
        if (num(row.unassigned_pending) > 0) return "Owner missing";
        if (num(row.pending) > 0) return "Pending";
        if (num(row.achievability_updated) < num(row.total_action_plans)) return "Achievability Pending";
        if (num(row.revised_score_updated) < num(row.total_action_plans)) return "Latest Score Pending";
        return "CQI Details Pending";
    }

    function queryParams() {
        return state.pager.params({
            search: document.getElementById("stateCqiSearch")?.value || ""
        });
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

    function yesNo(value) {
        return value
            ? `<span class="sq-state-badge sq-state-latest">Yes</span>`
            : `<span class="sq-state-badge sq-state-warning">No</span>`;
    }

    function renderRows(rows, page, perPage) {
        const target = document.getElementById("stateCqiRows");
        if (!target) return;
        target.innerHTML = rows.length
            ? `<table class="sq-state-table sq-state-cqi-table">
                <thead>
                    <tr>
                        <th>Sl. No.</th>
                        <th>District</th>
                        <th>Block</th>
                        <th>Facility Name</th>
                        <th>NIN No.</th>
                        <th>Facility Type</th>
                        <th>Assessment Name</th>
                        <th>Assessment Status</th>
                        <th>Score 0 Checkpoints</th>
                        <th>Score 1 Checkpoints</th>
                        <th>Action Plans Required<br><small>Score 0 + Score 1</small></th>
                        <th>Action Plans Created</th>
                        <th>Action Plans Closed</th>
                        <th>Achievable Plans</th>
                        <th>Non-achievable Plans</th>
                        <th>Pending Action Plans<br><small>Required − Created</small></th>
                        <th>Open Action Plans</th>
                        <th>CQI Status</th>
                    </tr>
                </thead>
                <tbody>${rows.map((row, index) => `
                    <tr>
                        <td>${esc(((num(page) - 1) * num(perPage)) + index + 1)}</td>
                        <td>${esc(row.district || "-")}</td>
                        <td>${esc(row.block || "-")}</td>
                        <td><strong>${esc(row.fac_name || "-")}</strong></td>
                        <td>${esc(row.nin_no || "-")}</td>
                        <td>${esc(row.facility_type || "-")}</td>
                        <td>${esc(row.assessment_name || "-")}</td>
                        <td>${esc(row.assessment_status || "-")}</td>
                        <td>${esc(row.score_zero_count || 0)}</td>
                        <td>${esc(row.score_one_count || 0)}</td>
                        <td>${esc(row.action_plans_required || 0)}</td>
                        <td>${esc(row.total_action_plans || 0)}</td>
                        <td>${esc(row.closed_action_plans || 0)}</td>
                        <td>${esc(row.achievable_action_plans || 0)}</td>
                        <td>${esc(row.non_achievable_action_plans || 0)}</td>
                        <td>${esc(row.pending_action_plan_creation || 0)}</td>
                        <td>${esc(row.pending || 0)}</td>
                        <td>${statusBadge(row)}</td>
                    </tr>
                `).join("")}</tbody>
            </table>`
            : `<div class="sq-state-empty">No facility CQI action plans available.</div>`;
    }

    function csvValue(value) {
        let text = String(value ?? "");
        if (/^[\s]*[=+@\-\t\r]/.test(text)) text = "'" + text;
        return `"${text.replace(/"/g, '""')}"`;
    }

    async function downloadAllStatuses() {
        const button = document.getElementById("stateCqiDownload");
        if (!button) return;
        const filters = Object.assign({}, queryParams(), { page: 1, per_page: 100, export_all: 1 });
        const output = [[
            "Facility ID", "District", "Block", "Facility Name", "NIN No.", "Facility Type",
            "Assessment ID", "Assessment Name", "Assessment Status", "Score 0 Checkpoints", "Score 1 Checkpoints", "Action Plans Required (Score 0 + Score 1)", "Action Plans Created", "Action Plans Closed", "Achievable Action Plans", "Non-achievable Action Plans", "Pending Action Plans (Required - Created)", "Open Action Plans",
            "CQI Completed", "CQI Status",
            "Pending Plans", "Overdue Plans", "High Priority Pending", "Owner Missing"
        ]];
        button.disabled = true;
        button.textContent = "Preparing download...";
        try {
            const response = await SQ.api.get("/state/v1/cqi_summary.php", filters, {
                loader: false,
                showError: false,
                timeout: 120000
            });
            for (const row of response.data?.rows || []) {
                    const actionPlanCount = num(row.total_action_plans);
                    output.push([
                        row.fac_id_fk, row.district, row.block, row.fac_name, row.nin_no, row.facility_type,
                        row.assessment_id, row.assessment_name, row.assessment_status, row.score_zero_count, row.score_one_count, row.action_plans_required, actionPlanCount, row.closed_action_plans, row.achievable_action_plans, row.non_achievable_action_plans, row.pending_action_plan_creation, row.pending,
                        (row.cqi_completed === true || String(row.cqi_completed) === "1") ? "Yes" : "No", cqiStatus(row), row.pending, row.overdue,
                        row.high_priority_pending, row.unassigned_pending
                    ]);
            }

            const blob = new Blob(["\ufeff", output.map(row => row.map(csvValue).join(",")).join("\r\n")], { type: "text/csv;charset=utf-8" });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = `cqi-facility-status-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (error) {
            SQ.notification?.error(error?.message || "Unable to download CQI status details.");
        } finally {
            button.disabled = false;
            button.textContent = "Download All Statuses (CSV)";
        }
    }

    function renderKpiBreakdown(rows) {
        const metrics = [
            ["stateCqiTotalTypes", "facilities_submitted", "facilities"],
            ["stateCqiPlanTypes", "action_plans_created", "plans"],
            ["stateCqiMissingTypes", "action_plan_not_created", "facilities"],
            ["stateCqiCompletedTypes", "facilities_completed", "facilities"]
        ];
        metrics.forEach(([id, metric, unit]) => {
            const target = document.getElementById(id);
            if (!target) return;
            const types = rows
                .filter(row => num(row[metric]) > 0)
                .sort((a, b) => num(b[metric]) - num(a[metric]));
            const visible = types.slice(0, 3);
            const remaining = types.length - visible.length;
            target.innerHTML = visible.length
                ? visible.map(row => `<span><b>${esc(row.facility_type || "Unspecified")}</b><em>${num(row[metric]).toLocaleString()} ${unit}</em></span>`).join("")
                    + (remaining ? `<small>+ ${remaining} other facility type${remaining === 1 ? "" : "s"}</small>` : "")
                : "<span>No data available</span>";
        });
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/cqi_summary.php", queryParams(), {
                loader: false,
                showError: false
            });
            const data = response.data || {};

            setText("stateCqiTotal", data.facilities_submitted || 0);
            setText("stateCqiDone", data.action_plans_created || 0);
            setText("stateCqiHighPriority", data.action_plan_not_created || 0);
            setText("stateCqiUnassigned", data.facilities_completed || 0);
            renderKpiBreakdown(data.facility_type_breakdown || []);
            const pagination = data.pagination || {};
            renderRows(data.rows || [], pagination.page || 1, pagination.per_page || 10);
            state.pager.set(pagination).render("stateCqiPager", "Showing");
        } catch (error) {
            setText("stateCqiTotal", 0);
            setText("stateCqiDone", 0);
            setText("stateCqiHighPriority", 0);
            setText("stateCqiUnassigned", 0);
            renderKpiBreakdown([]);
            const rowsTarget = document.getElementById("stateCqiRows");
            if (rowsTarget) rowsTarget.innerHTML =
                `<div class="sq-state-empty">${esc(error.message || "Unable to load CQI monitoring data.")}</div>`;
            state.pager.set({ page: 1, total_pages: 1, total_rows: 0 }).render("stateCqiPager", "Showing");
        }
    }

    async function init() {
        if (SQ.deployment && typeof SQ.deployment.load === "function") {
            await SQ.deployment.load();
            SQ.deployment.applyLabels(document);
        }
        state.pager = SQ.pagination.create({ page: 1, perPage: 10, onChange: load });
        document.getElementById("stateCqiRefresh")?.addEventListener("click", function () {
            state.pager.reset();
            load();
        });
        document.getElementById("stateCqiDownload")?.addEventListener("click", downloadAllStatuses);
        bindSearch("stateCqiSearch");
        await load();
    }

    SQ.stateCqi = { init };
})(window, document);
