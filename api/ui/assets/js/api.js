/**
 * api.js
 * -------------------------------------------------------
 * Central API helper for SaQshi UI.
 * Handles:
 * - API base URL
 * - CSRF token
 * - GET / POST requests
 * - JSON parsing
 * - Unauthorized redirect
 * -------------------------------------------------------
 */

const SaqshiAPI = (() => {
   const API_BASE = "/api";

    const STORAGE_KEYS = {
        CSRF_TOKEN: "saqshi_csrf_token",
        USER: "saqshi_user"
    };

    function getCsrfToken() {
        return localStorage.getItem(STORAGE_KEYS.CSRF_TOKEN) || "";
    }

    function setCsrfToken(token) {
        if (token) {
            localStorage.setItem(STORAGE_KEYS.CSRF_TOKEN, token);
        }
    }

    function clearSession() {
        localStorage.removeItem(STORAGE_KEYS.CSRF_TOKEN);
        localStorage.removeItem(STORAGE_KEYS.USER);
    }

    function saveUser(user) {
        localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user || {}));
    }

    function getUser() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.USER) || "{}");
        } catch (e) {
            return {};
        }
    }

    async function request(path, options = {}) {
        const url = `${API_BASE}${path}`;

        const headers = {
            "Accept": "application/json",
            ...(options.headers || {})
        };

        if (options.body && !(options.body instanceof FormData)) {
            headers["Content-Type"] = "application/json";
        }

        const csrf = getCsrfToken();

        if (csrf) {
            headers["X-CSRF-TOKEN"] = csrf;
        }

        const response = await fetch(url, {
            credentials: "include",
            ...options,
            headers
        });

        let data = null;

        try {
            data = await response.json();
        } catch (e) {
            throw new Error("Invalid server response");
        }

        if (response.status === 401) {
            clearSession();
            window.location.href = "login.html";
            return;
        }

        if (!response.ok || data.status === "error") {
            throw new Error(data.message || "Request failed");
        }

        return data;
    }

    async function get(path) {
        return request(path, {
            method: "GET"
        });
    }

    async function post(path, body = {}) {
        return request(path, {
            method: "POST",
            body: JSON.stringify(body)
        });
    }

    async function put(path, body = {}) {
        return request(path, {
            method: "PUT",
            body: JSON.stringify(body)
        });
    }

    async function del(path, body = {}) {
        return request(path, {
            method: "DELETE",
            body: JSON.stringify(body)
        });
    }

    async function fetchCsrfToken() {
        const res = await get("/auth/v1/csrf.php");

        if (res.data && res.data.csrf_token) {
            setCsrfToken(res.data.csrf_token);
        }

        return res.data;
    }

    return {
        get,
        post,
        put,
        delete: del,
        fetchCsrfToken,
        getCsrfToken,
        setCsrfToken,
        clearSession,
        saveUser,
        getUser
    };
})();