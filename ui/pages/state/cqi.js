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
    const state = { pager: null, filterOptions: { divisions: [], districts: [], blocks: [] } };

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
            search: document.getElementById("stateCqiSearch")?.value || "",
            division: document.getElementById("stateCqiDivision")?.value || "",
            district: document.getElementById("stateCqiDistrict")?.value || "",
            block: document.getElementById("stateCqiBlock")?.value || ""
        });
    }

    function optionRows(select, rows, labelKey, valueKey, selected) {
        if (!select) return;
        select.innerHTML = `<option value="">All ${select.id === "stateCqiDivision" ? "divisions" : select.id === "stateCqiDistrict" ? "districts" : "blocks"}</option>${rows.map(row => `<option value="${esc(row[valueKey] || "")}">${esc(row[labelKey] || "")}</option>`).join("")}`;
        select.value = rows.some(row => String(row[valueKey] || "") === String(selected || "")) ? selected : "";
    }

    function populateLocationFilters(resetChildren) {
        const division = document.getElementById("stateCqiDivision");
        const district = document.getElementById("stateCqiDistrict");
        const block = document.getElementById("stateCqiBlock");
        if (!division || !district || !block) return;

        const selectedDivision = division.value;
        const selectedDistrict = resetChildren ? "" : district.value;
        const selectedBlock = resetChildren ? "" : block.value;
        optionRows(division, state.filterOptions.divisions, "division_name", "division_name", selectedDivision);
        const divisionRow = state.filterOptions.divisions.find(row => String(row.division_name) === String(division.value));
        const districts = divisionRow
            ? state.filterOptions.districts.filter(row => String(row.division_id) === String(divisionRow.division_id))
            : state.filterOptions.districts;
        optionRows(district, districts, "district_name", "district_name", selectedDistrict);
        const districtRow = districts.find(row => String(row.district_name) === String(district.value));
        const blocks = districtRow
            ? state.filterOptions.blocks.filter(row => String(row.dist_id) === String(districtRow.dist_id))
            : (divisionRow ? state.filterOptions.blocks.filter(row => String(row.division_id) === String(divisionRow.division_id)) : state.filterOptions.blocks);
        optionRows(block, blocks, "block_name", "block_name", selectedBlock);
        district.disabled = false;
        block.disabled = !district.value;
    }

    async function loadLocationFilters() {
        try {
            const response = await SQ.api.get("/state/v1/user_scope_options.php", {}, { loader: false, showError: false });
            const data = response.data || {};
            state.filterOptions = {
                divisions: Array.isArray(data.divisions) ? data.divisions : [],
                districts: Array.isArray(data.districts) ? data.districts : [],
                blocks: Array.isArray(data.blocks) ? data.blocks : []
            };
            populateLocationFilters(false);
        } catch (error) {
            // Search and CQI data remain usable if scope options are unavailable.
            console.warn("CQI location filters could not be loaded", error);
        }
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
                        <th>Sl.</th>
                        <th>District</th>
                        <th>Block</th>
                        <th>Facility</th>
                        <th>NIN</th>
                        <th>Type</th>
                        <th>Assessment</th>
                        <th>Status</th>
                        <th>Score 0</th>
                        <th>Score 1</th>
                        <th title="Action Plans Required (Score 0 + Score 1)">Plans Required</th>
                        <th>Plans Created</th>
                        <th>Plans Closed</th>
                        <th>Achievable</th>
                        <th>Non-achievable</th>
                        <th title="Pending Action Plans (Required − Created)">Plans Pending</th>
                        <th>Open Plans</th>
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
        const normalizedRows = rows.map(row => ({
            ...row,
            facilities_with_action_plan_created_not_closed: Object.prototype.hasOwnProperty.call(row, "facilities_with_action_plan_created_not_closed")
                ? num(row.facilities_with_action_plan_created_not_closed)
                : Math.max(0, num(row.facilities_with_submitted_action_plan) - num(row.facilities_with_closed_action_plan))
        }));
        const metrics = [
            ["stateCqiSubmittedForPlanTypes", "facilities_submitted_for_action_plan"],
            ["stateCqiPlanCreatedNotClosedTypes", "facilities_with_action_plan_created_not_closed"],
            ["stateCqiPlanClosedTypes", "facilities_with_closed_action_plan"],
            ["stateCqiPlanNotStartedTypes", "facilities_action_plan_not_started"]
        ];
        metrics.forEach(([id, metric]) => {
            const target = document.getElementById(id);
            if (!target) return;
            const types = normalizedRows
                .filter(row => num(row[metric]) > 0)
                .sort((a, b) => num(b[metric]) - num(a[metric]));
            target.innerHTML = types.length
                ? types.map(row => `<span title="${esc(row.facility_type || "Unspecified")}"><b>${esc(row.facility_type || "Unspecified")}</b><em>${num(row[metric]).toLocaleString()}</em></span>`).join("")
                : "<span>No data available</span>";
        });
    }

    function createPager() {
        if (SQ.pagination && typeof SQ.pagination.create === "function") {
            return SQ.pagination.create({ page: 1, perPage: 10, onChange: load });
        }

        // The KPI request must still run if the optional pagination helper
        // has not loaded yet. Pagination resumes after the next full reload.
        const fallback = {
            page: 1,
            perPage: 10,
            params() { return { page: this.page, per_page: this.perPage }; },
            reset() { this.page = 1; return this; },
            set() { return this; },
            render() { return this; }
        };
        return fallback;
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/cqi_summary.php", queryParams(), {
                loader: false,
                showError: false
            });
            // The user may change route while the request is in flight. Do not
            // update a CQI view that has already been removed by the router.
            if (!document.getElementById("stateCqiRows") || !document.getElementById("stateCqiPager")) return;
            const data = response.data || {};
            const createdNotClosed = Object.prototype.hasOwnProperty.call(data, "facilities_with_action_plan_created_not_closed")
                ? num(data.facilities_with_action_plan_created_not_closed)
                : Math.max(0, num(data.facilities_with_submitted_action_plan) - num(data.facilities_with_closed_action_plan));

            setText("stateCqiSubmittedForPlan", data.facilities_submitted_for_action_plan || 0);
            setText("stateCqiPlanCreatedNotClosed", createdNotClosed);
            setText("stateCqiPlanClosed", data.facilities_with_closed_action_plan || 0);
            setText("stateCqiPlanNotStarted", data.facilities_action_plan_not_started || 0);
            renderKpiBreakdown(data.facility_type_breakdown || []);
            const pagination = data.pagination || {};
            renderRows(data.rows || [], pagination.page || 1, pagination.per_page || 10);
            state.pager.set(pagination).render("stateCqiPager", "Showing");
        } catch (error) {
            if (!document.getElementById("stateCqiRows") || !document.getElementById("stateCqiPager")) return;
            setText("stateCqiSubmittedForPlan", 0);
            setText("stateCqiPlanCreatedNotClosed", 0);
            setText("stateCqiPlanClosed", 0);
            setText("stateCqiPlanNotStarted", 0);
            renderKpiBreakdown([]);
            const rowsTarget = document.getElementById("stateCqiRows");
            if (rowsTarget) rowsTarget.innerHTML =
                `<div class="sq-state-empty">${esc(error.message || "Unable to load CQI monitoring data.")}</div>`;
            state.pager.set({ page: 1, total_pages: 1, total_rows: 0 }).render("stateCqiPager", "Showing");
        }
    }

    function moveToolbarIntoPageHeader() {
        const title = document.getElementById("sq-page-title");
        const toolbar = document.querySelector(".sq-state-cqi-page > .sq-state-toolbar");
        if (!title || !toolbar || toolbar.classList.contains("sq-cqi-header-toolbar")) return;

        const row = document.createElement("div");
        row.className = "sq-cqi-page-title-row";
        title.parentNode.insertBefore(row, title);
        row.appendChild(title);
        toolbar.classList.add("sq-cqi-header-toolbar");
        row.appendChild(toolbar);
    }

    async function init() {
        moveToolbarIntoPageHeader();
        if (SQ.deployment && typeof SQ.deployment.load === "function") {
            try {
                await SQ.deployment.load();
                SQ.deployment.applyLabels(document);
            } catch (error) {
                // Labels are optional; do not prevent the CQI API from loading.
                console.warn("CQI deployment labels could not be applied", error);
            }
        }
        state.pager = createPager();
        document.getElementById("stateCqiRefresh")?.addEventListener("click", function () {
            state.pager.reset();
            load();
        });
        document.getElementById("stateCqiDownload")?.addEventListener("click", downloadAllStatuses);
        bindSearch("stateCqiSearch");
        document.getElementById("stateCqiDivision")?.addEventListener("change", function () {
            populateLocationFilters(true);
            state.pager.reset();
            load();
        });
        document.getElementById("stateCqiDistrict")?.addEventListener("change", function () {
            const block = document.getElementById("stateCqiBlock");
            if (block) block.value = "";
            populateLocationFilters(false);
            state.pager.reset();
            load();
        });
        document.getElementById("stateCqiBlock")?.addEventListener("change", function () {
            state.pager.reset();
            load();
        });
        await loadLocationFilters();
        await load();
    }

    SQ.stateCqi = { init };
})(window, document);
