/*!
 * ==========================================================
 * SaQshi Open Source
 * Performance Trend
 * trend.js
 * Version 1.0.0 | Updated 2026-07-06
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = {
        facility: {},
        summary: {},
        monthStatus: [],
        trends: { KPI: [], OUTCOME: [], EFFECTIVE: [] },
        effectiveLabel: "Performance",
        effectiveType: "KPI",
        selectedType: "KPI",
        departmentId: "",
        departments: []
    };

    function currentPeriod() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    }

    function $(id) {
        return document.getElementById(id);
    }

    function setText(id, value) {
        const el = $(id);
        if (el) el.textContent = value;
    }

    function esc(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function num(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function fmt(value) {
        const parsed = num(value);
        return Number.isInteger(parsed) ? String(parsed) : parsed.toFixed(2);
    }

    function shortMonth(period) {
        const parts = String(period || "").split("-");
        if (parts.length !== 2) return period || "-";
        const month = Number(parts[1]);
        const names = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return `${names[month - 1] || parts[1]} ${String(parts[0]).slice(-2)}`;
    }

    function indicatorEntryUrl(type, departmentId, period) {
        const params = new URLSearchParams({
            indicator_type: type || "OUTCOME",
            department_id: String(departmentId || ""),
            period: period || ""
        });
        return `/ui/pages/performance/indicator.html?${params.toString()}`;
    }

    function isAssessorUser() {
        const user = SQ.auth && typeof SQ.auth.getUser === "function" ? SQ.auth.getUser() : null;
        const roleName = String(user?.role_name || user?.user_type || "").toLowerCase();
        return Number(user?.role_id || 0) === 10 || roleName.includes("assessor");
    }

    function isReadonly() {
        const params = new URLSearchParams(window.location.search);
        return params.get("readonly") === "1" || params.get("mode") === "view" || isAssessorUser();
    }

    function handleEditLink(event) {
        const link = event.target.closest('a[href*="/ui/pages/performance/indicator.html"]');
        if (!link || !SQ.router || typeof SQ.router.navigate !== "function") {
            return;
        }

        event.preventDefault();

        if (isReadonly()) {
            return;
        }

        const url = new URL(link.href, window.location.origin);
        const params = {};
        url.searchParams.forEach((value, key) => {
            params[key] = value;
        });
        SQ.router.navigate("performance/indicator", params);
    }

    function renderMonthStatus() {
        const target = $("trendMonthRows");
        if (!target) return;

        target.innerHTML = state.monthStatus.length
            ? state.monthStatus.slice(-12).map(row => `
                <div class="sq-trend-month-card">
                    <strong>${esc(shortMonth(row.period))}</strong>
                    <span>${esc(state.selectedType)} <b>${esc(row.total_entries || 0)}</b></span>
                    ${isReadonly() ? `<span>View only</span>` : `<a href="${esc(indicatorEntryUrl(state.selectedType, state.selectedType === "OUTCOME" ? state.departmentId : "", row.period))}">Edit month</a>`}
                </div>
            `).join("")
            : `<div class="sq-trend-empty">No month-wise performance entries available.</div>`;
    }

    function chartSvg(points, style) {
        const width = 300;
        const height = 90;
        const padX = 16;
        const padY = 12;
        const values = points.map(point => num(point.result));
        const max = Math.max(...values, 0);
        const min = Math.min(...values, 0);
        const range = Math.max(1, max - min);

        if (!points.length) {
            return `<div class="sq-trend-empty">No trend data.</div>`;
        }

        const coords = points.map((point, index) => {
            const x = points.length === 1
                ? width / 2
                : padX + (index * (width - (padX * 2)) / (points.length - 1));
            const y = height - padY - ((num(point.result) - min) * (height - (padY * 2)) / range);
            return { x, y, point };
        });

        const path = coords.map(coord => `${coord.x.toFixed(1)},${coord.y.toFixed(1)}`).join(" ");
        const areaPath = `${padX},${height - padY} ${path} ${width - padX},${height - padY}`;
        const bars = coords.map((coord, index) => {
            const barWidth = Math.max(8, Math.min(28, (width - (padX * 2)) / Math.max(1, coords.length) * .55));
            const x = coord.x - (barWidth / 2);
            const y = coord.y;
            const barHeight = Math.max(2, height - padY - y);
            return `
                <rect x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${barWidth.toFixed(1)}" height="${barHeight.toFixed(1)}" rx="3">
                    <title>${esc(shortMonth(coords[index].point.period))}: ${esc(fmt(coords[index].point.result))}</title>
                </rect>
            `;
        }).join("");
        const circles = coords.map(coord => `
            <circle cx="${coord.x.toFixed(1)}" cy="${coord.y.toFixed(1)}" r="3">
                <title>${esc(shortMonth(coord.point.period))}: ${esc(fmt(coord.point.result))}</title>
            </circle>
        `).join("");

        return `
            <svg class="sq-trend-sparkline is-${esc(style)}" viewBox="0 0 ${width} ${height}" role="img" aria-label="Month-wise result trend">
                <line x1="${padX}" y1="${height - padY}" x2="${width - padX}" y2="${height - padY}"></line>
                ${style === "area" ? `<polygon points="${areaPath}"></polygon>` : ""}
                ${style === "bar" ? bars : `<polyline points="${path}"></polyline>${circles}`}
            </svg>
        `;
    }

    function renderCharts(targetId, series) {
        const target = $(targetId);
        if (!target) return;
        const style = $("trendChartStyle")?.value || "line";
        const summaryId = targetId === "trendOutcomeCharts" ? "trendOutcomeSummary" : "trendKpiSummary";
        let summary = $(summaryId);
        if (!summary) {
            summary = document.createElement("div");
            summary.id = summaryId;
            summary.className = "sq-sr-only";
            target.parentNode.insertBefore(summary, target.nextSibling);
            target.setAttribute("aria-describedby", summaryId);
        }

        target.innerHTML = series.length
            ? series.map(item => {
                const points = item.points || [];
                const latest = points[points.length - 1] || {};
                const editUrl = indicatorEntryUrl(item.indicator_type, item.department_id, latest.period);
                const readonly = isReadonly();
                return `
                    <div class="sq-trend-chart-card">
                        <div class="sq-trend-chart-head">
                            <strong>${esc(item.indicator_name || "Indicator")}</strong>
                            <span>${esc(item.indicator_code || "-")} | ${esc(item.department_name || "-")}</span>
                        </div>
                        ${chartSvg(points, style)}
                        <div class="sq-trend-points">
                            ${points.map(point => `
                                ${readonly ? `<span>${esc(shortMonth(point.period))}: ${esc(fmt(point.result))}</span>` : `<a href="${esc(indicatorEntryUrl(item.indicator_type, item.department_id, point.period))}">
                                    ${esc(shortMonth(point.period))}: ${esc(fmt(point.result))}
                                </a>`}
                            `).join("")}
                        </div>
                        <div class="sq-trend-chart-foot">
                            <span>${esc(points.length)} month${points.length === 1 ? "" : "s"}</span>
                            ${readonly ? `<span>View only</span>` : `<a href="${esc(editUrl)}">Edit latest</a>`}
                        </div>
                    </div>
                `;
            }).join("")
            : `<div class="sq-trend-empty">No trend data available.</div>`;

        summary.textContent = series.length
            ? series.map(item => {
                const points = item.points || [];
                const latest = points[points.length - 1] || {};
                return `${item.indicator_name || "Indicator"} has ${points.length} month${points.length === 1 ? "" : "s"} of data. Latest ${shortMonth(latest.period)} result is ${fmt(latest.result)}.`;
            }).join(" ")
            : "No trend data available.";

        if (SQ.a11y) {
            SQ.a11y.enhance(target);
        }
    }

    function filterByDepartment(series) {
        if (!state.departmentId) {
            return series;
        }

        return series.filter(item => String(item.department_id || 0) === state.departmentId);
    }

    function renderDepartmentFilter() {
        const select = $("trendDepartmentFilter");
        if (!select) return;

        const departments = new Map(state.departments.filter(item => Number(item.department_id || 0) > 0).map(item => [
            String(item.department_id || 0),
            item.department_name || (Number(item.department_id || 0) === 0 ? "Facility-level KPI" : `Department ${item.department_id}`)
        ]));

        const current = state.departmentId;
        select.innerHTML = `<option value="">All departments</option>` + [...departments.entries()]
            .sort((a, b) => a[1].localeCompare(b[1]))
            .map(([id, name]) => `<option value="${esc(id)}">${esc(name)}</option>`)
            .join("");
        state.departmentId = departments.has(current) ? current : "";
        select.value = state.departmentId;
    }

    function renderAll() {
        const readonly = isReadonly();
        const outcomeIsEffective = state.effectiveType === "OUTCOME";
        const outcomeSeries = outcomeIsEffective
            ? (state.trends.EFFECTIVE || state.trends.OUTCOME || [])
            : (state.trends.OUTCOME || []);
        setText("trendFacility", state.facility?.fac_name || "-");
        setText("trendTotalMonths", state.summary?.total_months || 0);
        setText("trendTotalEntries", state.summary?.total_entries || 0);
        setText("trendLatestPeriod", state.summary?.latest_period ? shortMonth(state.summary.latest_period) : "-");
        if ($("trendDepartmentContainer")) $("trendDepartmentContainer").hidden = state.selectedType !== "OUTCOME";
        document.querySelectorAll(".sq-trend-outcome-summary").forEach(el => { el.hidden = state.selectedType !== "OUTCOME"; });
        setText("trendMonthStatusLabel", `${state.selectedType === "KPI" ? "KPI" : "Outcome"} entries by month`);
        if ($("trendOutcomeSection")) $("trendOutcomeSection").hidden = state.selectedType !== "OUTCOME";
        if ($("trendKpiSection")) $("trendKpiSection").hidden = state.selectedType !== "KPI";
        if ($("btnDownloadOutcomeTrend")) $("btnDownloadOutcomeTrend").hidden = readonly || state.selectedType !== "OUTCOME";
        if ($("btnDownloadKpiTrend")) $("btnDownloadKpiTrend").hidden = readonly || state.selectedType !== "KPI";
        renderMonthStatus();
        const departmentName = state.departmentId ? $("trendDepartmentFilter")?.selectedOptions[0]?.textContent : "";
        setText("trendEffectiveTitle", `${outcomeIsEffective ? (state.effectiveLabel || "Outcome as KPI") : "Outcome"} Trends${departmentName ? ` — ${departmentName}` : ""}`);
        renderCharts("trendOutcomeCharts", filterByDepartment(outcomeSeries));
        renderCharts("trendKpiCharts", state.trends.KPI || []);
    }

    function excelText(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    async function downloadOutcomePdf() {
        const button = $("btnOutcomePdf");
        const series = filterByDepartment(state.effectiveType === "OUTCOME" ? (state.trends.EFFECTIVE || state.trends.OUTCOME || []) : (state.trends.OUTCOME || []));
        if (!series.length) {
            if (SQ.toast) SQ.toast("No outcome trend data to download.", "warning");
            return;
        }
        button.disabled = true;
        button.setAttribute("aria-busy", "true");
        try {
            await new Promise(resolve => requestAnimationFrame(() => setTimeout(resolve, 0)));
            const pages = [];
            const title = $("trendEffectiveTitle")?.textContent || "Outcome Trends";
            const style = $("trendChartStyle")?.value || "line";
            for (let start = 0; start < series.length; start += 4) {
                const canvas = document.createElement("canvas");
                canvas.width = 1240; canvas.height = 1754;
                const ctx = canvas.getContext("2d");
                ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = "#0f172a"; ctx.font = "bold 36px Arial"; ctx.fillText(title, 60, 70, 1120);
                ctx.font = "22px Arial"; ctx.fillText(state.facility?.fac_name || "", 60, 110, 1120);
                series.slice(start, start + 4).forEach((item, index) => {
                    const top = 145 + index * 390;
                    ctx.strokeStyle = "#cbd5e1"; ctx.strokeRect(60, top, 1120, 365);
                    ctx.fillStyle = "#0f172a"; ctx.font = "bold 25px Arial";
                    ctx.fillText(item.indicator_name || "Indicator", 80, top + 38, 1080);
                    ctx.fillStyle = "#64748b"; ctx.font = "20px Arial";
                    ctx.fillText(`${item.indicator_code || ""} | ${item.department_name || ""}`, 80, top + 68, 1080);
                    const points = item.points || [];
                    if (!points.length) { ctx.fillText("No trend data", 80, top + 130); return; }
                    const values = points.map(point => num(point.result));
                    const min = Math.min(0, ...values), max = Math.max(0, ...values), range = Math.max(1, max - min);
                    const left = 105, right = 1135, bottom = top + 285, height = 160;
                    const coords = points.map((point, i) => ({ x: points.length === 1 ? (left + right) / 2 : left + i * (right - left) / (points.length - 1), y: bottom - (num(point.result) - min) * height / range, point }));
                    ctx.strokeStyle = "#cbd5e1"; ctx.beginPath(); ctx.moveTo(left, bottom); ctx.lineTo(right, bottom); ctx.stroke();
                    ctx.strokeStyle = "#2563eb"; ctx.fillStyle = "#2563eb"; ctx.lineWidth = 4;
                    if (style === "bar") {
                        const width = Math.min(40, (right - left) / points.length * .55);
                        coords.forEach(p => ctx.fillRect(p.x - width / 2, p.y, width, Math.max(2, bottom - p.y)));
                    } else {
                        if (style === "area") {
                            ctx.beginPath(); ctx.moveTo(coords[0].x, bottom); coords.forEach(p => ctx.lineTo(p.x, p.y)); ctx.lineTo(coords[coords.length - 1].x, bottom); ctx.closePath();
                            ctx.fillStyle = "rgba(37,99,235,.18)"; ctx.fill(); ctx.fillStyle = "#2563eb";
                        }
                        ctx.beginPath(); coords.forEach((p, i) => i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y)); ctx.stroke();
                        coords.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, 5, 0, Math.PI * 2); ctx.fill(); });
                    }
                    ctx.fillStyle = "#475569"; ctx.font = "18px Arial"; ctx.textAlign = "center";
                    coords.forEach(p => { ctx.fillText(shortMonth(p.point.period), p.x, bottom + 25); ctx.fillText(fmt(p.point.result), p.x, Math.max(top + 95, p.y - 10)); });
                    ctx.textAlign = "left"; ctx.lineWidth = 1;
                });
                const data = canvas.toDataURL("image/jpeg", .9).split(",")[1];
                const binary = atob(data);
                pages.push(Uint8Array.from(binary, char => char.charCodeAt(0)));
            }
            const encoder = new TextEncoder(), chunks = [], offsets = [0]; let length = 0;
            const add = value => { const bytes = typeof value === "string" ? encoder.encode(value) : value; chunks.push(bytes); length += bytes.length; };
            const object = (id, content) => { offsets[id] = length; add(`${id} 0 obj\n`); content(); add("\nendobj\n"); };
            add("%PDF-1.4\n");
            object(1, () => add("<< /Type /Catalog /Pages 2 0 R >>"));
            object(2, () => add(`<< /Type /Pages /Count ${pages.length} /Kids [${pages.map((_, i) => `${3 + i * 3} 0 R`).join(" ")}] >>`));
            pages.forEach((jpeg, i) => {
                const page = 3 + i * 3, imageId = page + 1, contentId = page + 2;
                object(page, () => add(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /XObject << /Im0 ${imageId} 0 R >> >> /Contents ${contentId} 0 R >>`));
                object(imageId, () => { add(`<< /Type /XObject /Subtype /Image /Width 1240 /Height 1754 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpeg.length} >>\nstream\n`); add(jpeg); add("\nendstream"); });
                const stream = "q\n595 0 0 842 0 0 cm\n/Im0 Do\nQ\n";
                object(contentId, () => add(`<< /Length ${encoder.encode(stream).length} >>\nstream\n${stream}endstream`));
            });
            const xref = length;
            add(`xref\n0 ${offsets.length}\n0000000000 65535 f \n`);
            for (let i = 1; i < offsets.length; i++) add(`${String(offsets[i]).padStart(10, "0")} 00000 n \n`);
            add(`trailer\n<< /Size ${offsets.length} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`);
            const url = URL.createObjectURL(new Blob(chunks, { type: "application/pdf" }));
            const link = document.createElement("a"); link.href = url; link.download = "outcome_trends.pdf"; document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
        } catch (error) {
            console.error(error); if (SQ.toast) SQ.toast("Unable to download outcome trends PDF.", "error");
        } finally { button.disabled = false; button.removeAttribute("aria-busy"); }
    }

    async function downloadExcel(type) {
        if (type === "OUTCOME" && !state.departmentId) {
            await SQ.api.download("/performance/v1/trend_excel.php", {}, "outcome_trends_by_department.xlsx");
            return;
        }
        const requestedType = type === "EFFECTIVE" ? (state.effectiveType || "OUTCOME") : type;
        const rawSeries = type === "EFFECTIVE" ? (state.trends.EFFECTIVE || []) : (state.trends[type] || []);
        const series = requestedType === "OUTCOME" ? filterByDepartment(rawSeries) : rawSeries;
        if (!series.length) {
            if (SQ.toast) SQ.toast(`No ${type} trend data to download.`, "warning");
            return;
        }

        const periods = [...new Set(series.flatMap(item => (item.points || []).map(point => point.period)))].sort();
        const colCount = 4 + Math.max(1, periods.length) * 3;
        const reportTime = new Date().toLocaleString();
        const title = `${type === "EFFECTIVE" ? state.effectiveLabel : requestedType} Performance Trend`;
        const department = requestedType === "OUTCOME" ? (state.departmentId ? $("trendDepartmentFilter")?.selectedOptions[0]?.textContent : "All departments") : "Facility-wide";
        const dataRows = series.map(item => {
            const byPeriod = new Map((item.points || []).map(point => [point.period, point]));
            return `
                <tr>
                    <td class="indicator">${excelText(item.indicator_name || "")}</td>
                    <td>${excelText(item.formula || "")}</td>
                    <td>${excelText(item.numerator_label || "")}</td>
                    <td>${excelText(item.denominator_label || "")}</td>
                    ${periods.map(period => {
                        const point = byPeriod.get(period);
                        return ["numerator", "denominator", "result"].map(field => `<td class="num">${point && point[field] != null ? excelText(fmt(point[field])) : ""}</td>`).join("");
                    }).join("")}
                </tr>
            `;
        }).join("");

        const html = `
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <style>
                    table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt}
                    td,th{border:1px solid #000;padding:4px 6px;vertical-align:middle}
                    .label{background:#b8c9e6;font-weight:bold}
                    .value{background:#f4e3d1}
                    .report{background:#d6d900;font-weight:bold;text-align:center;font-size:12pt}
                    .head{background:#156799;color:#000;font-weight:bold;text-align:center}
                    .month{background:#86a86f;font-weight:bold;text-align:center}
                    .indicator{min-width:360px;font-weight:bold}
                    .num{text-align:right}
                </style>
            </head>
            <body>
                <table>
                    <tr>
                        <td class="label">Facility Name</td>
                        <td class="value">${excelText(state.facility?.fac_name || "-")}</td>
                        <td class="label">Report Time</td>
                        <td class="value">${excelText(reportTime)}</td>
                        <td class="label">Department</td>
                        <td class="value" colspan="${colCount - 5}">${excelText(department)}</td>
                    </tr>
                    <tr>
                        <td class="label">Facility NIN No</td>
                        <td class="value" colspan="${colCount - 1}">${excelText(state.facility?.nin_no || state.facility?.NIN_no || state.facility?.fac_nin || "-")}</td>
                    </tr>
                    <tr>
                        <td class="label">Report Type</td>
                        <td class="report" colspan="${colCount - 1}">${excelText(title)}</td>
                    </tr>
                    <tr>
                        <td class="head" rowspan="2">Indicator's Name</td>
                        <td class="head" rowspan="2">Formula</td>
                        <td class="head" rowspan="2">Numerator label</td>
                        <td class="head" rowspan="2">Denominator label</td>
                        ${periods.map(period => `<td class="head" colspan="3">${excelText(shortMonth(period))}</td>`).join("")}
                    </tr>
                    <tr>
                        ${periods.map(() => `<td class="month">Numerator</td><td class="month">Denominator</td><td class="month">Value</td>`).join("")}
                    </tr>
                    ${dataRows}
                </table>
            </body>
            </html>
        `;

        const blob = new Blob(["\ufeff", html], { type: "application/vnd.ms-excel;charset=utf-8" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `${String(type === "EFFECTIVE" ? state.effectiveLabel : requestedType).toLowerCase().replace(/[^a-z0-9]+/g, "_")}_performance_trend_${new Date().toISOString().slice(0, 10)}.xls`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    async function downloadWithProgress(type, button) {
        if (!button || button.disabled) return;
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.setAttribute("aria-busy", "true");
        button.textContent = "Please wait, downloading…";
        try {
            await new Promise(resolve => requestAnimationFrame(() => setTimeout(resolve, 0)));
            await downloadExcel(type);
        } catch (error) {
            console.error(error);
            if (SQ.toast) SQ.toast(error.message || "Unable to download report.", "error");
        } finally {
            button.disabled = false;
            button.removeAttribute("aria-busy");
            button.innerHTML = originalContent;
        }
    }

    async function loadTrend() {
        const response = await SQ.api.get("/performance/v1/trend.php", {
            all_indicators: $("trendAllIndicators")?.checked ? 1 : 0,
            trend_type: state.selectedType,
            department_id: state.selectedType === "OUTCOME" ? state.departmentId : "",
            period: $("trendPeriodFilter")?.value || currentPeriod()
        }, { loader: false, showError: false });

        state.facility = response?.data?.facility || {};
        state.summary = response?.data?.summary || {};
        state.monthStatus = response?.data?.month_status || [];
        state.trends = response?.data?.indicator_trends || { KPI: [], OUTCOME: [], EFFECTIVE: [] };
        state.departments = response?.data?.trend_departments || [];
        state.effectiveType = response?.data?.effective_indicator_type || "KPI";
        state.effectiveLabel = response?.data?.effective_indicator_label || state.effectiveType || "Performance";
        const outcomeDepartmentStatus = response?.data?.outcome_department_status || {};
        setText("trendOutcomeDepartments", outcomeDepartmentStatus.total_departments || 0);
        setText("trendCompletedOutcomeDepartments", outcomeDepartmentStatus.completed_departments || 0);
        setText("trendSelectedPeriod", shortMonth(outcomeDepartmentStatus.period || currentPeriod()));
        renderDepartmentFilter();
        renderAll();
    }

    function init() {
        const periodFilter = $("trendPeriodFilter");
        if (periodFilter && !periodFilter.value) periodFilter.value = currentPeriod();
        document.removeEventListener("click", handleEditLink);
        document.addEventListener("click", handleEditLink);
        $("btnTrendRefresh")?.addEventListener("click", loadTrend);
        $("trendAllIndicators")?.addEventListener("change", loadTrend);
        $("trendTypeFilter")?.addEventListener("change", event => {
            state.selectedType = event.target.value;
            renderAll();
            loadTrend().catch(console.error);
        });
        periodFilter?.addEventListener("change", loadTrend);
        $("trendDepartmentFilter")?.addEventListener("change", event => {
            state.departmentId = event.target.value;
            loadTrend().catch(console.error);
        });
        $("trendChartStyle")?.addEventListener("change", renderAll);
        $("btnOutcomePdf")?.addEventListener("click", downloadOutcomePdf);
        $("btnDownloadOutcomeTrend")?.addEventListener("click", event => downloadWithProgress("OUTCOME", event.currentTarget));
        $("btnDownloadKpiTrend")?.addEventListener("click", event => downloadWithProgress("KPI", event.currentTarget));
        loadTrend().catch(console.error);
    }

    SQ.performanceTrend = { init };
})(window, document);
