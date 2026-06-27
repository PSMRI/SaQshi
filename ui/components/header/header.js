/*!
 * ==========================================================
 * SQ Header Component JS v1.0
 * ----------------------------------------------------------
 * Project   : SaQshi Open Source
 * Component : Header
 * File      : header.js
 * License   : Apache-2.0
 * ==========================================================
 *
 * Responsibilities:
 * - Sidebar toggle
 * - Theme toggle
 * - User dropdown
 * - Logout binding
 * - User details rendering
 * - AI assistant button
 * - Notification count placeholder
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    if (!window.SQ) {
        window.SQ = {};
    }

    const SQ = window.SQ;

    function renderUser() {
        if (!SQ.auth || !SQ.auth.getUser) {
            return;
        }

        const user = SQ.auth.getUser();

        if (!user) {
            return;
        }

        document.querySelectorAll("[data-sq-user-name]").forEach(function (el) {
            el.textContent = user.full_name || user.u_name || "User";
        });

        document.querySelectorAll("[data-sq-user-role]").forEach(function (el) {
            el.textContent = user.role_name || user.role_id || "Role";
        });
    }

   function bindSidebarToggle() {
    const btn = document.getElementById("sq-sidebar-toggle");

    if (!btn) {
        return;
    }

    btn.onclick = function () {
        document.body.classList.toggle("sq-sidebar-collapsed");
    };
}

    function bindThemeToggle() {
        document.querySelectorAll("[data-sq-theme-toggle]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const current =
                    document.documentElement.getAttribute("data-theme") || "light";

                const next = current === "dark" ? "light" : "dark";

                document.documentElement.setAttribute("data-theme", next);

                if (SQ.storage) {
                    SQ.storage.set("theme", next);
                }

                if (SQ.toast) {
                    SQ.toast("Theme changed to " + next, "info");
                }
            });
        });
    }

    function bindDropdown() {
        document.querySelectorAll("[data-sq-dropdown]").forEach(function (trigger) {
            trigger.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();

                const target = trigger.getAttribute("data-sq-dropdown");
                const menu = document.querySelector(target);

                if (!menu) {
                    return;
                }

                document.querySelectorAll(".sq-dropdown-menu.is-open").forEach(function (item) {
                    if (item !== menu) {
                        item.classList.remove("is-open");
                    }
                });

                menu.classList.toggle("is-open");
            });
        });

        document.addEventListener("click", function (event) {
            if (!event.target.closest(".sq-user-menu")) {
                document.querySelectorAll(".sq-dropdown-menu.is-open").forEach(function (menu) {
                    menu.classList.remove("is-open");
                });
            }
        });
    }

    function bindLogout() {
        document.querySelectorAll("[data-sq-logout]").forEach(function (btn) {
            btn.addEventListener("click", function (event) {
                event.preventDefault();

                const confirmed = window.confirm("Are you sure you want to logout?");

                if (!confirmed) {
                    return;
                }

                if (SQ.auth && SQ.auth.logout) {
                    SQ.auth.logout();
                } else {
                    window.location.href = "/ui/login.html";
                }
            });
        });
    }

    function bindAiButton() {
        const btn = document.querySelector("#sq-ai-button");

        if (!btn) {
            return;
        }

        btn.addEventListener("click", function () {
            if (SQ.chat && SQ.chat.toggle) {
                SQ.chat.toggle();
            }
        });
    }

    function bindGlobalSearch() {
        const searchForm = document.querySelector(".sq-header-search");

        if (!searchForm) {
            return;
        }

        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const input = searchForm.querySelector("input[type='search']");
            const keyword = input ? input.value.trim() : "";

            if (!keyword) {
                return;
            }

            if (SQ.router) {
                SQ.router.go("/ui/search.html", {
                    q: keyword
                });
            }
        });
    }

    function initNotificationCount() {
        const badge = document.querySelector("#sq-notification-count");

        if (!badge) {
            return;
        }

        badge.textContent = "0";
    }

    function init() {
        renderUser();
        bindSidebarToggle();
        bindThemeToggle();
        bindDropdown();
        bindLogout();
        bindAiButton();
        bindGlobalSearch();
        initNotificationCount();
    }

    window.SQ.header = {
        init: init,
        renderUser: renderUser
    };

    document.addEventListener("DOMContentLoaded", init);
    document.addEventListener("sq:component-loaded", function (event) {
        if (event.detail && event.detail.name === "header") {
            init();
        }
    });
document.addEventListener("click", function (event) {
    const btn = event.target.closest("[data-sq-sidebar-toggle]");

    if (!btn) {
        return;
    }

    document.body.classList.toggle("sq-sidebar-collapsed");
});
})(window, document);