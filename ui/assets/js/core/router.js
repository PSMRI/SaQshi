/*!
 * ==========================================================
 * SQ Router Service v1.0
 * ----------------------------------------------------------
 * Project  : SaQshi Open Source
 * Module   : Frontend Navigation / Routing Service
 * File     : router.js
 * License  : Apache-2.0
 * ==========================================================
 *
 * PURPOSE
 * ----------------------------------------------------------
 * Centralized navigation manager.
 *
 * Used for:
 * - Page navigation
 * - Query string handling
 * - Active menu highlighting
 * - Breadcrumb generation
 * - Browser history handling
 * - Safe redirects
 * - Future SPA migration
 *
 * Do not use scattered window.location code everywhere.
 * Prefer:
 *
 *      SQ.router.go("/ui/dashboard.html");
 *      SQ.router.query("assessment_id");
 *      SQ.router.setActiveMenu();
 *
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    if (!window.SQ) {
        window.SQ = {};
    }

    const SQ = window.SQ;

    const CONFIG = {
        basePath: "/ui",
        defaultPage: "/ui/dashboard.html",
        loginPage: "/ui/login.html",
        notFoundPage: "/ui/404.html"
    };

    function normalize(path) {
        if (!path) {
            return CONFIG.defaultPage;
        }

        if (path.startsWith("http")) {
            return path;
        }

        if (path.startsWith("/")) {
            return path;
        }

        return CONFIG.basePath + "/" + path;
    }

    function buildUrl(path, params = {}) {
        const url = new URL(normalize(path), window.location.origin);

        Object.keys(params).forEach(function (key) {
            if (
                params[key] !== null &&
                params[key] !== undefined &&
                params[key] !== ""
            ) {
                url.searchParams.set(key, params[key]);
            }
        });

        return url.toString();
    }

    function go(path, params = {}, options = {}) {
        const url = buildUrl(path, params);

        if (options.replace) {
            window.location.replace(url);
            return;
        }

        window.location.href = url;
    }

    function replace(path, params = {}) {
        go(path, params, { replace: true });
    }

    function reload() {
        window.location.reload();
    }

    function back() {
        window.history.back();
    }

    function current() {
        return window.location.pathname;
    }

    function currentFull() {
        return window.location.href;
    }

    function query(name, fallback = null) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name) || fallback;
    }

    function queries() {
        const params = new URLSearchParams(window.location.search);
        const obj = {};

        params.forEach(function (value, key) {
            obj[key] = value;
        });

        return obj;
    }

    function setQuery(params = {}, options = {}) {
        const url = new URL(window.location.href);

        Object.keys(params).forEach(function (key) {
            if (
                params[key] === null ||
                params[key] === undefined ||
                params[key] === ""
            ) {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, params[key]);
            }
        });

        if (options.replace !== false) {
            window.history.replaceState({}, "", url.toString());
        } else {
            window.history.pushState({}, "", url.toString());
        }
    }

    function removeQuery(keys = []) {
        const url = new URL(window.location.href);

        keys.forEach(function (key) {
            url.searchParams.delete(key);
        });

        window.history.replaceState({}, "", url.toString());
    }

    function isCurrent(path) {
        return current() === normalize(path);
    }

    function setActiveMenu(selector = "[data-sq-nav]") {
        const currentPath = current();

        document.querySelectorAll(selector).forEach(function (link) {
            const href = link.getAttribute("href") || "";
            const navPath = new URL(normalize(href), window.location.origin).pathname;

            if (navPath === currentPath) {
                link.classList.add("is-active");
                link.setAttribute("aria-current", "page");
            } else {
                link.classList.remove("is-active");
                link.removeAttribute("aria-current");
            }
        });
    }

    function redirectIf(condition, path, params = {}) {
        if (condition) {
            go(path, params);
        }
    }

    function safeBack(fallback = CONFIG.defaultPage) {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            go(fallback);
        }
    }

    function breadcrumb(items = [], targetSelector = "[data-sq-breadcrumb]") {
        const target = document.querySelector(targetSelector);

        if (!target) {
            return;
        }

        target.innerHTML = "";

        items.forEach(function (item, index) {
            const li = document.createElement("li");
            li.className = "sq-breadcrumb-item";

            if (index === items.length - 1 || !item.url) {
                li.textContent = item.label;
                li.setAttribute("aria-current", "page");
            } else {
                const a = document.createElement("a");
                a.href = item.url;
                a.textContent = item.label;
                li.appendChild(a);
            }

            target.appendChild(li);
        });
    }

    function bindLinks() {
        document.querySelectorAll("[data-sq-route]").forEach(function (el) {
            el.addEventListener("click", function (event) {
                event.preventDefault();

                const route = el.getAttribute("data-sq-route");
                go(route);
            });
        });

        document.querySelectorAll("[data-sq-back]").forEach(function (el) {
            el.addEventListener("click", function (event) {
                event.preventDefault();
                safeBack();
            });
        });
    }

    function init() {
        setActiveMenu();
        bindLinks();
    }

    SQ.router = {
        config: function (settings = {}) {
            Object.assign(CONFIG, settings);
        },

        go,
        replace,
        reload,
        back,
        safeBack,

        current,
        currentFull,

        query,
        queries,
        setQuery,
        removeQuery,

        isCurrent,
        setActiveMenu,
        redirectIf,
        breadcrumb,

        buildUrl,
        normalize,
        init
    };

    document.addEventListener("DOMContentLoaded", init);

})(window, document);