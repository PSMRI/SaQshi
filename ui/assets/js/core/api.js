/*!
 * ==========================================================
 * SQ-UI API Client v1.0
 * ----------------------------------------------------------
 * Project   : SaQshi Open Source
 * Component : Central API Client
 * Standard  : ES6 + Fetch API
 * License   : Apache-2.0
 * ==========================================================
 */

(function (window) {
    "use strict";

    if (!window.SQ) {
        window.SQ = {};
    }

    const SQ = window.SQ;

    const API = {
        baseUrl: "/api",
        timeout: 30000,
        csrfKey: "sq_csrf_token",
        debug: true
    };

    function buildUrl(endpoint, params = {}) {
        let url = endpoint.startsWith("http")
            ? endpoint
            : API.baseUrl + endpoint;

        const query = new URLSearchParams();

        Object.keys(params).forEach(function (key) {
            if (params[key] !== null && params[key] !== undefined && params[key] !== "") {
                query.append(key, params[key]);
            }
        });

        const queryString = query.toString();

        if (queryString) {
            url += (url.includes("?") ? "&" : "?") + queryString;
        }

        return url;
    }

    function getCsrfToken() {
        return localStorage.getItem(API.csrfKey) || "";
    }

    function setCsrfToken(token) {
        if (token) {
            localStorage.setItem(API.csrfKey, token);
        }
    }

    function clearSession() {
        localStorage.removeItem(API.csrfKey);
        localStorage.removeItem("sq_user");
        localStorage.removeItem("sq_active_assessment");
    }

    function requestHeaders(extraHeaders = {}, isFormData = false) {
        const headers = {};

        if (!isFormData) {
            headers["Content-Type"] = "application/json";
        }

        headers["Accept"] = "application/json";

        const csrf = getCsrfToken();

        if (csrf) {
            headers["X-CSRF-TOKEN"] = csrf;
            headers["X-CSRF-Token"] = csrf;
        }

        return Object.assign(headers, extraHeaders);
    }

    async function parseResponse(response) {
        const contentType = response.headers.get("content-type") || "";

        if (contentType.includes("application/json")) {
            return await response.json();
        }

        const text = await response.text();

        return {
            status: response.ok ? "success" : "error",
            message: text || response.statusText,
            data: null,
            errors: null
        };
    }

    async function request(method, endpoint, data = null, options = {}) {
        const controller = new AbortController();
        const timeout = options.timeout || API.timeout;

        const timer = setTimeout(function () {
            controller.abort();
        }, timeout);

        const isFormData = data instanceof FormData;

        const fetchOptions = {
            method: method,
            credentials: "include",
            headers: requestHeaders(options.headers || {}, isFormData),
            signal: controller.signal
        };

        if (data && method !== "GET") {
            fetchOptions.body = isFormData ? data : JSON.stringify(data);
        }

        const url = method === "GET"
            ? buildUrl(endpoint, data || {})
            : buildUrl(endpoint, options.params || {});

        try {
            if (options.loader !== false && SQ.loader) {
                SQ.loader.show(options.loaderText || "Please wait...");
            }

            if (API.debug) {
                console.log("[SQ API]", method, url, data || "");
            }

            const response = await fetch(url, fetchOptions);

            const result = await parseResponse(response);

            if (response.status === 401) {
                clearSession();

                if (SQ.toast) {
                    SQ.toast("Session expired. Please login again.", "danger");
                }

                if (options.redirectOnUnauthorized !== false) {
                    setTimeout(function () {
                        window.location.href = "/ui/login.html";
                    }, 800);
                }

                throw result;
            }

            if (!response.ok) {
                throw result;
            }

            if (result && result.csrf_token) {
                setCsrfToken(result.csrf_token);
            }

            if (result && result.data && result.data.csrf_token) {
                setCsrfToken(result.data.csrf_token);
            }

            return result;

        } catch (error) {
            if (error.name === "AbortError") {
                error = {
                    status: "error",
                    message: "Request timeout. Please try again.",
                    data: null,
                    errors: null
                };
            }

            if (SQ.toast && options.showError !== false) {
                SQ.toast(error.message || "Something went wrong", "danger");
            }

            throw error;

        } finally {
            clearTimeout(timer);

            if (options.loader !== false && SQ.loader) {
                SQ.loader.hide();
            }
        }
    }

    SQ.api = {
        config: function (settings = {}) {
            Object.assign(API, settings);
        },

        get: function (endpoint, params = {}, options = {}) {
            return request("GET", endpoint, params, options);
        },

        post: function (endpoint, data = {}, options = {}) {
            return request("POST", endpoint, data, options);
        },

        put: function (endpoint, data = {}, options = {}) {
            return request("PUT", endpoint, data, options);
        },

        patch: function (endpoint, data = {}, options = {}) {
            return request("PATCH", endpoint, data, options);
        },

        delete: function (endpoint, data = {}, options = {}) {
            return request("DELETE", endpoint, data, options);
        },

        upload: function (endpoint, formData, options = {}) {
            if (!(formData instanceof FormData)) {
                throw new Error("Upload requires FormData");
            }

            return request("POST", endpoint, formData, options);
        },

        download: async function (endpoint, params = {}, filename = "download") {
            const url = buildUrl(endpoint, params);

            const response = await fetch(url, {
                method: "GET",
                credentials: "include",
                headers: requestHeaders({}, false)
            });

            if (!response.ok) {
                throw new Error("Download failed");
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);

            const link = document.createElement("a");
            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();

            URL.revokeObjectURL(objectUrl);
        },

        setCsrfToken: setCsrfToken,
        getCsrfToken: getCsrfToken,
        clearSession: clearSession
    };

})(window);