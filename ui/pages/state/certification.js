/*!
 * ==========================================================
 * SaQshi Open Source
 * State Certification Status
 * certification.js
 * Version 1.2.0 | Updated 2026-07-10
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    let initializedPage = null;
    let globalEventsBound = false;

    const state = {
        facilities: [],
        status: [],
        types: [],
        facilityTypes: [],
        mix: { national: 0, conditionality: 0, state: 0, state_conditionality: 0 },
        renewedByType: { national: 0, state: 0 },
        upgradedFromStateToNational: 0,
        breakdown: [],
        breakdownPage: 1,
        breakdownPerPage: 5,
        breakdownView: "DIVISION",
        viewBy: "DISTRICT",
        filterOptions: { districts: [], blocks: [] },
        warning: "",
        pager: null
    };

    const statuses = ["CONDITIONAL", "CERTIFIED"];
    const ALL_DISTRICTS_VALUE = "__ALL_DISTRICTS__";

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

    function html(id, value) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = value;
    }

    function text(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function renderWarning() {
        html("stateCertWarning", state.warning
            ? `<div class="sq-alert sq-alert-warning"><div class="sq-alert-content"><div class="sq-alert-title">Certification history notice</div><div class="sq-alert-text">${esc(state.warning)}</div></div></div>`
            : "");
    }

    function populateLocationFilters() {
        const division = document.getElementById("stateCertDivision");
        const district = document.getElementById("stateCertDistrict");
        const block = document.getElementById("stateCertBlock");
        if (division) {
            const selected = division.value;
            division.innerHTML = `<option value="">All divisions</option>${(state.filterOptions.divisions || []).map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            division.value = (state.filterOptions.divisions || []).includes(selected) ? selected : "";
        }
        if (district) {
            const selected = district.value;
            district.innerHTML = `<option value="${ALL_DISTRICTS_VALUE}">All districts</option>${state.filterOptions.districts.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            district.value = selected === ALL_DISTRICTS_VALUE || state.filterOptions.districts.includes(selected) ? selected : ALL_DISTRICTS_VALUE;
        }
        if (block) {
            const selected = block.value;
            block.innerHTML = `<option value="">All blocks</option>${state.filterOptions.blocks.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            block.disabled = !district?.value || district.value === ALL_DISTRICTS_VALUE;
            block.value = state.filterOptions.blocks.includes(selected) ? selected : "";
        }
    }

    function groupedCertificationMix() {
        const mix = {
            national: Number(state.mix.national) || 0,
            conditionality: Number(state.mix.conditionality) || 0,
            state: Number(state.mix.state) || 0,
            stateConditionality: Number(state.mix.state_conditionality) || 0
        };
        const conditionalFromStatus = state.status.reduce(function (total, row) {
            const status = String(row.status || "").trim().toUpperCase();
            return total + (['CONDITIONAL', 'CONDITIONAL CERTIFIED', 'CONDITIONALLY CERTIFIED'].includes(status)
                ? (Number(row.count) || 0)
                : 0);
        }, 0);
        const alreadyClassified = mix.conditionality + mix.stateConditionality;

        // Old migrated conditional records did not always retain a type. They
        // are State conditional unless explicitly recorded as National.
        if (conditionalFromStatus > alreadyClassified) {
            mix.stateConditionality += conditionalFromStatus - alreadyClassified;
        }

        return mix;
    }

    function renderCards() {
        const counts = {};
        state.status.forEach(row => {
            counts[row.status] = Number(row.count) || 0;
        });
        const mix = groupedCertificationMix();
        const renewed = state.renewedByType || {};

        const cards = [
            ["TOTAL FACILITIES", state.status.reduce((sum, row) => sum + (Number(row.count) || 0), 0), "is-total"],
            ["NOT CERTIFIED", counts["NOT CERTIFIED"] || 0, "is-not-certified"],
            ["EXPIRING SOON", counts["EXPIRING SOON"] || 0, "is-expiring"],
            ["EXPIRED", counts.EXPIRED || 0, "is-expired"]
        ];

        const certificationCards = [
            ["NATIONAL CERTIFICATION", mix.national, mix.conditionality, "is-national", renewed.national || 0],
            ["STATE CERTIFICATION", mix.state, mix.stateConditionality, "is-state", renewed.state || 0],
        ];

        const standardCards = cards.map(card => `
            <div class="sq-cert-kpi ${esc(card[2])}">
                <span>${esc(card[0])}</span>
                <strong>${esc(card[1])}</strong>
            </div>
        `).join("");
        const groupedCertificationCards = certificationCards.map(card => `
            <div class="sq-cert-kpi sq-cert-kpi--split ${esc(card[3])}">
                <span>${esc(card[0])}</span>
                <strong>${esc(card[1] + card[2])}</strong>
                <small><b>Certified</b><b>${esc(card[1])}</b></small>
                <small><b>Conditional</b><b>${esc(card[2])}</b></small>
                <em class="sq-cert-renewal-info">Renewed ${esc(card[4])}</em>
            </div>
        `).join("");
        const upgradeCard = `
            <div class="sq-cert-kpi is-upgrade">
                <span>STATE → NATIONAL UPGRADES</span>
                <strong>${esc(state.upgradedFromStateToNational)}</strong>
                <i class="bi bi-graph-up-arrow sq-cert-upgrade-trend" aria-hidden="true"></i>
            </div>
        `;

        html("stateCertCards", standardCards + groupedCertificationCards + upgradeCard);
    }

    function renderSummary() {
        const mix = groupedCertificationMix();
        const counts = {
            NATIONAL: mix.national,
            CONDITIONAL: mix.conditionality,
            STATE: mix.state,
            STATE_CONDITIONAL: mix.stateConditionality
        };
        const total = counts.NATIONAL + counts.CONDITIONAL + counts.STATE + counts.STATE_CONDITIONAL;
        if (!total) return html("stateCertRows", `<div class="sq-state-empty">No certification type data available.</div>`);
        const national = counts.NATIONAL * 100 / total, conditionality = counts.CONDITIONAL * 100 / total, statePercent = counts.STATE * 100 / total, stateConditionality = counts.STATE_CONDITIONAL * 100 / total;
        const facilityTypeMax = Math.max(...state.facilityTypes.map(row => Number(row.count) || 0), 1);
        const facilityTypes = state.facilityTypes.length
            ? `<section class="sq-cert-facility-types"><h4>Facility type distribution</h4>${state.facilityTypes.map(row => `<div class="sq-cert-facility-type-row"><span>${esc(row.type)}</span><div class="sq-cert-facility-stacked"><i class="is-national" style="width:${(Number(row.national) || 0) * 100 / facilityTypeMax}%"></i><i class="is-conditionality" style="width:${(Number(row.conditionality) || 0) * 100 / facilityTypeMax}%"></i><i class="is-state" style="width:${(Number(row.state) || 0) * 100 / facilityTypeMax}%"></i><i class="is-state-conditional" style="width:${(Number(row.state_conditionality) || 0) * 100 / facilityTypeMax}%"></i></div><b>${esc(row.certified || 0)} / ${esc(row.count || 0)} (${esc(row.completion_percentage || 0)}%)<small>N ${esc(row.national || 0)} · NC ${esc(row.conditionality || 0)} · S ${esc(row.state || 0)} · SC ${esc(row.state_conditionality || 0)}</small></b></div>`).join("")}</section>`
            : `<section class="sq-cert-facility-types"><h4>Facility type distribution</h4><div class="sq-state-empty">No facility type data available.</div></section>`;
        return html("stateCertRows", `<div class="sq-cert-overview"><section class="sq-cert-mix-panel"><h4>Certification type mix</h4><div class="sq-cert-mix"><div class="sq-cert-donut" style="background:conic-gradient(#078b7a 0 ${national}%,#e59008 ${national}% ${national + conditionality}%,#3f5fc4 ${national + conditionality}% ${national + conditionality + statePercent}%,#c026d3 ${national + conditionality + statePercent}% 100%)"></div></div><div class="sq-cert-legend"><div><i class="is-national"></i>Certified — National <b>${counts.NATIONAL} (${Math.round(national)}%)</b></div><div><i class="is-conditionality"></i>National conditionality <b>${counts.CONDITIONAL} (${Math.round(conditionality)}%)</b></div><div><i class="is-state"></i>Certified — State <b>${counts.STATE} (${Math.round(statePercent)}%)</b></div><div><i class="is-state-conditional"></i>State conditionality <b>${counts.STATE_CONDITIONAL} (${Math.round(stateConditionality)}%)</b></div></div></section>${facilityTypes}</div>`);
        /* legacy status summary retained below for reference */
        html("stateCertRows", state.status.length
            ? `<table class="sq-state-table">
                <thead><tr><th>Status</th><th>Facilities</th><th>%</th></tr></thead>
                <tbody>${state.status.map(row => `
                    <tr>
                        <td>${esc(row.status)}</td>
                        <td>${esc(row.count)}</td>
                        <td>${esc(row.percentage ?? 0)}%</td>
                    </tr>
                `).join("")}</tbody>
            </table>`
            : `<div class="sq-state-empty">No certification status available.</div>`);
    }

    function renderChart() {
        const rows = state.breakdown.length ? state.breakdown : state.status;
        const max = Math.max(...rows.map(row => Number(row.total ?? row.count) || 0), 1);
        const viewBy = state.viewBy || "DISTRICT";
        const totalPages = Math.max(1, Math.ceil(rows.length / state.breakdownPerPage));
        state.breakdownPage = Math.min(state.breakdownPage, totalPages);
        const start = (state.breakdownPage - 1) * state.breakdownPerPage;
        const pageRows = rows.slice(start, start + state.breakdownPerPage);
        text("stateCertBreakdownTitle", `Certification Comparison — ${viewBy.replace("_", " ").replace(/\b\w/g, char => char.toUpperCase())}`);
        html("stateCertComparisonSwitch", ["DIVISION", "DISTRICT", "FACILITY_TYPE"].map(level =>
            `<button class="sq-cert-comparison-option ${level === viewBy ? "is-active" : ""}" type="button" data-cert-comparison-view="${level}">${level === "FACILITY_TYPE" ? "Facility type" : level[0] + level.slice(1).toLowerCase()}</button>`
        ).join(""));

        html("stateCertChart", rows.length
            ? `<div class="sq-cert-bar-legend"><span><i class="is-national"></i>Certified — National</span><span><i class="is-conditionality"></i>National conditionality</span><span><i class="is-state"></i>Certified — State</span><span><i class="is-state-conditional"></i>State conditionality</span></div><div class="sq-state-chart-bars">${pageRows.map(row => `
                <button class="sq-state-chart-row" type="button" ${viewBy === "DIVISION" ? `data-cert-breakdown-division="${esc(row.label ?? "")}" title="Show districts for ${esc(row.label ?? "this division")}"` : (viewBy === "DISTRICT" ? `data-cert-breakdown-district="${esc(row.label ?? "")}" title="Show blocks for ${esc(row.label ?? "this district")}"` : "")}>
                    <span>${esc(row.label ?? row.status)}</span>
                    <div class="sq-cert-stacked"><i class="is-national" style="width:${(row.national || 0) * 100 / max}%"></i><i class="is-conditionality" style="width:${(row.conditionality || 0) * 100 / max}%"></i><i class="is-state" style="width:${(row.state || 0) * 100 / max}%"></i><i class="is-state-conditional" style="width:${(row.state_conditionality || 0) * 100 / max}%"></i></div>
                    <b>${esc((row.certified ?? row.count) || 0)}/${esc((row.total ?? row.count) || 0)} (${esc(row.completion_percentage ?? row.percentage ?? 0)}%)<small title="N = National; NC = National conditionality; S = State; SC = State conditionality">N ${esc(row.national || 0)} · NC ${esc(row.conditionality || 0)} · S ${esc(row.state || 0)} · SC ${esc(row.state_conditionality || 0)}</small></b>
                </button>
            `).join("")}</div>`
            : `<div class="sq-state-empty">No comparison data.</div>`);
        html("stateCertBreakdownPager", rows.length > state.breakdownPerPage
            ? `<div class="sq-state-pagination"><button class="sq-btn sq-btn-light sq-btn-sm sq-cert-page-icon" type="button" data-cert-breakdown-page="${state.breakdownPage - 1}" ${state.breakdownPage === 1 ? "disabled" : ""} aria-label="Previous page" title="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></button><span>${start + 1}–${Math.min(start + state.breakdownPerPage, rows.length)} / ${rows.length}</span><button class="sq-btn sq-btn-light sq-btn-sm sq-cert-page-icon" type="button" data-cert-breakdown-page="${state.breakdownPage + 1}" ${state.breakdownPage === totalPages ? "disabled" : ""} aria-label="Next page" title="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></button></div>`
            : "");
        html("stateCertBreakdownBack", viewBy === "BLOCK"
            ? `<button class="sq-btn sq-btn-light sq-btn-sm" type="button" data-cert-show-districts><i class="bi bi-arrow-left"></i> District-wise</button>`
            : (viewBy === "DISTRICT" ? `<button class="sq-btn sq-btn-light sq-btn-sm" type="button" data-cert-show-divisions><i class="bi bi-arrow-left"></i> Division-wise</button>` : ""));
    }

    function renderTable() {
        const visibleRows = state.facilities;
        const pagerState = state.pager ? state.pager.state() : { totalRows: visibleRows.length };
        text("stateCertCount", `${pagerState.totalRows} facilities`);

        html("stateCertTable", visibleRows.length
            ? `<table class="sq-state-table">
                <thead>
                    <tr>
                        <th>${esc(domainLabel("facility", "Facility"))}</th>
                        <th>${esc(domainLabel("facility_code", "NIN"))}</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Certification</th>
                        <th>Score</th>
                        <th>Renewal</th>
                        <th>History</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>${visibleRows.map(row => `
                    <tr>
                        <td><strong>${esc(row.fac_name || "-")}</strong><br><small>${esc(row.facility_type || "-")}</small></td>
                        <td>${esc(row.fac_nin || "-")}</td>
                        <td>${esc(row.district || "-")}<br><small>${esc(row.block || "")}</small></td>
                        <td><span class="sq-state-badge">${esc(row.status || "NOT CERTIFIED")}</span></td>
                        <td>${esc(row.certification_type || "-")}<br><small>${esc(row.assessment_mode || "-")} | ${esc(row.certification_date || "-")} ${row.valid_to ? `to ${esc(row.valid_to)}` : ""}</small><br><small>Applied ${esc(row.applied_date || "-")}</small></td>
                        <td>${row.score !== null && row.score !== undefined ? esc(row.score) : "-"}</td>
                        <td>${esc(row.renewal_status || "-")}<br><small>${row.valid_from ? `From ${esc(row.valid_from)}` : ""}</small></td>
                        <td>${esc(row.last_action || "-")}<br><small>${esc(row.last_updated_on || "")}</small></td>
                        <td>
                            <div class="sq-state-inline-update">
                                <button class="sq-btn sq-btn-primary" data-cert-update="${esc(row.fac_id)}">Update</button>
                            </div>
                        </td>
                    </tr>
                `).join("")}</tbody>
            </table>`
            : `<div class="sq-state-empty">No facilities match the selected filter.</div>`);

        renderPager();
    }

    function renderPager() {
        if (state.pager) {
            state.pager.render("stateCertPagerTop", "Showing");
            state.pager.render("stateCertPager", "Showing");
            ["stateCertPagerTop", "stateCertPager"].forEach(function (id) {
                const pager = document.getElementById(id);
                const previous = pager?.querySelector("[data-sq-page='prev']");
                const next = pager?.querySelector("[data-sq-page='next']");
                if (previous) {
                    previous.classList.add("sq-cert-history-page-icon", "is-previous");
                    previous.setAttribute("aria-label", "Previous page");
                    previous.setAttribute("title", "Previous page");
                    previous.innerHTML = '<i class="bi bi-chevron-left" aria-hidden="true"></i>';
                }
                if (next) {
                    next.classList.add("sq-cert-history-page-icon", "is-next");
                    next.setAttribute("aria-label", "Next page");
                    next.setAttribute("title", "Next page");
                    next.innerHTML = '<i class="bi bi-chevron-right" aria-hidden="true"></i>';
                }
            });
        }
    }

    function renderVisualChart() {
        const rows = state.breakdown.length ? state.breakdown : [];
        const max = Math.max(...rows.map(row => Number(row.total ?? row.count) || 0), 1);
        const viewBy = state.viewBy || "DIVISION";
        const labels = { DIVISION: "Division", DISTRICT: "District", BLOCK: "Block", FACILITY_TYPE: "Facility type" };
        const hasDistrict = Boolean(selectedDistrictFilter());
        text("stateCertVisualTitle", `Certification Visual Comparison — ${labels[viewBy] || viewBy}`);
        html("stateCertVisualSwitch", ["DIVISION", "DISTRICT", "BLOCK", "FACILITY_TYPE"].map(function (level) {
            const disabled = level === "BLOCK" && !hasDistrict;
            return `<button class="sq-cert-comparison-option ${level === viewBy ? "is-active" : ""}" type="button" data-cert-comparison-view="${level}" ${disabled ? "disabled title=\"Select a district to compare blocks\"" : ""}>${labels[level]}</button>`;
        }).join(""));
        const visualRows = rows;
        html("stateCertVisualChart", visualRows.length
            ? `<div class="sq-cert-visual-bars">${visualRows.map(function (row) {
                const total = Number(row.total ?? row.count) || 0;
                const pct = Math.round((Number(row.certified ?? row.count) || 0) * 100 / Math.max(total, 1));
                const certified = (Number(row.national) || 0) + (Number(row.conditionality) || 0) + (Number(row.state) || 0) + (Number(row.state_conditionality) || 0);
                const barHeight = Math.max(5, Math.min(90, Math.round(pct * 0.95)));
                return `<div class="sq-cert-visual-bar" title="${esc(row.label || row.status || "")} — ${pct}% certified"><div class="sq-cert-visual-stack" style="height:${barHeight}%"><i class="is-national" style="height:${(Number(row.national) || 0) * 100 / Math.max(certified, 1)}%"></i><i class="is-conditionality" style="height:${(Number(row.conditionality) || 0) * 100 / Math.max(certified, 1)}%"></i><i class="is-state" style="height:${(Number(row.state) || 0) * 100 / Math.max(certified, 1)}%"></i><i class="is-state-conditional" style="height:${(Number(row.state_conditionality) || 0) * 100 / Math.max(certified, 1)}%"></i></div><b>${pct}%</b><span>${esc(row.label || row.status || "-")}</span></div>`;
            }).join("")}</div>`
            : `<div class="sq-state-empty">No comparison data for this view.</div>`);
    }

    function renderAll() {
        renderWarning();
        renderCards();
        renderSummary();
        renderChart();
        renderVisualChart();
        renderTable();
    }

    function selectedDistrictFilter() {
        const value = document.getElementById("stateCertDistrict")?.value || "";
        return value === ALL_DISTRICTS_VALUE ? "" : value;
    }

    async function load() {
        try {
            const selectedDivision = document.getElementById("stateCertDivision")?.value || "";
            const selectedDistrict = selectedDistrictFilter();
            const response = await SQ.api.get("/state/v1/certification_summary.php", state.pager.params({
                search: document.getElementById("stateCertSearch")?.value || "",
                status: document.getElementById("stateCertFilter")?.value || "",
                certification_type: document.getElementById("stateCertTypeFilter")?.value || "",
                division: selectedDivision,
                district: selectedDistrict,
                block: document.getElementById("stateCertBlock")?.value || "",
                view_by: state.breakdownView === "FACILITY_TYPE" ? "FACILITY_TYPE" : (selectedDistrict ? "BLOCK" : state.breakdownView),
                _: Date.now()
            }), {
                loader: false,
                showError: false
            });

            state.facilities = response.data?.facilities || [];
            state.status = response.data?.status || [];
            state.types = response.data?.types || [];
            state.facilityTypes = response.data?.facility_types || [];
            state.mix = response.data?.mix || { national: 0, conditionality: 0, state: 0, state_conditionality: 0 };
            state.renewedByType = response.data?.renewed_by_type || { national: 0, state: 0 };
            state.upgradedFromStateToNational = Number(response.data?.upgraded_from_state_to_national) || 0;
            state.breakdown = response.data?.breakdown || [];
            state.viewBy = response.data?.view_by || "DISTRICT";
            state.filterOptions = response.data?.filter_options || { divisions: [], districts: [], blocks: [] };
            state.warning = response.data?._warning || "";
            state.pager.set(response.data?.pagination || {
                page: 1,
                per_page: 5,
                total_rows: state.facilities.length,
                total_pages: 1
            });

            populateLocationFilters();
            renderAll();
        } catch (error) {
            console.error(error);
            html("stateCertTable", `<div class="sq-state-empty">${esc(error.message || "Unable to load certification history.")}</div>`);
        }
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value ?? "";
    }

    function openUpdateModal(facId) {
        const row = state.facilities.find(item => Number(item.fac_id) === Number(facId));
        if (!row) return;

        html("stateCertEditStatus", statuses.map(status => `<option value="${esc(status)}" ${row.status === status ? "selected" : ""}>${esc(status)}</option>`).join(""));
        setValue("stateCertFacId", row.fac_id);
        setValue("stateCertFacilityName", row.fac_name || "");
        setValue("stateCertFacilityNin", row.fac_nin || "");
        setValue("stateCertType", row.certification_type || "STATE");
        setValue("stateCertAssessmentMode", row.assessment_mode || "PHYSICAL");
        setValue("stateCertDate", row.certification_date || "");
        setValue("stateCertAppliedDate", row.applied_date || "");
        setValue("stateCertScore", row.score ?? "");
        setValue("stateCertRemarks", row.remarks || "");

        const modal = document.getElementById("stateCertModal");
        if (modal) modal.hidden = false;
    }

    function closeUpdateModal() {
        const modal = document.getElementById("stateCertModal");
        if (modal) modal.hidden = true;
    }

    async function saveUpdate(event) {
        event.preventDefault();
        const facId = document.getElementById("stateCertFacId")?.value || "";
        const row = state.facilities.find(item => Number(item.fac_id) === Number(facId));
        if (!row) return;
        const certDate = document.getElementById("stateCertDate")?.value || "";
        const appliedDate = document.getElementById("stateCertAppliedDate")?.value || "";
        const today = new Date().toISOString().slice(0, 10);

        if (certDate && certDate > today) {
            if (SQ.notification) SQ.notification.warning("Certification date cannot be a future date.");
            return;
        }

        if (appliedDate && certDate && appliedDate > certDate) {
            if (SQ.notification) SQ.notification.warning("Applied date cannot be greater than certification date.");
            return;
        }

        try {
            await SQ.api.post("/state/v1/certification_update.php", {
                fac_id: row.fac_id,
                fac_nin: row.fac_nin,
                fac_name: row.fac_name,
                fac_type: row.facility_type,
                status: document.getElementById("stateCertEditStatus")?.value || "",
                certification_type: document.getElementById("stateCertType")?.value || "",
                assessment_mode: document.getElementById("stateCertAssessmentMode")?.value || "",
                certification_date: certDate,
                applied_date: appliedDate,
                score: document.getElementById("stateCertScore")?.value || "",
                remarks: document.getElementById("stateCertRemarks")?.value || ""
            }, { loader: true });

            if (SQ.notification) SQ.notification.success("Certification status updated");
            closeUpdateModal();
            await load();
        } catch (error) {
            if (SQ.notification) SQ.notification.error(error.message || "Update failed");
        }
    }

    async function downloadCsv() {
        let rows = [];
        try {
            const response = await SQ.api.get("/state/v1/certification_summary.php", {
                _all_facilities: 1,
                search: document.getElementById("stateCertSearch")?.value || "",
                division: document.getElementById("stateCertDivision")?.value || "",
                district: selectedDistrictFilter(),
                block: document.getElementById("stateCertBlock")?.value || "",
                _: Date.now()
            }, { loader: true, showError: false });
            rows = response.data?.facilities || [];
        } catch (error) {
            if (SQ.notification) SQ.notification.error(error.message || "Unable to prepare the certification download.");
            return;
        }
        const header = [
            domainLabel("facility_code", "NIN"),
            domainLabel("facility", "Facility") + " Name",
            "Division",
            "District",
            "Block",
            "Facility Type",
            "Status",
            "Certification Type",
            "Assessment Mode",
            "Certification Date",
            "Applied Date",
            "Valid From",
            "Valid To",
            "Renewal Status",
            "Score",
            "Last Action",
            "Last Updated",
            "Remarks"
        ];
        const body = rows.map(row => [
            row.fac_nin,
            row.fac_name,
            row.division,
            row.district,
            row.block,
            row.facility_type,
            row.status,
            row.certification_type,
            row.assessment_mode,
            row.certification_date,
            row.applied_date,
            row.valid_from,
            row.valid_to,
            row.renewal_status,
            row.score,
            row.last_action,
            row.last_updated_on,
            row.remarks
        ]);
        const lines = [header, ...body]
            .map(row => row.map(value => `"${String(value ?? "").replace(/"/g, '""')}"`).join(","))
            .join("\r\n");
        const blob = new Blob([lines], { type: "text/csv;charset=utf-8" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `state-certification-status-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    async function init() {
        const page = document.getElementById("stateCertCards")?.closest(".sq-state-page");
        if (!page || page === initializedPage) return;
        initializedPage = page;
        if (SQ.deployment && typeof SQ.deployment.load === "function") {
            await SQ.deployment.load();
            SQ.deployment.applyLabels(document);
        }
        document.getElementById("stateCertRefresh")?.addEventListener("click", load);
        let searchTimer = null;
        document.getElementById("stateCertSearch")?.addEventListener("input", function () {
            state.pager.reset();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 300);
        });
        document.getElementById("stateCertFilter")?.addEventListener("change", function () {
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertDivision")?.addEventListener("change", function () {
            const district = document.getElementById("stateCertDistrict");
            const block = document.getElementById("stateCertBlock");
            if (district) district.value = ALL_DISTRICTS_VALUE;
            if (block) block.value = "";
            state.breakdownView = this.value ? "DISTRICT" : "DIVISION";
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertDistrict")?.addEventListener("change", function () {
            const block = document.getElementById("stateCertBlock");
            if (block) block.value = "";
            state.breakdownView = "DISTRICT";
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertDistrict")?.addEventListener("click", function () {
            if (this.value !== ALL_DISTRICTS_VALUE || state.breakdownView === "DISTRICT") return;
            state.breakdownView = "DISTRICT";
            state.breakdownPage = 1;
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertBlock")?.addEventListener("change", function () {
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertTypeFilter")?.addEventListener("change", function () {
            state.pager.reset();
            load();
        });
        document.getElementById("stateCertDownload")?.addEventListener("click", () => { downloadCsv(); });
        document.getElementById("stateCertForm")?.addEventListener("submit", saveUpdate);
        document.getElementById("stateCertModalClose")?.addEventListener("click", closeUpdateModal);
        document.getElementById("stateCertCancel")?.addEventListener("click", closeUpdateModal);
        if (!globalEventsBound) {
            document.addEventListener("click", function (event) {
                const button = event.target.closest("[data-cert-update]");
                if (button) openUpdateModal(button.getAttribute("data-cert-update"));
                const breakdownButton = event.target.closest("[data-cert-breakdown-page]");
                if (breakdownButton && !breakdownButton.disabled) {
                    state.breakdownPage = Number(breakdownButton.getAttribute("data-cert-breakdown-page")) || 1;
                    renderChart();
                }
                const comparisonButton = event.target.closest("[data-cert-comparison-view]");
                if (comparisonButton) {
                    const level = comparisonButton.getAttribute("data-cert-comparison-view") || "DIVISION";
                    const divisionSelect = document.getElementById("stateCertDivision");
                    const districtSelect = document.getElementById("stateCertDistrict");
                    const blockSelect = document.getElementById("stateCertBlock");
                    if (level === "BLOCK" && !selectedDistrictFilter()) {
                        if (SQ.notification) SQ.notification.warning("Select a district before comparing blocks.");
                        return;
                    }
                    state.breakdownView = level;
                    if (level === "DIVISION") {
                        if (divisionSelect) divisionSelect.value = "";
                        if (districtSelect) districtSelect.value = ALL_DISTRICTS_VALUE;
                        if (blockSelect) blockSelect.value = "";
                    } else if (level === "DISTRICT") {
                        if (districtSelect) districtSelect.value = ALL_DISTRICTS_VALUE;
                        if (blockSelect) blockSelect.value = "";
                    }
                    state.breakdownPage = 1;
                    state.pager.reset();
                    load();
                }
                const districtButton = event.target.closest("[data-cert-breakdown-district]");
                if (districtButton) {
                    const district = districtButton.getAttribute("data-cert-breakdown-district") || "";
                    const districtSelect = document.getElementById("stateCertDistrict");
                    if (!district || !districtSelect) return;
                    districtSelect.value = district;
                    state.breakdownPage = 1;
                    state.pager.reset();
                    load();
                }
                const divisionButton = event.target.closest("[data-cert-breakdown-division]");
                if (divisionButton) {
                    const division = divisionButton.getAttribute("data-cert-breakdown-division") || "";
                    const divisionSelect = document.getElementById("stateCertDivision");
                    if (!division || !divisionSelect) return;
                    divisionSelect.value = division;
                    state.breakdownView = "DISTRICT";
                    state.breakdownPage = 1;
                    state.pager.reset();
                    load();
                }
                const showDistrictsButton = event.target.closest("[data-cert-show-districts]");
                if (showDistrictsButton) {
                    const districtSelect = document.getElementById("stateCertDistrict");
                    const blockSelect = document.getElementById("stateCertBlock");
                    if (districtSelect) districtSelect.value = ALL_DISTRICTS_VALUE;
                    if (blockSelect) blockSelect.value = "";
                    state.breakdownView = "DISTRICT";
                    state.breakdownPage = 1;
                    state.pager.reset();
                    load();
                }
                const showDivisionsButton = event.target.closest("[data-cert-show-divisions]");
                if (showDivisionsButton) {
                    const divisionSelect = document.getElementById("stateCertDivision");
                    const districtSelect = document.getElementById("stateCertDistrict");
                    const blockSelect = document.getElementById("stateCertBlock");
                    if (divisionSelect) divisionSelect.value = "";
                    if (districtSelect) districtSelect.value = ALL_DISTRICTS_VALUE;
                    if (blockSelect) blockSelect.value = "";
                    state.breakdownView = "DIVISION";
                    state.breakdownPage = 1;
                    state.pager.reset();
                    load();
                }
            });
            globalEventsBound = true;
        }

        state.pager = SQ.pagination.create({
            page: 1,
            perPage: 5,
            onChange: load
        });
        await load();
    }

    SQ.stateCertification = { init };
    document.addEventListener("sq:page-ready", function (event) {
        if (event.detail?.route === "state/certification") init();
    });
})(window, document);
