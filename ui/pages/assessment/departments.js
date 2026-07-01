/*!
 * ==========================================================
 * SaQshi Open Source
 * Assessment Departments
 * departments.js
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    const API = {
        activeAssessment: "/assessment/v1/active_assessment.php",
        departments: "/framework/v1/my_departments.php",
        saveDepartmentStatus: "/assessment/v1/department-status/save.php",
        listDepartmentStatus: "/assessment/v1/department-status/list.php"
    };

    const state = {
        assessment: null,
        departments: [],
        statuses: {},
        eventsBound: false,
        initialized: false,
        isRefreshing: false
    };

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function notify(type, message) {
        if (SQ.notification && typeof SQ.notification[type] === "function") {
            SQ.notification[type](message);
            return;
        }

        if (SQ.toast) {
            SQ.toast(message, type);
        }
    }

    function hideLoader() {
        if (SQ.loader && typeof SQ.loader.hide === "function") {
            SQ.loader.hide();
        }

        document.querySelectorAll("#sq-page-loader, .sq-loader").forEach(function (el) {
            el.classList.remove("active", "success", "error");
            el.style.display = "none";
            el.style.pointerEvents = "none";
            el.setAttribute("aria-hidden", "true");
        });

        document.body.style.overflow = "";
    }

    async function apiGet(url, params = {}) {
        return SQ.api.get(url, params, { loader: false });
    }

    async function apiPost(url, payload = {}) {
        return SQ.api.post(url, payload, { loader: false });
    }

    function getAssessmentId() {
        return state.assessment?.assessment_id || "";
    }

    function getFacilityId() {
        return state.assessment?.fac_id || state.assessment?.fac_id_fk || "";
    }

    function getAssessmentPeriod() {
        return (
            state.assessment?.ass_period ||
            state.assessment?.assessment_period ||
            state.assessment?.assessment_id ||
            ""
        );
    }

    function getDeptId(dept) {
        return (
            dept.dept_id ||
            dept.fac_dept_id ||
            dept.fac_dept_id_fk ||
            dept.department_id ||
            dept.id ||
            ""
        );
    }

    function getDeptName(dept) {
        return (
            dept.department_name ||
            dept.fac_dept_name ||
            dept.dept_name ||
            dept.name ||
            "Department"
        );
    }

    function getDeptCode(dept) {
        return dept.department_code || dept.dept_code || dept.code || "DEPT-" + getDeptId(dept);
    }

    function normalizeDepartments(response) {
        const data = response?.data || response || {};
        let list = data.departments || data.department_list || response?.departments || [];

        if (!Array.isArray(list) && Array.isArray(data)) {
            list = data;
        }

        return Array.isArray(list) ? list : [];
    }

    function normalizeStatuses(response) {
        const data = response?.data || response || {};
        let list = data.statuses || data.departments || data.department_status || response?.statuses || [];

        if (!Array.isArray(list) && Array.isArray(data)) {
            list = data;
        }

        const map = {};

        if (Array.isArray(list)) {
            list.forEach(function (item) {
                const id =
                    item.dept_id ||
                    item.fac_dept_id ||
                    item.fac_dept_id_fk ||
                    item.department_id ||
                    item.id;

                if (id) {
                    map[String(id)] = item;
                }
            });
        }

        return map;
    }

    function isActive(dept) {
        const status = state.statuses[String(getDeptId(dept))];

        if (!status) {
            return false;
        }

        return (
            status.is_active === true ||
            status.is_active === 1 ||
            status.is_active === "1" ||
            String(status.status || "").toUpperCase() === "ACTIVE"
        );
    }

    function progressPercent(dept) {
        const status = state.statuses[String(getDeptId(dept))] || {};
        const total = Number(status.total_checkpoints || status.total || 0);
        const completed = Number(status.completed_checkpoints || status.completed || 0);

        return total ? Math.round((completed / total) * 100) : 0;
    }

    function renderSummary() {
        const target = $("assessmentSummary");
        if (!target) return;

        const a = state.assessment || {};

        if (!a.assessment_id) {
            target.innerHTML = `<div class="sq-empty-message">No active assessment found. Please create assessment first.</div>`;
            return;
        }

        target.innerHTML = `
            <div class="sq-summary-card">
                <div class="sq-summary-label">Assessment</div>
                <div class="sq-summary-value">${escapeHtml(a.assessment_name || "-")}</div>
            </div>
            <div class="sq-summary-card">
                <div class="sq-summary-label">Framework</div>
                <div class="sq-summary-value">${escapeHtml(a.framework_code || "-")}</div>
            </div>
            <div class="sq-summary-card">
                <div class="sq-summary-label">Status</div>
                <div class="sq-summary-value">${escapeHtml(a.status || "ACTIVE")}</div>
            </div>
            <div class="sq-summary-card">
                <div class="sq-summary-label">Assessment ID</div>
                <div class="sq-summary-value">${escapeHtml(a.assessment_id || "-")}</div>
            </div>
        `;
    }

    function renderDepartments() {
        const target = $("departmentList");
        if (!target) return;

        if (!state.departments.length) {
            target.innerHTML = `<div class="sq-empty-message">No departments found for this facility/framework.</div>`;
            return;
        }

        target.innerHTML = state.departments.map(function (dept) {
            const id = getDeptId(dept);
            const active = isActive(dept);
            const progress = progressPercent(dept);

            return `
                <article class="sq-department-card" data-department-id="${escapeHtml(id)}">
                    <div class="sq-department-header">
                        <div>
                            <div class="sq-department-name">${escapeHtml(getDeptName(dept))}</div>
                            <div class="sq-department-code">${escapeHtml(getDeptCode(dept))}</div>
                        </div>

                        <span class="sq-status-chip ${active ? "sq-status-active" : "sq-status-inactive"}">
                            ${active ? "Active" : "Inactive"}
                        </span>
                    </div>

                    <div class="sq-department-description">
                        ${escapeHtml(dept.description || "Activate this department to begin checklist assessment.")}
                    </div>

                    <div class="sq-department-progress">
                        <div class="sq-progress-text">
                            <span>Progress</span>
                            <span>${progress}%</span>
                        </div>
                        <div class="sq-progress">
                            <div class="sq-progress-bar" style="width:${progress}%"></div>
                        </div>
                    </div>

                    <div class="sq-department-footer">
                        <label class="sq-toggle">
                            <input
                                type="checkbox"
                                data-department-toggle="${escapeHtml(id)}"
                                ${active ? "checked" : ""}>
                            <span class="sq-toggle-label">${active ? "Activated" : "Activate"}</span>
                        </label>

                        <button
                            type="button"
                            class="sq-btn sq-btn-primary"
                            data-start-checklist="${escapeHtml(id)}"
                            ${active ? "" : "disabled"}>
                            <i class="bi bi-list-check"></i>
                            Start Checklist
                        </button>
                    </div>
                </article>
            `;
        }).join("");
    }

    async function loadActiveAssessment() {
        const response = await apiGet(API.activeAssessment);

        state.assessment =
            response?.data?.assessment ||
            response?.assessment ||
            response?.data ||
            null;
    }

    async function loadDepartments() {
        const response = await apiGet(API.departments, {
            framework_code: state.assessment?.framework_code || "saqshi-nqas"
        });

        state.departments = normalizeDepartments(response);
    }

    async function loadDepartmentStatuses() {
        const response = await apiGet(API.listDepartmentStatus, {
            fac_id: getFacilityId(),
            ass_period: getAssessmentPeriod()
        });

        state.statuses = normalizeStatuses(response);
    }

    async function toggleDepartment(departmentId, active) {
        const id = String(departmentId);

        state.statuses[id] = {
            ...(state.statuses[id] || {}),
            dept_id: departmentId,
            is_active: active ? 1 : 0,
            status: active ? "ACTIVE" : "INACTIVE"
        };

        renderDepartments();

        try {
            const response = await apiPost(API.saveDepartmentStatus, {
                ass_period: getAssessmentPeriod(),
                dept_id: departmentId,
                is_active: active ? 1 : 0
            });

            if (response?.status === "error") {
                throw new Error(response.message || "Unable to update department.");
            }

            notify("success", active ? "Department activated." : "Department deactivated.");

            await loadDepartmentStatuses();
            renderDepartments();

        } catch (error) {
            console.error(error);
            notify("error", error.message || "Unable to update department status.");
            await refresh();
        }
    }

    function startChecklist(departmentId) {
        sessionStorage.setItem("sq_active_department_id", departmentId);

        if (SQ.router && typeof SQ.router.navigate === "function") {
            SQ.router.navigate("assessment/checklist", {
                department_id: departmentId,
                assessment_id: getAssessmentId()
            });
        }
    }

    function bindEvents() {
        if (state.eventsBound) return;

        state.eventsBound = true;

        document.addEventListener("click", function (event) {
            const refreshBtn = event.target.closest("#btnRefreshDepartments");

            if (refreshBtn) {
                event.preventDefault();
                refresh();
                return;
            }

            const backBtn = event.target.closest("#btnBackToCreateAssessment");

            if (backBtn) {
                event.preventDefault();
                SQ.router.navigate("assessment/create");
                return;
            }

            const startBtn = event.target.closest("[data-start-checklist]");

            if (startBtn && !startBtn.disabled) {
                event.preventDefault();
                startChecklist(startBtn.getAttribute("data-start-checklist"));
            }
        });

        document.addEventListener("change", function (event) {
            const toggle = event.target.closest("[data-department-toggle]");

            if (!toggle) return;

            toggleDepartment(
                toggle.getAttribute("data-department-toggle"),
                toggle.checked
            );
        });
    }

    async function refresh() {
        if (state.isRefreshing) return;

        state.isRefreshing = true;

        try {
            hideLoader();

            await loadActiveAssessment();
            renderSummary();

            if (!state.assessment?.assessment_id) {
                state.departments = [];
                state.statuses = {};
                renderDepartments();
                return;
            }

            await loadDepartments();
            await loadDepartmentStatuses();

            renderDepartments();

        } catch (error) {
            console.error(error);
            notify("error", error.message || "Unable to load departments.");
        } finally {
            state.isRefreshing = false;
            hideLoader();
        }
    }

    async function init() {
        bindEvents();
        await refresh();
    }

    SQ.assessmentDepartments = {
        init,
        refresh,
        state
    };

    document.addEventListener("sq:page-loaded", function (event) {
        if (event.detail && event.detail.route === "assessment/departments") {
            init();
        }
    });

    if (document.readyState !== "loading") {
        if ($("departmentList")) {
            init();
        }
    }

})(window, document);