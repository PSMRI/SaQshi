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

    window.SQ = window.SQ || {};
    const SQ = window.SQ;

    const CONFIG = {
        basePath: "/ui",
        defaultRoute: "dashboard",
        loginRoute: "login",
        loginPage: "/ui/login.html",
        layoutPath: "/ui/layouts",
        pagesPath: "/ui/pages",
        rootSelector: "#sq-root",
        contentSelector: "#sq-page-content",
        pageTitleSelector: "#sq-page-title",
        pageSubtitleSelector: "#sq-page-subtitle",
        pageActionsSelector: "#sq-page-actions",
        debug: true
    };

    const state = {
        currentRoute: null,
        currentLayout: null,
        currentManifest: null,
        loadedCss: {},
        loadedJs: {},
        isLoading: false
    };

    function routeName(route = "") {
        let value = String(route || CONFIG.defaultRoute)
            .replace(window.location.origin, "")
            .replace(/^\/ui\//, "")
            .replace(/^\/+/, "")
            .replace(/\.html$/, "")
            .split("?")[0];

        return value || CONFIG.defaultRoute;
    }

    function manifestUrl(route) {
        const name = routeName(route);
        return `${CONFIG.pagesPath}/${name}/${name}.json`;
    }

    function pageHtmlUrl(route) {
        const name = routeName(route);
        return `${CONFIG.pagesPath}/${name}/${name}.html`;
    }

    function layoutUrl(layout) {
        return `${CONFIG.layoutPath}/${layout}.html`;
    }

    async function fetchJson(url) {
        const res = await fetch(url, {
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        });

        if (!res.ok) {
            throw new Error("Unable to load JSON: " + url);
        }

        return res.json();
    }

    async function fetchHtml(url) {
        const res = await fetch(url, {
            credentials: "same-origin",
            headers: { "Accept": "text/html" }
        });

        if (!res.ok) {
            throw new Error("Unable to load HTML: " + url);
        }

        return res.text();
    }

    async function loadCss(url) {
        if (!url || state.loadedCss[url]) return;

        if (document.querySelector(`link[href="${url}"]`)) {
            state.loadedCss[url] = true;
            return;
        }

        await new Promise(function (resolve, reject) {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = url;
            link.setAttribute("data-sq-page-css", url);
            link.onload = resolve;
            link.onerror = function () {
                reject(new Error("Unable to load CSS: " + url));
            };
            document.head.appendChild(link);
        });

        state.loadedCss[url] = true;
    }

    async function loadJs(url) {
        if (!url || state.loadedJs[url]) return;

        if (document.querySelector(`script[src="${url}"]`)) {
            state.loadedJs[url] = true;
            return;
        }

        await new Promise(function (resolve, reject) {
            const script = document.createElement("script");
            script.src = url;
            script.setAttribute("data-sq-page-js", url);
            script.onload = resolve;
            script.onerror = function () {
                reject(new Error("Unable to load JS: " + url));
            };
            document.body.appendChild(script);
        });

        state.loadedJs[url] = true;
    }

    async function loadLayout(layoutName) {
        const root = document.querySelector(CONFIG.rootSelector);

        if (!root) {
            throw new Error("Root container not found: " + CONFIG.rootSelector);
        }

        if (state.currentLayout === layoutName && root.innerHTML.trim() !== "") {
            return;
        }

        root.innerHTML = await fetchHtml(layoutUrl(layoutName));
        state.currentLayout = layoutName;

        if (SQ.componentLoader && typeof SQ.componentLoader.load === "function") {
            await SQ.componentLoader.load(root);
        }
    }

    async function checkAuth(manifest) {
        const required = manifest.authentication && manifest.authentication.required === true;

        if (!required) return;

        if (SQ.auth && typeof SQ.auth.requireAuth === "function") {
            await SQ.auth.requireAuth();
        }
    }

    function setPageMeta(manifest) {
        document.title = `${manifest.title || manifest.name || "SaQshi"} | SaQshi`;

        const title = document.querySelector(CONFIG.pageTitleSelector);
        const subtitle = document.querySelector(CONFIG.pageSubtitleSelector);

        if (title) {
            title.textContent =
                manifest.pageHeader?.title ||
                manifest.title ||
                "";
        }

        if (subtitle) {
            subtitle.textContent =
                manifest.pageHeader?.subtitle ||
                manifest.description ||
                "";
        }
    }

    function renderActions(manifest) {
        const target = document.querySelector(CONFIG.pageActionsSelector);
        if (!target) return;

        target.innerHTML = "";

        (manifest.quickActions || []).forEach(function (action) {
            const a = document.createElement("a");
            a.href = action.url || "#";
            a.className = "sq-btn sq-btn-primary";
            a.setAttribute("data-sq-route", action.url || "#");

            a.innerHTML = `
                ${action.icon ? `<i class="bi ${action.icon}"></i>` : ""}
                ${escapeHtml(action.title || "Action")}
            `;

            target.appendChild(a);
        });
    }

    function renderBreadcrumb(manifest) {
        if (!SQ.breadcrumb || typeof SQ.breadcrumb.render !== "function") return;

        const items = (manifest.breadcrumb || []).map(function (item) {
            return {
                label: item.title || item.label,
                url: item.url,
                icon: item.icon
            };
        });

        SQ.breadcrumb.render(items);
    }

    async function loadPage(route, options = {}) {
        if (state.isLoading) return;

        const name = routeName(route);
        state.isLoading = true;

        try {
            // if (SQ.loader && typeof SQ.loader.show === "function") {
            //     SQ.loader.show("Loading page...");
            //  }
            const useLoader = options.loader !== false && name !== CONFIG.loginRoute;

            forceHideLoader();

            if (useLoader && SQ.loader && typeof SQ.loader.show === "function") {
                SQ.loader.show("Loading page...");
            }
            const manifest = await fetchJson(manifestUrl(name));

            await checkAuth(manifest);
            await loadLayout(manifest.layout || "dashboard");

            for (const css of manifest.assets?.css || []) {
                await loadCss(css);
            }

            const content = document.querySelector(CONFIG.contentSelector);

            if (!content) {
                throw new Error("Page content container not found: " + CONFIG.contentSelector);
            }

            content.innerHTML = await fetchHtml(pageHtmlUrl(name));

            setPageMeta(manifest);
            renderActions(manifest);
            renderBreadcrumb(manifest);

            if (SQ.componentLoader && typeof SQ.componentLoader.load === "function") {
                await SQ.componentLoader.load(content);
            }

            for (const js of manifest.assets?.js || []) {
                await loadJs(js);
            }

            state.currentRoute = name;
            state.currentManifest = manifest;

            if (options.history !== false) {
                history.pushState({ route: name }, "", `/ui/${name}.html`);
            }

            setActiveMenu();

            document.dispatchEvent(new CustomEvent("sq:page-loaded", {
                detail: {
                    route: name,
                    manifest: manifest
                }
            }));

            const module = SQ[name];

            if (module && typeof module.init === "function") {
                await module.init();
            }

        } catch (error) {
            console.error("[SQ Router Error]", error);

            const root = document.querySelector(CONFIG.rootSelector) || document.body;

            root.innerHTML = `
                <div class="sq-alert sq-alert-danger sq-m-5">
                    <div class="sq-alert-content">
                        <div class="sq-alert-title">Page Load Failed</div>
                        <div class="sq-alert-text">${escapeHtml(error.message || error)}</div>
                    </div>
                </div>
            `;
        } finally {
    state.isLoading = false;

    if (SQ.loader && typeof SQ.loader.hide === "function") {
        SQ.loader.hide();
    }

    document.querySelectorAll("#sq-page-loader, .sq-loader").forEach(function (el) {
        el.classList.remove("active", "success", "error");
        el.style.display = "none";
        el.setAttribute("aria-hidden", "true");
    });

    document.body.style.overflow = "";
}
    }
    function forceHideLoader() {
        document.querySelectorAll(".sq-loader").forEach(function (loader) {
            loader.classList.remove("active", "success", "error");
            loader.setAttribute("aria-hidden", "true");
        });

        document.body.style.overflow = "";
    }
    function navigate(route, params = {}, options = {}) {
        if (Object.keys(params).length) {
            setQuery(params);
        }

        return loadPage(routeName(route), options);
    }

    function go(path, params = {}, options = {}) {
        if (options.spa === true) {
            return navigate(path, params, options);
        }

        const url = buildUrl(path, params);

        if (options.replace) {
            window.location.replace(url);
        } else {
            window.location.href = url;
        }
    }

    function buildUrl(path, params = {}) {
        const url = new URL(
            path.startsWith("/") ? path : CONFIG.basePath + "/" + path,
            window.location.origin
        );

        Object.keys(params).forEach(function (key) {
            if (params[key] !== null && params[key] !== undefined && params[key] !== "") {
                url.searchParams.set(key, params[key]);
            }
        });

        return url.toString();
    }

    function setQuery(params = {}, options = {}) {
        const url = new URL(window.location.href);

        Object.keys(params).forEach(function (key) {
            if (params[key] === null || params[key] === undefined || params[key] === "") {
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

    function query(name, fallback = null) {
        return new URLSearchParams(window.location.search).get(name) || fallback;
    }

    function queries() {
        const obj = {};
        new URLSearchParams(window.location.search).forEach(function (value, key) {
            obj[key] = value;
        });
        return obj;
    }

    function removeQuery(keys = []) {
        const url = new URL(window.location.href);
        keys.forEach(function (key) {
            url.searchParams.delete(key);
        });
        window.history.replaceState({}, "", url.toString());
    }

    function setActiveMenu(selector = "[data-sq-nav]") {
        document.querySelectorAll(selector).forEach(function (link) {
            const href = link.getAttribute("href") || "";
            const active =
                href.includes(state.currentRoute || "") ||
                window.location.pathname === href;

            link.classList.toggle("is-active", active);

            if (active) {
                link.setAttribute("aria-current", "page");
            } else {
                link.removeAttribute("aria-current");
            }
        });
    }

    function reload() {
        if (state.currentRoute) {
            return loadPage(state.currentRoute, { history: false });
        }

        window.location.reload();
    }

    function back() {
        window.history.back();
    }

    function safeBack(fallback = CONFIG.defaultRoute) {
        if (window.history.length > 1) {
            back();
        } else {
            navigate(fallback);
        }
    }

    function bindLinks() {
        document.addEventListener("click", function (event) {
            const el = event.target.closest("[data-sq-route]");

            if (!el) return;

            event.preventDefault();

            const route = el.getAttribute("data-sq-route") || el.getAttribute("href");
            navigate(route);
        });
    }

    function init() {
        bindLinks();

        window.addEventListener("popstate", function (event) {
            const route = event.state?.route || state.currentRoute || CONFIG.defaultRoute;
            loadPage(route, { history: false });
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    function forceHideLoader() {
        if (SQ.loader && typeof SQ.loader.hide === "function") {
            SQ.loader.hide();
        }

        document.querySelectorAll(".sq-loader").forEach(function (loader) {
            loader.classList.remove("active", "success", "error");
            loader.setAttribute("aria-hidden", "true");
        });

        document.body.style.overflow = "";
    }
    SQ.router = {
        config(settings = {}) {
            Object.assign(CONFIG, settings);
        },

        navigate,
        loadPage,
        go,
        reload,
        back,
        safeBack,

        query,
        queries,
        setQuery,
        removeQuery,

        setActiveMenu,
        buildUrl,
        routeName,
        init,
        state
    };

    document.addEventListener("DOMContentLoaded", init);

})(window, document);