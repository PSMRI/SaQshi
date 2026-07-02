/*!
 * ==========================================================
 * SaQshi Dashboard Page v1.0
 * ----------------------------------------------------------
 * Project : SaQshi Open Source
 * Module  : Dashboard
 * File    : dashboard.js
 * License : Apache-2.0
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    if (!window.SQ) {
        window.SQ = {};
    }

    const SQ = window.SQ;

    const API = {
        activeAssessment: "/assessment/v1/active_assessment.php",
        progress: "/assessment/v1/progress.php",
        score: "/assessment/v1/score.php",
        gapAnalysis: "/assessment/v1/gap_analysis.php"
    };

    const state = {
        activeAssessment: null,
        progress: null,
        score: null,
        gaps: null
    };

    function setText(id, value) {
        const el = document.getElementById(id);

        if (el) {
            el.textContent = value;
        }
    }

    function badge(status) {
        const value = String(status || "").toUpperCase();

        if (value === "COMPLETED") {
            return `<span class="sq-badge sq-status-completed">Completed</span>`;
        }

        if (value === "ACTIVE" || value === "IN_PROGRESS") {
            return `<span class="sq-badge sq-status-in-progress">In Progress</span>`;
        }

        if (value === "CANCELLED") {
            return `<span class="sq-badge sq-status-cancelled">Cancelled</span>`;
        }

        return `<span class="sq-badge sq-status-not-started">Not Started</span>`;
    }

    async function loadActiveAssessment() {
        const response = await SQ.api.get(
            API.activeAssessment,
            {},
            {
                loader: false,
                showError: false
            }
        );

        state.activeAssessment =
            response.data?.assessment ||
            response.data ||
            null;

        renderActiveAssessment();

        if (state.activeAssessment?.assessment_id) {
            await Promise.all([
                loadProgress(state.activeAssessment.assessment_id),
                loadScore(state.activeAssessment.assessment_id),
                loadGapAnalysis(state.activeAssessment.assessment_id)
            ]);
        }
    }

    async function loadProgress(assessmentId) {
        const response = await SQ.api.get(
            API.progress,
            {
                assessment_id: assessmentId
            },
            {
                loader: false,
                showError: false
            }
        );

        state.progress = response.data || null;
        renderProgress();
    }

    async function loadScore(assessmentId) {
        const response = await SQ.api.get(
            API.score,
            {
                assessment_id: assessmentId
            },
            {
                loader: false,
                showError: false
            }
        );

        state.score = response.data || null;
        renderScore();
    }

    async function loadGapAnalysis(assessmentId) {
        const response = await SQ.api.get(
            API.gapAnalysis,
            {
                assessment_id: assessmentId
            },
            {
                loader: false,
                showError: false
            }
        );

        state.gaps = response.data || null;
        renderGaps();
    }

    function renderActiveAssessment() {
        const target = document.getElementById("active-assessment-card");

        if (!target) {
            return;
        }

        const assessment = state.activeAssessment;

        if (!assessment || !assessment.assessment_id) {
            target.innerHTML = `
                <div class="sq-empty-message">
                    <div>
                        <strong>No active assessment found.</strong>
                        <br>
                        <a href="#" data-sq-route="assessment/create" class="sq-btn sq-btn-primary sq-mt-3">
                            Create Assessment
                        </a>
                    </div>
                </div>
            `;
            return;
        }

        target.innerHTML = `
            <div class="sq-assessment-card">
                <div class="sq-assessment-card-header">
                    <div>
                        <div class="sq-assessment-title">
                            ${escapeHtml(assessment.assessment_name || "Assessment")}
                        </div>
                        <div class="sq-assessment-meta">
                            Framework: ${escapeHtml(assessment.framework_code || "N/A")}
                        </div>
                    </div>
                    ${badge(assessment.status)}
                </div>

                <div class="sq-grid sq-grid-2 sq-mt-4">
                    <div>
                        <div class="sq-text-muted sq-text-sm">Start Date</div>
                        <strong>${escapeHtml(assessment.start_date || "-")}</strong>
                    </div>

                    <div>
                        <div class="sq-text-muted sq-text-sm">End Date</div>
                        <strong>${escapeHtml(assessment.end_date || "-")}</strong>
                    </div>
                </div>

                <div class="sq-assessment-footer">
                    <a href="#" data-sq-route="assessment/departments"
                       class="sq-btn sq-btn-outline-primary sq-btn-sm">
                        View Progress
                    </a>

                    <a href="#" data-sq-route="assessment/checklist"
                       class="sq-btn sq-btn-primary sq-btn-sm">
                        Continue Assessment
                    </a>
                </div>
            </div>
        `;
    }

    function renderProgress() {
        const summary = state.progress?.summary || {};

        const active = Number(summary.active_departments || 0);
        const completed = Number(summary.completed || 0);
        const pending = Math.max(active - completed, 0);
        const percent = Number(summary.department_completion_percent || 0);

        setText("active-departments", active);
        setText("completed-departments", completed);
        setText("pending-departments", pending);
        setText("overall-progress-text", percent + "%");

        const bar = document.getElementById("overall-progress-bar");

        if (bar) {
            bar.style.width = percent + "%";
        }

        setText("metric-in-progress", Number(summary.in_progress || 0));
        setText("metric-completed", completed);
    }

    function renderScore() {
        const overall =
            state.score?.overall_score ||
            state.score?.score ||
            {};

        const percentage = Number(
            overall.improved_percentage ||
            overall.percentage ||
            0
        );

        const el = document.getElementById("dashboard-score");

        if (el) {
            el.textContent = percentage + "%";
        }
    }

    function renderGaps() {
        const gaps = state.gaps || {};

        setText(
            "metric-open-gaps",
            Number(gaps.open_gaps || gaps.summary?.open_gaps || 0)
        );
    }

    function renderRecentAssessments() {
        const target = document.getElementById("recent-assessment-list");

        if (!target) {
            return;
        }

        const assessment = state.activeAssessment;

        if (!assessment || !assessment.assessment_id) {
            target.innerHTML = `
                <tr>
                    <td colspan="7" class="sq-text-center sq-text-muted">
                        No assessment found.
                    </td>
                </tr>
            `;
            return;
        }

        target.innerHTML = `
            <tr>
                <td>${escapeHtml(assessment.assessment_name || "Assessment")}</td>
                <td>${escapeHtml(assessment.framework_code || "-")}</td>
                <td>${badge(assessment.status)}</td>
                <td>${escapeHtml(assessment.start_date || "-")}</td>
                <td>${escapeHtml(assessment.end_date || "-")}</td>
                <td id="recent-score">-</td>
                <td class="sq-td-right">
                    <a href="#" data-sq-route="assessment/departments"
                       class="sq-btn sq-btn-sm sq-btn-outline-primary">
                        Open
                    </a>
                </td>
            </tr>
        `;
    }

    function renderMetrics() {
        setText("metric-total-assessments", state.activeAssessment ? 1 : 0);
        renderRecentAssessments();
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    async function init() {
        try {
            if (SQ.loader) {
                SQ.loader.show("Loading dashboard...");
            }

            if (SQ.breadcrumb) {
                SQ.breadcrumb.render([
                    {
                        label: "Dashboard"
                    }
                ]);
            }

            await loadActiveAssessment();
            renderMetrics();

        } catch (error) {
            if (SQ.notification) {
                SQ.notification.error(
                    error.message || "Unable to load dashboard"
                );
            }

            renderRecentAssessments();

        } finally {
            if (SQ.loader) {
                SQ.loader.hide();
            }
        }
    }

    SQ.dashboard = {
        init,
        state
    };

})(window, document);
