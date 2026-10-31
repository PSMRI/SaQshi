(function (window, document) {
    "use strict";
    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { data: {}, filters: { divisions: [], districts: [] } };
    let previewMap = null, previewMapLayer = null, previewMapMask = null;
    const n = value => Number.isFinite(Number(value)) ? Number(value) : 0;
    const esc = value => String(value ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
    const pct = (part, whole) => whole > 0 ? Math.round((n(part) / n(whole)) * 100) : 0;

    function parameters() {
        return { group_by_facility: 0, per_page: 100, page: 1, view_by: "DISTRICT", division: document.getElementById("assessmentPreviewDivision")?.value || "", district: document.getElementById("assessmentPreviewDistrict")?.value || "" };
    }

    function populateFilters() {
        const division = document.getElementById("assessmentPreviewDivision");
        const district = document.getElementById("assessmentPreviewDistrict");
        const selectedDivision = division?.value || "";
        const selectedDistrict = district?.value || "";
        if (division) {
            division.innerHTML = `<option value="">All divisions</option>${(state.filters.divisions || []).map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join("")}`;
            division.value = (state.filters.divisions || []).includes(selectedDivision) ? selectedDivision : "";
        }
        if (district) {
            district.disabled = false;
            district.innerHTML = `<option value="">All districts</option>${(state.filters.districts || []).map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join("")}`;
            district.value = (state.filters.districts || []).includes(selectedDistrict) ? selectedDistrict : "";
        }
    }

    function renderKpis(summary) {
        const target = document.getElementById("assessmentPreviewKpis");
        if (!target) return;
        const values = [
            ["Registered facilities", n(summary.registered_facilities), "is-total"],
            ["Facilities assessed", n(summary.facilities_with_assessment), "is-blue"],
            ["Completed assessments", n(summary.completed), "is-green"],
            ["Active assessments", n(summary.active), "is-amber"],
            ["No assessment", n(summary.facilities_without_assessment), "is-red"]
        ];
        target.innerHTML = values.map(([label, value, tone]) => `<article class="sq-assessment-preview-kpi ${tone}"><span>${label}</span><strong>${esc(value)}</strong></article>`).join("");
    }

    function renderLifecycle(summary) {
        const registered = n(summary.registered_facilities), assessed = n(summary.facilities_with_assessment), completed = n(summary.completed_facilities), active = n(summary.active_facilities), noAssessment = n(summary.facilities_without_assessment);
        const stages = [["Registered", registered, "bi-buildings"], ["Assessed", assessed, "bi-clipboard-check"], ["Completed", completed, "bi-check2-circle"], ["Active", active, "bi-arrow-repeat"], ["No assessment", noAssessment, "bi-dash-circle"]];
        const target = document.getElementById("assessmentPreviewLifecycle");
        const isOperations = document.querySelector("[data-preview-type]")?.dataset.previewType === "operations";
        target.innerHTML = isOperations
            ? `<div class="sq-assessment-rings">${stages.slice(1).map(([label, value, icon]) => `<div class="sq-assessment-ring"><i class="bi ${icon}"></i><strong>${esc(value)}</strong><span>${label}</span><small>${esc(pct(value, registered))}% of registered</small></div>`).join("")}</div>`
            : `<div class="sq-assessment-lifecycle">${stages.map(([label, value, icon], index) => `<div class="sq-assessment-stage"><i class="bi ${icon}"></i><span>${label}</span><strong>${esc(value)}</strong><small>${esc(pct(value, registered))}% of registered</small>${index < stages.length - 1 ? '<b class="bi bi-chevron-right"></b>' : ""}</div>`).join("")}</div>`;
    }

    function renderDistricts(rows) {
        const target = document.getElementById("assessmentPreviewDistricts");
        const sorted = (rows || []).slice().sort((a, b) => n(b.completion_percentage) - n(a.completion_percentage)).slice(0, 10);
        target.innerHTML = sorted.length ? `<div class="sq-assessment-preview-districts">${sorted.map(row => `<div class="sq-assessment-preview-district"><span>${esc(row.label)}</span><div><i style="width:${Math.max(0, Math.min(100, n(row.completion_percentage)))}%"></i></div><b>${esc(row.completion_percentage)}%</b><small>${esc(row.completed)} / ${esc(row.total)}</small></div>`).join("")}</div>` : '<div class="sq-state-empty">No district data for the selected filters.</div>';
    }

    function renderAttention(rows) {
        const active = (rows || []).filter(row => String(row.status || "").toUpperCase() === "ACTIVE").sort((a, b) => pct(a.checkpoint_done, a.total_checkpoints) - pct(b.checkpoint_done, b.total_checkpoints)).slice(0, 6);
        document.getElementById("assessmentPreviewAttention").innerHTML = active.length ? `<div class="sq-assessment-attention-list">${active.map(row => { const value = pct(row.checkpoint_done, row.total_checkpoints); return `<div><span class="sq-assessment-attention-score">${esc(value)}%</span><section><strong>${esc(row.fac_name || "Facility")}</strong><small>${esc(row.district || "-")} · ${esc(row.assessment_name || "Assessment")}</small><em><i style="width:${esc(value)}%"></i></em></section><a href="/ui/dashboard.html?route=state%2Fassessment-progress">Open</a></div>`; }).join("")}</div>` : '<div class="sq-state-empty">No active assessments need follow-up for the selected filters.</div>';
    }

    function districtKey(value) {
        const key = String(value || "").trim().toUpperCase().replace(/[^A-Z0-9]+/g, "_");
        return ({ PASHCHIM_CHAMPARAN: "WEST_CHAMPARAN", PURBA_CHAMPARAN: "EAST_CHAMPARAN" })[key] || key;
    }

    function mapColor(value) {
        const score = n(value);
        return score >= 70 ? "#159b51" : (score >= 40 ? "#e48a08" : "#d63b43");
    }

    function boundaryHoles(boundary) {
        const holes = [];
        (boundary?.features || []).forEach(feature => {
            const geometry = feature?.geometry;
            if (geometry?.type === "Polygon" && Array.isArray(geometry.coordinates?.[0])) holes.push(geometry.coordinates[0]);
            if (geometry?.type === "MultiPolygon") geometry.coordinates.forEach(polygon => { if (Array.isArray(polygon?.[0])) holes.push(polygon[0]); });
        });
        return holes.map(ring => ring.map(point => [Number(point[1]), Number(point[0])]).filter(point => Number.isFinite(point[0]) && Number.isFinite(point[1]))).filter(ring => ring.length > 3);
    }

    async function renderDistrictMap() {
        const canvas = document.getElementById("assessmentPreviewMap");
        if (!canvas || !window.L) return;
        try {
            const [mapResponse, districtResponse] = await Promise.all([
                SQ.api.get("/state/v1/map.php", { map_mode: "presence" }, { loader: false, showError: false }),
                SQ.api.get("/state/v1/assessment_progress.php", Object.assign({}, parameters(), { per_page: 1 }), { loader: false, showError: false })
            ]);
            const config = mapResponse.data?.map_config || {};
            if (!config.boundary_url) throw new Error("District boundary configuration is unavailable.");
            const boundaryResponse = await fetch(config.boundary_url, { cache: "no-store" });
            if (!boundaryResponse.ok) throw new Error("District boundary could not load.");
            const boundaryPayload = await boundaryResponse.json();
            const boundary = boundaryPayload.status === "success" ? boundaryPayload.data : boundaryPayload;
            const scores = {};
            (districtResponse.data?.distribution || []).forEach(row => { scores[districtKey(row.label)] = row; });
            if (!previewMap) {
                previewMap = L.map("assessmentPreviewMap", { zoomControl: false, attributionControl: false, scrollWheelZoom: false });
                L.tileLayer(config.tile_url || "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: Number(config.max_zoom) || 18 }).addTo(previewMap);
            }
            if (previewMapMask) previewMap.removeLayer(previewMapMask);
            const holes = boundaryHoles(boundary);
            if (holes.length) {
                previewMapMask = L.polygon([[[-90, -360], [-90, 360], [90, 360], [90, -360]], ...holes], {
                    stroke: false, fillColor: "#f8fafc", fillOpacity: 0.94, interactive: false
                }).addTo(previewMap);
            }
            if (previewMapLayer) previewMap.removeLayer(previewMapLayer);
            previewMapLayer = L.geoJSON(boundary, {
                style(feature) {
                    const name = feature?.properties?.district || feature?.properties?.DISTRICT || feature?.properties?.Dist_Name || feature?.properties?.DIST_NAME || "";
                    const row = scores[districtKey(name)];
                    return { color: "#ffffff", weight: 1, fillColor: row ? mapColor(row.completion_percentage) : "#cbd5e1", fillOpacity: row ? 0.72 : 0.25 };
                },
                onEachFeature(feature, layer) {
                    const name = feature?.properties?.district || feature?.properties?.DISTRICT || feature?.properties?.Dist_Name || feature?.properties?.DIST_NAME || "District";
                    const row = scores[districtKey(name)];
                    layer.bindTooltip(row ? `<strong>${esc(name)}</strong><br>${esc(row.completion_percentage)}% completed<br>${esc(row.completed)} of ${esc(row.total)} assessments` : `<strong>${esc(name)}</strong><br>No assessment data`, { sticky: true });
                }
            }).addTo(previewMap);
            const bounds = previewMapLayer.getBounds();
            if (bounds.isValid()) previewMap.fitBounds(bounds, { padding: [8, 8] });
            window.requestAnimationFrame(() => previewMap?.invalidateSize());
        } catch (error) {
            canvas.innerHTML = `<div class="sq-state-empty">${esc(error.message || "District assessment map could not load.")}</div>`;
        }
    }

    async function load() {
        try {
            const response = await SQ.api.get("/state/v1/assessment_progress.php", parameters(), { loader: false, showError: false });
            state.data = response.data || {};
            state.filters = state.data.filter_options || {};
            populateFilters();
            const summary = state.data.summary || {};
            if (!n(summary.registered_facilities)) {
                const facilities = await SQ.api.get("/state/v1/facility_category.php", parameters(), { loader: false, showError: false });
                summary.registered_facilities = n(facilities.data?.total_facilities);
            }
            renderKpis(summary); renderLifecycle(summary); renderDistricts(state.data.distribution || []); renderAttention(state.data.rows || []);
            await renderDistrictMap();
        } catch (error) {
            ["assessmentPreviewKpis", "assessmentPreviewLifecycle", "assessmentPreviewDistricts", "assessmentPreviewAttention", "assessmentPreviewMap"].forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = `<div class="sq-state-empty">${esc(error.message || "Unable to load assessment progress.")}</div>`; });
        }
    }

    async function init() {
        document.getElementById("assessmentPreviewRefresh")?.addEventListener("click", load);
        document.getElementById("assessmentPreviewDivision")?.addEventListener("change", load);
        document.getElementById("assessmentPreviewDistrict")?.addEventListener("change", load);
        await load();
    }
    SQ.stateAssessmentProgressOperations = { init };
})(window, document);
