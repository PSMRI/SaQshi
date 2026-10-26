/*!
 * ==========================================================
 * SaQshi Open Source
 * State Assessment Progress
 * assessment-progress.js
 * Version 1.1.0 | Updated 2026-07-13
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { pager: null, rows: [], distribution: [], viewBy: "DIVISION", recordSearch: "", filterOptions: { divisions: [], districts: [], blocks: [] } };

    function domainLabel(key, fallback) {
        return SQ.deployment && typeof SQ.deployment.label === "function"
            ? SQ.deployment.label(key, fallback)
            : fallback;
    }

    function assessmentUnitLabel() {
        return domainLabel("departments", "Departments");
    }

    function isEducationProfile() {
        const profile = SQ.deployment?.current?.domain?.profile_code || SQ.deployment?.current?.modules?.active_profile || "";
        return String(profile).toLowerCase() === "education";
    }

    function scoreDimensionLabel() {
        return isEducationProfile() ? "Domain" : "Area of Concern";
    }

    function esc(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function number(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function percent(value) {
        return Math.max(0, Math.min(100, number(value)));
    }

    function progress(done, total) {
        const safeDone = number(done);
        const safeTotal = number(total);
        const pct = safeTotal > 0 ? Math.round((safeDone / safeTotal) * 100) : 0;

        return `
            <div class="sq-state-mini-progress">
                <div><span style="width:${percent(pct)}%"></span></div>
                <small>${esc(safeDone)} / ${esc(safeTotal)}</small>
            </div>
        `;
    }

    function scoreCell(row) {
        const finalScore = number(row.score_percent).toFixed(2);
        const baseline = number(row.baseline_score_percent).toFixed(2);

        return `
            <strong>${esc(finalScore)}%</strong>
            <small>Baseline ${esc(baseline)}%</small>
            <small>${esc(row.final_obtained_score || 0)} / ${esc(row.total_score || 0)}</small>
        `;
    }

    function queryParams() {
        return state.pager.params({
            group_by_facility: 1,
            view_by: document.getElementById("stateAssessViewBy")?.value === "AUTO" ? "" : (document.getElementById("stateAssessViewBy")?.value || ""),
            search: document.getElementById("stateAssessSearch")?.value || "",
            division: document.getElementById("stateAssessDivision")?.value || "",
            district: document.getElementById("stateAssessDistrict")?.value || "",
            block: document.getElementById("stateAssessBlock")?.value || ""
        });
    }

    function populateLocationFilters() {
        const division = document.getElementById("stateAssessDivision");
        const district = document.getElementById("stateAssessDistrict");
        const block = document.getElementById("stateAssessBlock");
        if (division) {
            const selected = division.value;
            division.innerHTML = `<option value="">All divisions</option>${state.filterOptions.divisions.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            division.value = state.filterOptions.divisions.includes(selected) ? selected : "";
        }
        if (district) {
            const selected = district.value;
            district.innerHTML = `<option value="">All districts</option>${state.filterOptions.districts.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            district.disabled = false;
            district.value = state.filterOptions.districts.includes(selected) ? selected : "";
        }
        if (block) {
            const selected = block.value;
            block.innerHTML = `<option value="">All blocks</option>${state.filterOptions.blocks.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            block.disabled = !district?.value;
            block.value = state.filterOptions.blocks.includes(selected) ? selected : "";
        }
    }

    function domainClass(index) {
        return ["is-domain-blue", "is-domain-teal", "is-domain-purple", "is-domain-orange", "is-domain-red"][index % 5];
    }

    function categoryBadge(category) {
        if (!isEducationProfile()) return "";
        const name = String(category?.name || "-");
        const key = name.toLowerCase();
        const css = key === "jagriti" ? "is-jagriti" : (key === "pragati" ? "is-pragati" : (key === "abhilasha" ? "is-abhilasha" : ""));
        return `<span class="sq-score-category ${css}">${esc(name)}</span>`;
    }

    function openDomainDialog(assessmentId, showRound) {
        const row = state.rows.find(function (item) { return Number(item.assessment_id) === Number(assessmentId); });
        const modelScore = showRound ? row?.round_score : row?.model_score;
        if (!modelScore?.models?.length) return;
        document.getElementById("stateDomainScoreDialog")?.remove();
        const modal = document.createElement("div");
        modal.id = "stateDomainScoreDialog";
        modal.className = "sq-state-modal";
        modal.setAttribute("role", "dialog");
        modal.setAttribute("aria-modal", "true");
        const dimension = scoreDimensionLabel();
        const title = showRound ? `Assessment Round ${dimension}-wise Scores` : `${dimension}-wise Scores`;
        const overall = isEducationProfile()
            ? `${showRound ? "Whole School / Facility round average" : "Class domain average"}</span><strong>${esc(number(modelScore.percentage).toFixed(2))}% · ${esc(modelScore.category?.name || "-")}`
            : `Overall assessment score</span><strong>${esc(number(modelScore.percentage).toFixed(2))}%`;
        modal.innerHTML = `<div class="sq-state-modal-panel sq-state-domain-dialog">
            <div class="sq-card-header"><div><h3>${esc(title)}</h3><p>${esc(row.fac_name || "School / Facility")} · ${showRound ? `Round ${esc(row.round_no || row.round_id || "-")}` : esc(row.assessment_name || "Assessment")}</p></div><button type="button" class="sq-btn sq-btn-light" data-close-domain-dialog>Close</button></div>
            <div class="sq-card-body">${modelScore.models.map(function (model, index) {
                const value = Math.max(0, Math.min(100, number(model.percentage)));
                const checkpoints = model.total_checkpoints !== undefined ? `${number(model.answered_checkpoints)} / ${number(model.total_checkpoints)} checkpoints` : "Round aggregate";
                const score = model.total_score !== undefined ? `${number(model.obtained_score).toFixed(2)} / ${number(model.total_score).toFixed(2)} score` : "";
                return `<div class="sq-domain-score ${domainClass(index)}"><div class="sq-domain-score-title"><strong>${esc(model.model_name)}</strong><b>${esc(value.toFixed(2).replace(/\.00$/, ""))}%</b></div><div class="sq-domain-score-bar"><span style="width:${value}%"></span></div><div class="sq-domain-score-meta">${esc(checkpoints)}${score ? ` · ${esc(score)}` : ""}</div>${isEducationProfile() ? `<div class="sq-domain-score-category">${esc(model.category?.name || "-")}</div>` : ""}</div>`;
            }).join("")}<div class="sq-domain-score-total"><span>${overall}</strong></div></div>
        </div>`;
        document.body.appendChild(modal);
        modal.addEventListener("click", function (event) {
            if (event.target === modal || event.target.closest("[data-close-domain-dialog]")) modal.remove();
        });
    }

    function createPager() {
        if (SQ.pagination && typeof SQ.pagination.create === "function") {
            return SQ.pagination.create({ page: 1, perPage: 50, onChange: load });
        }

        return {
            params(extra) { return Object.assign({ page: 1, per_page: 50 }, extra || {}); },
            set() { return this; },
            render() { return this; },
            reset() { return this; }
        };
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

    function renderSummary(summary) {
        document.getElementById("stateAssessSummary").innerHTML = `
            <div class="sq-assess-kpi is-total"><span>Total</span><strong>${esc(summary.total || 0)}</strong></div>
            <div class="sq-assess-kpi is-active"><span>Active</span><strong>${esc(summary.active || 0)}</strong></div>
            <div class="sq-assess-kpi is-completed"><span>Completed</span><strong>${esc(summary.completed || 0)}</strong></div>
            <div class="sq-assess-kpi is-cancelled"><span>Cancelled</span><strong>${esc(summary.cancelled || 0)}</strong></div>
            <div class="sq-assess-kpi is-total"><span>Facilities with no assessment</span><strong>${esc(summary.facilities_without_assessment || 0)}</strong></div>
        `;
    }

    function renderDistribution() {
        const viewBy = state.viewBy || "DIVISION";
        document.getElementById("stateAssessDistributionTitle").textContent = `${viewBy.charAt(0) + viewBy.slice(1).toLowerCase()}-wise assessment completion`;
        const rows = state.distribution || [];
        const target = document.getElementById("stateAssessDistribution");
        target.innerHTML = rows.length
            ? `<div class="sq-state-progress-cards">${rows.map(row => { const value = percent(row.completion_percentage); const tone = value >= 70 ? "is-good" : (value >= 40 ? "is-watch" : "is-low"); const types = (row.facility_type_breakdown || []).map(item => `${item.count} ${item.type}`).join(" · "); return `<article class="sq-state-progress-card ${tone}"><strong>${esc(row.label)}</strong><b>${esc(row.completion_percentage)}%</b><div class="sq-state-mini-progress"><div><span style="width:${value}%"></span></div></div><small>${esc(row.completed)} / ${esc(row.total)} assessments · ${esc(row.facility_count)} facilities · Active ${esc(row.active_facilities)} · Completed ${esc(row.completed_facilities)} · Cancelled ${esc(row.cancelled_facilities)}</small><small class="sq-state-facility-type-breakdown">${esc(types)}</small></article>`; }).join("")}</div>`
            : `<div class="sq-state-empty">No assessment records for the selected location.</div>`;
    }

    function renderRows(rows) {
        const needle = state.recordSearch.trim().toLowerCase();
        const visibleRows = needle ? rows.filter(function (row) {
            return [row.NIN_no, row.fac_name, row.division, row.district, row.block, row.assessment_name, row.assessment_id]
                .some(value => String(value ?? "").toLowerCase().includes(needle));
        }) : rows;
        const schools = Object.values(visibleRows.reduce(function (map, row) {
            const key = String(row.fac_id_fk || row.NIN_no || row.fac_name || "unknown");
            if (!map[key]) map[key] = { id: row.fac_id_fk, name: row.fac_name, code: row.NIN_no, state: row.state_name, division: row.division, district: row.district, block: row.block, rounds: {} };
            const round = String(row.round_no || row.round_id || "Unassigned");
            if (!map[key].rounds[round]) map[key].rounds[round] = [];
            map[key].rounds[round].push(row);
            return map;
        }, {}));
        document.getElementById("stateAssessRows").innerHTML = schools.length
            ? `<table class="sq-state-table"><thead><tr><th>Division</th><th>District</th><th>Block</th><th>NIN</th><th>Facility name</th><th>Assessments</th></tr></thead><tbody>${schools.map(function (school) {
                const assessmentCount = Object.values(school.rounds).reduce((total, entries) => total + entries.length, 0);
                return `<tr><td>${esc(school.division || "-")}</td><td>${esc(school.district || "-")}</td><td>${esc(school.block || "-")}</td><td>${esc(school.code || "-")}</td><td><strong>${esc(school.name || "School / Facility")}</strong><small>Facility ID: ${esc(school.id || "-")}</small></td><td>${esc(assessmentCount)}</td></tr><tr class="sq-state-facility-detail-row"><td colspan="6"><details class="sq-state-school-drill"><summary><span class="sq-state-expand-control">+</span> Show ${esc(assessmentCount)} assessment${assessmentCount === 1 ? "" : "s"}</summary>${Object.keys(school.rounds).map(function (round) {
                    const entries = school.rounds[round];
                    const completed = entries.filter(function (row) { return String(row.status || "").toUpperCase() === "COMPLETED"; });
                    const roundScore = completed[0]?.round_score;
                    const label = scoreDimensionLabel();
                    const hasRound = round !== "Unassigned";
                    const roundTitle = hasRound ? `Assessment Round ${esc(round)}` : "Assessment (round not assigned)";
                    const roundProgress = `${esc(entries.length)} assessment record${entries.length === 1 ? "" : "s"}`;
                    const roundButton = isEducationProfile() ? `View Assessment Round ${esc(round)} Domains` : `View Assessment Round ${esc(round)} ${esc(label)} Scores`;
                    const itemButton = isEducationProfile() ? "View class domains" : `View ${esc(label)} scores`;
                    return `<details class="sq-state-round-drill"><summary>${roundTitle} · ${roundProgress} ${roundScore ? categoryBadge(roundScore.category) : ""}</summary><div class="sq-assessment-detail-wrap">${completed.length && hasRound && roundScore?.models?.length ? `<button type="button" class="sq-btn sq-btn-light" data-round-domain-dialog="${esc(completed[0].assessment_id)}">${roundButton}</button>` : ""}<table class="sq-assessment-detail-table"><thead><tr><th>Assessment</th><th>Status</th><th>Responses<br>0 / 1 / 2</th><th>Checkpoint completion</th><th>Score</th><th>Start / planned end</th><th>Completed / cancelled</th></tr></thead><tbody>${entries.map(function (row) {
                        const done = String(row.status || "").toUpperCase() === "COMPLETED";
                        const total = number(row.total_checkpoints);
                        const completedCount = number(row.checkpoint_done);
                        const completion = total > 0 ? `${esc((completedCount * 100 / total).toFixed(2))}%<small>${esc(completedCount)} / ${esc(total)} checkpoints</small>` : `<span>Unknown</span><small>${esc(completedCount)} answered · checklist total unavailable</small>`;
                        const score = number(row.total_score) > 0 ? `${esc(number(row.score_percent).toFixed(2))}%<small>${esc(number(row.obtained_score))} / ${esc(number(row.total_score))} total possible</small>` : "-";
                        return `<tr><td><strong>${esc(row.assessment_name || "Assessment")}</strong><small>ID ${esc(row.assessment_id)}</small>${done && row.model_score?.models?.length ? `<button type="button" class="sq-btn sq-btn-light sq-state-domain-button" data-model-dialog="${esc(row.assessment_id)}">${itemButton}</button>` : ""}</td><td>${esc(row.status || "-")}</td><td>${esc(number(row.response_zero))} / ${esc(number(row.response_one))} / ${esc(number(row.response_two))}</td><td>${completion}</td><td>${score}</td><td><small>Start: ${esc(row.start_date || "-")}</small><small>End: ${esc(row.end_date || "-")}</small></td><td><small>Completed: ${esc(row.completed_on || "-")}</small><small>Cancelled: ${esc(row.cancelled_on || "-")}</small></td></tr>`;
                    }).join("")}</tbody></table></div></details>`;
                }).join("")}</details></td></tr>`;
            }).join("")}</tbody></table>`
            : `<div class="sq-state-empty">No School assessment records available.</div>`;
        /* Flat assessment table retained below for future export-only use. */
        /* `<table class="sq-state-table sq-state-assess-table">
                <thead>
                    <tr>
                        <th>${esc(domainLabel("facility", "Facility"))}</th>
                        <th>Assessment</th>
                        <th>Status</th>
                        <th>${esc(domainLabel("departments", "Departments"))}</th>
                        <th>Checkpoints</th>
                        <th>Action Plans</th>
                        <th>Score</th>
                        <th>Domain Average / Category</th>
                        <th>Round Average / Category</th>
                        <th>Timeline</th>
                    </tr>
                </thead>
                <tbody>${rows.map(renderRow).join("")}</tbody>
            </table>`
            : `<div class="sq-state-empty">No assessment records available.</div>`; */
    }

    function renderRow(row) {
        const modelScore = row.model_score || null;
        const category = modelScore?.category?.name || "-";
        const roundScore = row.round_score || null;
        const roundCategory = roundScore?.category?.name || "-";
        return `
            <tr>
                <td>
                    <strong>${esc(row.fac_name || "-")}</strong>
                    <small>${esc(row.district || "-")} / ${esc(row.block || "-")}</small>
                    <small>${esc(domainLabel("facility_code", "NIN"))} ${esc(row.NIN_no || "-")}</small>
                </td>
                <td>
                    <strong>${esc(row.assessment_name || "-")}</strong>
                    <small>ID ${esc(row.assessment_id || "-")} | ${esc(row.framework_code || "-")}</small>
                    ${row.round_id ? `<small>Round ${esc(row.round_no || row.round_id)}</small>` : ""}
                    ${row.is_latest ? `<span class="sq-state-badge sq-state-latest">Latest</span>` : ""}
                </td>
                <td><span class="sq-state-badge">${esc(row.status || "-")}</span></td>
                <td>
                    ${progress(row.completed_departments || 0, row.total_departments || 0)}
                    <small>Left ${esc(row.pending_departments || 0)}</small>
                </td>
                <td>
                    ${progress(row.checkpoint_done || 0, row.total_checkpoints || 0)}
                    <small>Left ${esc(row.checkpoint_left || 0)}</small>
                </td>
                <td>
                    ${progress(row.completed_action_plans || 0, row.total_action_plans || 0)}
                    <small>Left ${esc(row.pending_action_plans || 0)}</small>
                </td>
                <td>${scoreCell(row)}</td>
                <td>
                    ${modelScore ? `
                        <strong>${esc(number(modelScore.percentage).toFixed(2))}%</strong>
                        <small>${esc(category)} · equal average of domains</small>
                        <button type="button" class="sq-btn sq-btn-light sq-state-domain-button" data-model-dialog="${esc(row.assessment_id)}">View five domains</button>` : "-"}
                </td>
                <td>
                    ${roundScore ? `<strong>${esc(number(roundScore.percentage).toFixed(2))}%</strong><small>${esc(roundCategory)} · ${esc(roundScore.completed_classes_departments)} completed</small><button type="button" class="sq-btn sq-btn-light sq-state-domain-button" data-round-domain-dialog="${esc(row.assessment_id)}">View round domains</button>` : "-"}
                </td>
                <td>
                    <small>Start ${esc(row.start_date || "-")}</small>
                    <small>Planned end ${esc(row.end_date || "-")}</small>
                    ${row.completed_on ? `<small>Completed ${esc(row.completed_on)}</small>` : ""}
                    ${row.cancelled_on ? `<small>Cancelled ${esc(row.cancelled_on)}</small>` : ""}
                </td>
            </tr>
        `;
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/assessment_progress.php", queryParams(), {
                loader: false,
                showError: false
            });
            const data = response.data || {};

            state.rows = data.rows || [];
            state.distribution = data.distribution || [];
            state.viewBy = data.view_by || "DIVISION";
            const viewSelect = document.getElementById("stateAssessViewBy");
            if (viewSelect?.value === "AUTO") {
                viewSelect.options[0].text = `Automatic (${state.viewBy.charAt(0) + state.viewBy.slice(1).toLowerCase()})`;
            }
            state.filterOptions = data.filter_options || { divisions: [], districts: [], blocks: [] };
            populateLocationFilters();
            renderSummary(data.summary || {});
            renderDistribution();
            renderRows(state.rows);
            state.pager.set(data.pagination || {}).render("stateAssessPager", "Facilities");
        } catch (error) {
            document.getElementById("stateAssessSummary").innerHTML = "";
            document.getElementById("stateAssessRows").innerHTML =
                `<div class="sq-state-empty">${esc(error.message || "Unable to load assessment progress.")}</div>`;
            state.pager.set({ page: 1, total_pages: 1, total_rows: 0 }).render("stateAssessPager", "Showing");
        }
    }

    async function exportAssessments() {
        const button = document.getElementById("stateAssessExport");
        // Export assessment rows, not the grouped facility rows used by the screen.
        const filters = Object.assign({}, queryParams(), { group_by_facility: 0, page: 1, per_page: 100 });
        const header = ["Facility ID", "Facility name", "NIN", "State", "Division", "District", "Block", "Assessment ID", "Assessment name", "Round", "Status", "Score 0", "Score 1", "Score 2", "Answered checkpoints", "Total checkpoints", "Checkpoint completion %", "Obtained score", "Total possible score", "Score %", "Start date", "Planned end date", "Completed on", "Cancelled on"];
        const output = [header];
        const csvValue = value => {
            let text = String(value ?? "");
            if (/^[\s]*[=+@\-\t\r]/.test(text)) text = "'" + text;
            return `"${text.replace(/"/g, '""')}"`;
        };
        button.disabled = true;
        button.textContent = "Preparing download...";
        try {
            let page = 1, totalPages = 1;
            do {
                const response = await SQ.api.get("/state/v1/assessment_progress.php", Object.assign({}, filters, { page }), { loader: false, showError: false, timeout: 120000 });
                const data = response.data || {};
                totalPages = number(data.pagination?.total_pages) || 1;
                for (const row of data.rows || []) {
                    const total = number(row.total_checkpoints), scoreTotal = number(row.total_score);
                    output.push([row.fac_id_fk, row.fac_name, row.NIN_no, row.state_name, row.division, row.district, row.block, row.assessment_id, row.assessment_name, row.round_no || row.round_id || "", row.status, row.response_zero, row.response_one, row.response_two, row.checkpoint_done, total || "", total ? (number(row.checkpoint_done) * 100 / total).toFixed(2) : "", row.obtained_score, scoreTotal || "", scoreTotal ? number(row.score_percent).toFixed(2) : "", row.start_date, row.end_date, row.completed_on, row.cancelled_on]);
                }
                page++;
            } while (page <= totalPages);
            const blob = new Blob(["\ufeff", output.map(row => row.map(csvValue).join(",")).join("\r\n")], { type: "text/csv;charset=utf-8" });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = "all-assessment-details.csv";
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (error) {
            if (SQ.notification?.error) SQ.notification.error(error?.message || "Unable to download assessment details.");
        } finally {
            button.disabled = false;
            button.textContent = "Download Excel (CSV)";
        }
    }

    async function init() {
        try {
            if (SQ.deployment && typeof SQ.deployment.load === "function") {
                await SQ.deployment.load();
                SQ.deployment.applyLabels(document);
                const facility = SQ.deployment.label ? SQ.deployment.label("facility", "Facility") : "Facility";
                const pageSubtitle = document.getElementById("sq-page-subtitle");
                if (pageSubtitle) pageSubtitle.textContent = `${facility}-wise assessment history and status.`;
            }
            state.pager = createPager();
            document.getElementById("stateAssessViewBy")?.addEventListener("change", function () { state.pager.reset(); load(); });
            document.getElementById("stateAssessExport")?.addEventListener("click", exportAssessments);
            document.getElementById("stateAssessRecordSearch")?.addEventListener("input", function () {
                state.recordSearch = this.value || "";
                renderRows(state.rows);
            });
            document.getElementById("stateAssessRefresh")?.addEventListener("click", function () {
                state.pager.reset();
                load();
            });
            bindSearch("stateAssessSearch");
            document.getElementById("stateAssessDivision")?.addEventListener("change", function () {
                const district = document.getElementById("stateAssessDistrict");
                const block = document.getElementById("stateAssessBlock");
                if (district) district.value = "";
                if (block) block.value = "";
                const viewSelect = document.getElementById("stateAssessViewBy");
                if (viewSelect?.value === "AUTO") viewSelect.options[0].text = "Automatic (District)";
                state.pager.reset();
                load();
            });
            document.getElementById("stateAssessDistrict")?.addEventListener("change", function () {
                const block = document.getElementById("stateAssessBlock");
                if (block) block.value = "";
                const viewSelect = document.getElementById("stateAssessViewBy");
                if (viewSelect?.value === "AUTO") viewSelect.options[0].text = this.value ? "Automatic (Block)" : (document.getElementById("stateAssessDivision")?.value ? "Automatic (District)" : "Automatic (Division)");
                state.pager.reset();
                load();
            });
            document.getElementById("stateAssessBlock")?.addEventListener("change", function () {
                state.pager.reset();
                load();
            });
            document.addEventListener("click", function (event) {
                const button = event.target.closest("[data-model-dialog]");
                if (button) openDomainDialog(button.getAttribute("data-model-dialog"));
                const roundButton = event.target.closest("[data-round-domain-dialog]");
                if (roundButton) openDomainDialog(roundButton.getAttribute("data-round-domain-dialog"), true);
            });
            await load();
        } catch (error) {
            document.getElementById("stateAssessRows").innerHTML = `<div class="sq-state-empty">${esc(error.message || "Unable to initialize assessment progress.")}</div>`;
        }
    }

    SQ.stateAssessmentProgress = { init };
})(window, document);
