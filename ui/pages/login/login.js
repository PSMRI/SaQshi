/*!
 * ==========================================================
 * SaQshi Login Page v1.0
 * ----------------------------------------------------------
 * Project : SaQshi Open Source
 * Module  : Login
 * File    : login.js
 * License : Apache-2.0
 * ==========================================================
 */

(function (window, document) {
    "use strict";

    if (!window.SQ) {
        window.SQ = {};
    }

    const SQ = window.SQ;

    function form() {
        return document.getElementById("loginForm");
    }

    function username() {
        return document.getElementById("username");
    }

    function password() {
        return document.getElementById("password");
    }

    function button() {
        return document.getElementById("loginButton");
    }

    function captcha() {
        return document.getElementById("captcha");
    }

    function captchaQuestion() {
        return document.getElementById("captchaQuestion");
    }

    function setButtonLoading(isLoading) {
        const btn = button();

        if (!btn) {
            return;
        }

        btn.disabled = isLoading;

        btn.innerHTML = isLoading
            ? `<span class="sq-btn-spinner"></span> Signing in...`
            : `<i class="bi bi-box-arrow-in-right"></i> Login`;
    }

    function togglePassword() {
        const input = password();
        const icon = document.querySelector("#togglePassword i");

        if (!input) {
            return;
        }

        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";

        if (icon) {
            icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
        }
    }

    async function loadCaptcha() {
        const question = captchaQuestion();
        const input = captcha();

        if (question) {
            question.textContent = "Loading...";
        }

        if (input) {
            input.value = "";
        }

        try {
            const response = await SQ.api.get(
                "/auth/v1/captcha.php",
                {},
                {
                    loader: false,
                    showError: false,
                    redirectOnUnauthorized: false
                }
            );

            const text =
                response.data?.question ||
                response.question ||
                "";

            if (question) {
                question.textContent = text || "Unable to load";
            }

        } catch (error) {
            console.error(error);

            if (question) {
                question.textContent = "Refresh captcha";
            }
        }
    }

    async function handleLogin(event) {
        event.preventDefault();

        const frm = form();

        if (!frm) {
            return;
        }

        if (SQ.validator) {
            const result = SQ.validator.validateAndShow(frm, {
                username: ["required"],
                password: ["required"],
                captcha: ["required"]
            });

            if (!result.valid) {
                return;
            }
        }

        const payload = {
            username: username().value.trim(),
            password: password().value,
            captcha: captcha().value.trim()
        };

        try {
            setButtonLoading(true);

            if (SQ.loader) {
                SQ.loader.show("Signing in...");
            }

            let response;

            if (SQ.auth && SQ.auth.login) {
                response = await SQ.auth.login(payload.username, payload.password, payload.captcha);
            } else {
                response = await SQ.api.post(
                    "/auth/v1/login.php",
                    payload,
                    {
                        loader: false
                    }
                );
            }

            const user =
                response.data?.user ||
                response.user ||
                null;

            if (user && SQ.auth && SQ.auth.saveUser) {
                SQ.auth.saveUser(user);
            }

            if (SQ.notification) {
                SQ.notification.success(response.message || "Login successful");
            }

            window.location.href = "/ui/dashboard.html";

        } catch (error) {
            console.error(error);
            loadCaptcha();

            if (SQ.notification) {
                SQ.notification.error(error.message || "Invalid username or password");
            }

            if (error.errors && SQ.validator) {
                SQ.validator.showErrors(frm, error.errors);
            }

        } finally {
            setButtonLoading(false);

            if (SQ.loader) {
                SQ.loader.hide();
            }
        }
    }

    function bindEvents() {
        const frm = form();

        if (frm) {
            frm.addEventListener("submit", handleLogin);
        }

        const toggle = document.getElementById("togglePassword");

        if (toggle) {
            toggle.addEventListener("click", togglePassword);
        }

        const refresh = document.getElementById("refreshCaptcha");

        if (refresh) {
            refresh.addEventListener("click", loadCaptcha);
        }

        const forgot = document.getElementById("forgotPasswordLink");

        if (forgot) {
            forgot.addEventListener("click", function (event) {
                event.preventDefault();

                if (SQ.notification) {
                    SQ.notification.info("Forgot password feature will be available soon.");
                }
            });
        }
    }

    function focusUsername() {
        const input = username();

        if (input) {
            input.focus();
        }
    }

    function init() {
        bindEvents();
        loadCaptcha();
        focusUsername();
    }

    SQ.login = {
        init,
        loadCaptcha
    };

})(window, document);
