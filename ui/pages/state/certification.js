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
        mix: { national: 0, conditionality: 0, state: 0 },
        upgradedFromStateToNational: 0,
        breakdown: [],
        breakdownPage: 1,
        breakdownPerPage: 5,
        viewBy: "DISTRICT",
        filterOptions: { districts: [], blocks: [] },
        warning: "",
        pager: null
    };

    const statuses = ["CONDITIONAL", "CERTIFIED"];

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
        const district = document.getElementById("stateCertDistrict");
        const block = document.getElementById("stateCertBlock");
        if (district) {
            const selected = district.value;
            district.innerHTML = `<option value="">All districts</option>${state.filterOptions.districts.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            district.value = state.filterOptions.districts.includes(selected) ? selected : "";
        }
        if (block) {
            const selected = block.value;
            block.innerHTML = `<option value="">All blocks</option>${state.filterOptions.blocks.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join("")}`;
            block.disabled = !district?.value;
            block.value = state.filterOptions.blocks.includes(selected) ? selected : "";
        }
    }

    function renderCards() {
        const counts = {};
        state.status.forEach(row => {
            counts[row.status] = Number(row.count) || 0;
        });
        const mix = {
            national: Number(state.mix.national) || 0,
            conditionality: Number(state.mix.conditionality) || 0,
            state: Number(state.mix.state) || 0
        };

        const cards = [
            ["TOTAL FACILITIES", state.status.reduce((sum, row) => sum + (Number(row.count) || 0), 0), "is-total"],
            ["NOT CERTIFIED", counts["NOT CERTIFIED"] || 0, "is-not-certified"],
            ["EXPIRING SOON", counts["EXPIRING SOON"] || 0, "is-expiring"],
            ["EXPIRED", counts.EXPIRED || 0, "is-expired"],
            ["CERTIFIED — NATIONAL", mix.national, "is-national"],
            ["NATIONAL CONDITIONALITY", mix.conditionality, "is-conditional"],
            ["CERTIFIED — STATE", mix.state, "is-state"],
            ["STATE → NATIONAL UPGRADES", state.upgradedFromStateToNational, "is-upgrade"]
        ];

        html("stateCertCards", cards.map(card => `
            <div class="sq-cert-kpi ${esc(card[2])}">
                <span>${esc(card[0])}</span>
                <strong>${esc(card[1])}</strong>
            </div>
        `).join(""));
    }

    function renderSummary() {
        const counts = {
            NATIONAL: Number(state.mix.national) || 0,
            CONDITIONAL: Number(state.mix.conditionality) || 0,
            STATE: Number(state.mix.state) || 0
        };
        const total = counts.NATIONAL + counts.CONDITIONAL + counts.STATE;
        if (!total) return html("stateCertRows", `<div class="sq-state-empty">No certification type data available.</div>`);
        const national = counts.NATIONAL * 100 / total, conditionality = counts.CONDITIONAL * 100 / total;
        const facilityTypeMax = Math.max(...state.facilityTypes.map(row => Number(row.count) || 0), 1);
        const facilityTypes = state.facilityTypes.length
            ? `<section class="sq-cert-facility-types"><h4>Facility type distribution</h4>${state.facilityTypes.map(row => `<div class="sq-cert-facility-type-row"><span>${esc(row.type)}</span><div class="sq-cert-facility-stacked"><i class="is-national" style="width:${(Number(row.national) || 0) * 100 / facilityTypeMax}%"></i><i class="is-conditionality" style="width:${(Number(row.conditionality) || 0) * 100 / facilityTypeMax}%"></i><i class="is-state" style="width:${(Number(row.state) || 0) * 100 / facilityTypeMax}%"></i></div><b>${esc(row.certified || 0)} / ${esc(row.count || 0)} (${esc(row.completion_percentage || 0)}%)<small>N ${esc(row.national || 0)} · C ${esc(row.conditionality || 0)} · S ${esc(row.state || 0)}</small></b></div>`).join("")}</section>`
            : `<section class="sq-cert-facility-types"><h4>Facility type distribution</h4><div class="sq-state-empty">No facility type data available.</div></section>`;
        return html("stateCertRows", `<div class="sq-cert-overview"><section class="sq-cert-mix-panel"><h4>Certification type mix</h4><div class="sq-cert-mix"><div class="sq-cert-donut" style="background:conic-gradient(#078b7a 0 ${national}%,#e59008 ${national}% ${national + conditionality}%,#3f5fc4 ${national + conditionality}% 100%)"></div></div><div class="sq-cert-legend"><div><i class="is-national"></i>Certified — National <b>${counts.NATIONAL} (${Math.round(national)}%)</b></div><div><i class="is-conditionality"></i>Conditional <b>${counts.CONDITIONAL} (${Math.round(conditionality)}%)</b></div><div><i class="is-state"></i>Certified — State <b>${counts.STATE} (${Math.round(counts.STATE * 100 / total)}%)</b></div></div></section>${facilityTypes}</div>`);
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
        text("stateCertBreakdownTitle", `${viewBy.replace("_", " ").replace(/\b\w/g, char => char.toUpperCase())}-wise certification completion`);

        html("stateCertChart", rows.length
            ? `<div class="sq-cert-bar-legend"><span><i class="is-national"></i>Certified — National</span><span><i class="is-conditionality"></i>Conditional</span><span><i class="is-state"></i>Certified — State</span></div><div class="sq-state-chart-bars">${pageRows.map(row => `
                <button class="sq-state-chart-row" type="button" ${viewBy === "DISTRICT" ? `data-cert-breakdown-district="${esc(row.label ?? "")}" title="Show certification mix for ${esc(row.label ?? "this district")}"` : ""}>
                    <span>${esc(row.label ?? row.status)}</span>
                    <div class="sq-cert-stacked"><i class="is-national" style="width:${(row.national || 0) * 100 / max}%"></i><i class="is-conditionality" style="width:${(row.conditionality || 0) * 100 / max}%"></i><i class="is-state" style="width:${(row.state || 0) * 100 / max}%"></i></div>
                    <b>${esc((row.certified ?? row.count) || 0)}/${esc((row.total ?? row.count) || 0)} (${esc(row.completion_percentage ?? row.percentage ?? 0)}%)<small>National ${esc(row.national || 0)} · Conditional ${esc(row.conditionality || 0)} · State ${esc(row.state || 0)}</small></b>
                </button>
            `).join("")}</div>`
            : `<div class="sq-state-empty">No comparison data.</div>`);
        html("stateCertBreakdownPager", rows.length > state.breakdownPerPage
            ? `<div class="sq-state-pagination"><button class="sq-btn sq-btn-light sq-btn-sm" type="button" data-cert-breakdown-page="${state.breakdownPage - 1}" ${state.breakdownPage === 1 ? "disabled" : ""}>Previous</button><span>Showing ${start + 1}–${Math.min(start + state.breakdownPerPage, rows.length)} of ${rows.length}</span><button class="sq-btn sq-btn-light sq-btn-sm" type="button" data-cert-breakdown-page="${state.breakdownPage + 1}" ${state.breakdownPage === totalPages ? "disabled" : ""}>Next</button></div>`
            : "");
        html("stateCertBreakdownBack", viewBy === "BLOCK"
            ? `<button class="sq-btn sq-btn-light sq-btn-sm" type="button" data-cert-show-districts><i class="bi bi-arrow-left"></i> District-wise</button>`
            : "");
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
        }
    }

    function renderAll() {
        renderWarning();
        renderCards();
        renderSummary();
        renderChart();
        renderTable();
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/certification_summary.php", state.pager.params({
                search: document.getElementById("stateCertSearch")?.value || "",
                status: document.getElementById("stateCertFilter")?.value || "",
                certification_type: document.getElementById("stateCertTypeFilter")?.value || "",
                district: document.getElementById("stateCertDistrict")?.value || "",
                block: document.getElementById("stateCertBlock")?.value || "",
                view_by: document.getElementById("stateCertDistrict")?.value ? "BLOCK" : "DISTRICT",
                _: Date.now()
            }), {
                loader: false,
                showError: false
            });

            state.facilities = response.data?.facilities || [];
            state.status = response.data?.status || [];
            state.types = response.data?.types || [];
            state.facilityTypes = response.data?.facility_types || [];
            state.mix = response.data?.mix || { national: 0, conditionality: 0, state: 0 };
            state.upgradedFromStateToNational = Number(response.data?.upgraded_from_state_to_national) || 0;
            state.breakdown = response.data?.breakdown || [];
            state.viewBy = response.data?.view_by || "DISTRICT";
            state.filterOptions = response.data?.filter_options || { districts: [], blocks: [] };
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
                district: document.getElementById("stateCertDistrict")?.value || "",
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
        document.getElementById("stateCertDistrict")?.addEventListener("change", function () {
            const block = document.getElementById("stateCertBlock");
            if (block) block.value = "";
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
                const showDistrictsButton = event.target.closest("[data-cert-show-districts]");
                if (showDistrictsButton) {
                    const districtSelect = document.getElementById("stateCertDistrict");
                    const blockSelect = document.getElementById("stateCertBlock");
                    if (districtSelect) districtSelect.value = "";
                    if (blockSelect) blockSelect.value = "";
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
