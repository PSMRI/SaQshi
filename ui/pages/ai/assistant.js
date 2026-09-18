(function (window, document) {
    "use strict";
    window.SQ = window.SQ || {};
    const SQ = window.SQ;
    const state = { history: [], recentHistory: [], sending: false, source: "local" };
    const $ = id => document.getElementById(id);
    const esc = value => String(value ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    const display = value => esc(value).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    const help = {
        local: "Searches only approved SaQshi documents and uploaded resources.",
        auto: "Searches SaQshi resources first. If no relevant resource is found, searches approved public-health websites.",
        web: "Searches approved public-health websites only. Do not include patient or personal information."
    };

    function render() {
        const target = $("aiAssistantMessages");
        if (!target) return;
        const rows = state.history.length ? state.history : [{ role: "assistant", message: "Welcome to SaQshi AI Assistant. Select a source tab, then ask your question." }];
        target.innerHTML = rows.map(row => `<div class="sq-ai-page-message is-${row.role === "user" ? "user" : "assistant"}">${display(row.message)}</div>`).join("");
        target.scrollTop = target.scrollHeight;
        renderRecent();
    }

    function renderRecent() {
        const target = $("aiAssistantRecent");
        if (!target) return;
        const questions = state.recentHistory.filter(row => row.role === "user").map(row => row.message).filter(Boolean).slice(-20).reverse();
        target.innerHTML = questions.length
            ? questions.map(question => `<button type="button" title="${esc(question)}" data-ai-recent-question="${esc(question)}"><i class="bi bi-chat-left-text"></i> ${esc(question)}</button>`).join("")
            : '<p class="sq-ai-recent-empty">Your recent questions will appear here.</p>';
        target.querySelectorAll("[data-ai-recent-question]").forEach(button => button.addEventListener("click", () => {
            const input = $("aiAssistantQuestion");
            if (input) { input.value = button.dataset.aiRecentQuestion || ""; input.focus(); }
        }));
    }

    function setSource(source) {
        state.source = ["local", "auto", "web"].includes(source) ? source : "local";
        document.querySelectorAll("[data-ai-page-source]").forEach(button => {
            const active = button.dataset.aiPageSource === state.source;
            button.classList.toggle("is-active", active);
            button.setAttribute("aria-selected", active ? "true" : "false");
        });
        const question = $("aiAssistantQuestion");
        if (question) question.placeholder = state.source === "web" ? "Ask a question from approved public-health websites..." : state.source === "auto" ? "Search SaQshi resources, then web if needed..." : "Ask a question from the SaQshi Repository...";
        const sourceHelp = $("aiAssistantSourceHelp");
        if (sourceHelp) sourceHelp.textContent = help[state.source];
    }

    async function loadHistory() {
        try {
            const response = await SQ.api.get("/chat/v1/history.php", {}, { loader: false, showError: false, redirectOnUnauthorized: false });
            state.history = response.data?.history || [];
            state.recentHistory = state.history.slice();
        } catch (_) {
            state.history = [];
        }
        render();
    }

    async function ask(question) {
        if (state.sending || !question.trim()) return;
        state.sending = true;
        const send = $("aiAssistantSend");
        if (send) { send.disabled = true; send.innerHTML = '<i class="bi bi-hourglass-split"></i> Thinking...'; }
        state.history.push({ role: "user", message: question.trim() });
        state.recentHistory.push({ role: "user", message: question.trim() });
        render();
        try {
            const response = await SQ.api.post("/chat/v1/send.php", {
                message: question.trim(),
                context_page: "ai/assistant",
                search_mode: state.source
            }, { loader: false, showError: false, timeout: 120000 });
            const assistant = { role: "assistant", message: response.data?.reply || "No response was returned." };
            state.history.push(assistant);
            state.recentHistory.push(assistant);
        } catch (error) {
            const assistant = { role: "assistant", message: error.message || "Unable to reach SaQshi AI Assistant." };
            state.history.push(assistant);
            state.recentHistory.push(assistant);
        } finally {
            state.sending = false;
            if (send) { send.disabled = false; send.innerHTML = '<i class="bi bi-send"></i> Ask SaQshi AI'; }
            render();
        }
    }

    function clear() {
        state.history = [];
        render();
        $("aiAssistantQuestion")?.focus();
    }

    function bind() {
        $("aiAssistantForm")?.addEventListener("submit", event => {
            event.preventDefault();
            const input = $("aiAssistantQuestion");
            const question = input?.value || "";
            if (input) input.value = "";
            ask(question);
        });
        $("aiAssistantClear")?.addEventListener("click", clear);
        document.querySelectorAll("[data-ai-page-source]").forEach(button => button.addEventListener("click", () => setSource(button.dataset.aiPageSource)));
        document.querySelectorAll("[data-ai-page-question]").forEach(button => button.addEventListener("click", () => ask(button.dataset.aiPageQuestion || "")));
    }

    SQ.aiAssistantPage = {
        init: async function () {
            bind();
            setSource("local");
            await loadHistory();
        }
    };
})(window, document);
