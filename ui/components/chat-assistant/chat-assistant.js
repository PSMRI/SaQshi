(function (window, document) {
    "use strict";

    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { open: false, sending: false, history: [], initialized: false, searchMode: "local" };

    function $(id) { return document.getElementById(id); }
    function esc(value) {
        return String(value ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
    function formatMessage(value) {
        return esc(value).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    }
    function currentRoute() {
        return SQ.router?.state?.currentRoute || new URLSearchParams(window.location.search).get("route") || "dashboard";
    }
    function setSearchMode(mode) {
        state.searchMode = ["local", "web", "auto"].includes(mode) ? mode : "local";
        document.querySelectorAll("[data-ai-chat-source]").forEach(button => {
            const active = button.getAttribute("data-ai-chat-source") === state.searchMode;
            button.classList.toggle("is-active", active);
            button.setAttribute("aria-selected", active ? "true" : "false");
        });
        const input = $("sqAiChatInput");
        if (input) input.placeholder = state.searchMode === "web"
            ? "Ask a public web question..."
            : state.searchMode === "auto"
                ? "Search SaQshi repository, then web if needed..."
                : "Ask from SaQshi repository...";
    }
    function render() {
        const target = $("sqAiChatMessages");
        if (!target) return;
        const rows = state.history.length ? state.history : [{
            role: "assistant",
            message: "Hi. I can help with assessment, checklist, CQI, KPI/Outcome, reports and certification workflows."
        }];
        target.innerHTML = rows.map(row => `
            <div class="sq-ai-chat-msg is-${esc(row.role === "user" ? "user" : "assistant")}">${formatMessage(row.message)}</div>
        `).join("");
        target.scrollTop = target.scrollHeight;
    }
    function setOpen(open) {
        state.open = open;
        const panel = $("sqAiChatPanel");
        const toggle = $("sqAiChatToggle");
        if (panel) panel.hidden = !open;
        if (toggle) toggle.setAttribute("aria-expanded", open ? "true" : "false");
        if (open) $("sqAiChatInput")?.focus();
    }
    async function loadHistory() {
        try {
            const res = await SQ.api.get("/chat/v1/history.php", {}, { loader: false, showError: false, redirectOnUnauthorized: false });
            state.history = res.data?.history || [];
            render();
        } catch (error) {
            render();
        }
    }
    async function send(message) {
        if (state.sending || !message.trim()) return;
        state.sending = true;
        state.history.push({ role: "user", message: message.trim() });
        render();
        try {
            const res = await SQ.api.post("/chat/v1/send.php", {
                message: message.trim(),
                context_page: currentRoute(),
                search_mode: state.searchMode
            }, {
                loader: false,
                showError: false,
                // Local Qwen can take longer than the shared 30-second API
                // default while it loads into memory; chat alone may wait.
                timeout: 120000
            });
            state.history = res.data?.history || state.history.concat([{ role: "assistant", message: res.data?.reply || "Done." }]);
        } catch (error) {
            state.history.push({ role: "assistant", message: error.message || "Unable to reach AI Chat Assistant." });
        } finally {
            state.sending = false;
            render();
        }
    }
    async function clear() {
        try {
            await SQ.api.post("/chat/v1/clear.php", {}, { loader: false, showError: false });
        } catch (error) {
            /* local clear still helps the user */
        }
        state.history = [];
        render();
    }
    function bind() {
        $("sqAiChatToggle")?.addEventListener("click", function () { setOpen(!state.open); });
        $("sqAiChatClose")?.addEventListener("click", function () { setOpen(false); });
        $("sqAiChatClear")?.addEventListener("click", clear);
        document.querySelectorAll("[data-ai-chat-source]").forEach(button => {
            button.addEventListener("click", function () { setSearchMode(button.getAttribute("data-ai-chat-source")); });
        });
        $("sqAiChatForm")?.addEventListener("submit", function (event) {
            event.preventDefault();
            const input = $("sqAiChatInput");
            const message = input?.value || "";
            if (input) input.value = "";
            send(message);
        });
        document.querySelectorAll("[data-ai-chat-suggestion]").forEach(btn => {
            btn.addEventListener("click", function () {
                send(btn.getAttribute("data-ai-chat-suggestion") || "");
            });
        });
    }
    async function init() {
        if (SQ.deployment?.load) await SQ.deployment.load();
        if (SQ.deployment?.current?.domain?.profile_code === "education" || SQ.deployment?.current?.modules?.active_profile === "education") {
            $("sqAiChat")?.remove();
            return;
        }
        if (state.initialized || !$("sqAiChat")) return;
        state.initialized = true;
        bind();
        render();
        loadHistory();
    }

    SQ.aiChatAssistant = { init, open: () => setOpen(true), close: () => setOpen(false), send };

    document.addEventListener("sq:component-loaded", function (event) {
        if (event.detail?.name === "chat-assistant") init();
    });
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
