<?php
include("assets/head/h.php");

$my_facility = $_SESSION['u_facilityid'] ?? 0;
$is_admin    = ($_SESSION['userrole'] ?? '') === 9;
?>
<style>
    /* ===========================
   WHATSAPP-LIKE CHAT UI
=========================== */

:root {
    --wa-green: #075e54;
    --wa-green-light: #dcf8c6;
    --wa-bg: #efeae2;
    --wa-border: #e0e0e0;
}

/* ================= CONTAINER ================= */
.chat-container {
    height: 85vh;
    display: flex;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    position: relative;
}

/* ================= SIDEBAR ================= */
.chat-sidebar {
    width: 30%;
    min-width: 260px;
    background: #f7f7f7;
    border-right: 1px solid var(--wa-border);
    display: flex;
    flex-direction: column;
    z-index: 2;
}

.chat-search {
    padding: 12px;
    background: #ededed;
}

.chat-search input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 20px;
    border: 1px solid var(--wa-border);
    outline: none;
    font-size: 14px;
}

.chat-list {
    flex: 1;
    overflow-y: auto;
}

.chat-list-item {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    cursor: pointer;
    transition: background .2s;
}

.chat-list-item:hover {
    background: #e9f5ff;
}

.chat-list-item.active {
    background: #d9edf7;
}

/* Avatar */
.fac-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--wa-green);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 12px;
    flex-shrink: 0;
}

/* Facility text */
.fac-info {
    min-width: 0;
}

.fac-name {
    font-size: 15px;
    line-height: 1.2;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* ================= CHAT WINDOW ================= */
.chat-window {
    width: 70%;
    background: var(--wa-bg);
    display: flex;
    flex-direction: column;
    position: relative;
}

/* ================= HEADER ================= */
.chat-header {
    background: var(--wa-green);
    color: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    font-weight: 600;
    gap: 10px;
}

.back-btn {
    display: none;
    font-size: 20px;
    cursor: pointer;
}

.header-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ================= MESSAGES ================= */
.chat-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
}

.message {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 12px;
    margin-bottom: 10px;
    clear: both;
    font-size: 14px;
}

.sent {
    background: var(--wa-green-light);
    float: right;
    border-top-right-radius: 4px;
}

.received {
    background: #fff;
    float: left;
    border-top-left-radius: 4px;
}

.broadcast {
    background: #fff3cd;
    color: #856404;
    text-align: center;
    margin: 14px auto;
    border-radius: 10px;
    max-width: 90%;
    font-size: 13px;
}

/* ================= INPUT ================= */
.chat-input {
    display: flex;
    align-items: center;
    padding: 10px;
    background: #f0f0f0;
    border-top: 1px solid var(--wa-border);
}

#attachmentBtn {
    font-size: 22px;
    margin-right: 10px;
    cursor: pointer;
}

.chat-input textarea {
    flex: 1;
    border-radius: 20px;
    border: 1px solid var(--wa-border);
    padding: 10px 14px;
    resize: none;
    outline: none;
    font-size: 14px;
}

.send-btn {
    background: var(--wa-green);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    margin-left: 10px;
    cursor: pointer;
}

/* ================= UPLOAD ================= */
#uploadArea {
    background: #fff;
    padding: 10px 16px;
    border-top: 1px solid var(--wa-border);
    display: none;
}

/* ================= MOBILE MODE (WHATSAPP STYLE) ================= */
@media (max-width: 768px) {

    .chat-container {
        height: calc(100vh - 70px);
        border-radius: 0;
    }

    .chat-sidebar {
        width: 100%;
        min-width: unset;
    }

    .chat-window {
        width: 100%;
        display: none;
    }

    /* When chat is opened */
    .chat-container.chat-open .chat-sidebar {
        display: none;
    }

    .chat-container.chat-open .chat-window {
        display: flex;
    }

    .back-btn {
        display: inline-block;
    }
}

</style>


<div class="pcoded-main-container">
    <div class="pcoded-content">

        <h6 class="fw-bold text-primary mb-3">Facility Chat</h6>

        <div class="chat-container">

            <!-- SIDEBAR -->
            <div class="chat-sidebar">
                <div class="chat-search">
                    <input id="facilitySearch" placeholder="Search facility...">
                </div>
                <div id="facilityList" class="chat-list"></div>
            </div>

            <!-- CHAT WINDOW -->
            <div class="chat-window">

                <!-- HEADER -->
                <div class="chat-header" id="chatTitle">
                    <span class="back-btn" onclick="goBack()">←</span>
                    <div class="header-avatar">?</div>
                    <span>Select facility to start chat</span>
                    <span id="typingIndicator"></span>
                </div>

                <!-- MESSAGES -->
                <div class="chat-messages" id="chatMessages"></div>

                <!-- UPLOAD -->
                <div id="uploadArea">
                    <div id="uploadFileInfo"></div>
                    <div style="background:#ddd;height:6px;border-radius:6px">
                        <div id="uploadProgressBar"
                            style="height:6px;width:0%;background:var(--wa-green)"></div>
                    </div>
                </div>

                <!-- INPUT -->
                <form id="sendMessageForm" class="chat-input">
                    <span id="attachmentBtn">+</span>
                    <input type="file" id="fileInput" hidden>
                    <textarea id="messageBox" rows="1" placeholder="Type a message"></textarea>
                    <button class="send-btn">➤</button>
                </form>

            </div>
        </div>

    </div>
</div>
<script>
/* =========================
   GLOBAL STATE
========================== */
let myFacility      = <?= (int)$my_facility ?>;
let activeFacility  = null;   // null = none selected, 0 = broadcast
let lastMessageId   = 0;
let isSending       = false;

/* =========================
   LOAD FACILITY LIST
========================== */
function loadFacilityList() {
    $.get("assets/get/chat_backend.php", { load_facilities: 1 }, function (html) {
        $("#facilityList").html(html);
    });
}
loadFacilityList();
setInterval(loadFacilityList, 5000);

/* =========================
   OPEN CHAT (DESKTOP + MOBILE)
========================== */
function openChat(fid, name) {

    activeFacility = fid;
    lastMessageId  = 0;

    const avatar = (fid === 0) ? "📢" : name.charAt(0).toUpperCase();

    $("#chatTitle").html(`
        <span class="back-btn" onclick="goBack()">←</span>
        <div class="header-avatar">${avatar}</div>
        <span>${name}</span>
        <span id="typingIndicator"></span>
    `);

    $(".chat-list-item").removeClass("active");
    $("#fac_" + fid).addClass("active");

    $("#chatMessages").html("");

    loadMessages(true);

    /* MOBILE: switch to chat view */
    $(".chat-container").addClass("chat-open");
}

/* =========================
   BACK (MOBILE)
========================== */
function goBack() {
    activeFacility = null;
    $(".chat-container").removeClass("chat-open");
}

/* =========================
   LOAD MESSAGES
========================== */
function loadMessages(scrollDown = false) {

    if (activeFacility === null) return;

    $.get("assets/get/chat_backend.php", {
        load_messages: 1,
        sender:   myFacility,
        receiver: activeFacility,
        last_id:  lastMessageId
    }, function (html) {

        if (!html || !html.trim()) return;

        const temp  = $("<div>").html(html);
        const items = temp.find(".message-item");

        if (!items.length) return;

        items.each(function () {
            const id = Number($(this).data("id"));
            if (id > lastMessageId) {
                $("#chatMessages").append(this);
                lastMessageId = id;
            }
        });

        if (scrollDown) {
            const box = $("#chatMessages")[0];
            box.scrollTop = box.scrollHeight;
        }
    });
}

/* Poll messages */
setInterval(() => loadMessages(false), 1500);

/* =========================
   SEND MESSAGE
========================== */
$("#sendMessageForm").on("submit", function (e) {
    e.preventDefault();

    if (isSending || activeFacility === null) return;

    const msg = $("#messageBox").val().trim();
    if (!msg) return;

    isSending = true;

    $.post("assets/get/chat_backend.php", {
        send_message: 1,
        sender:   myFacility,
        receiver: activeFacility,
        message:  msg
    })
    .done(() => $("#messageBox").val(""))
    .fail(() => alert("Message send failed"))
    .always(() => isSending = false);
});

/* =========================
   TYPING INDICATOR
   (DISABLED FOR BROADCAST)
========================== */
$("#messageBox").on("input", function () {
    if (activeFacility === null || activeFacility === 0) return;

    $.post("assets/get/chat_backend.php", {
        typing: 1,
        sender: myFacility,
        receiver: activeFacility
    });
});

setInterval(function () {

    if (activeFacility === null || activeFacility === 0) return;

    $.get("assets/get/chat_backend.php", {
        get_typing: 1,
        sender:   myFacility,
        receiver: activeFacility
    }, function (txt) {
        $("#typingIndicator").text(txt || "");
    });

}, 900);

/* =========================
   FILE UPLOAD
========================== */
$("#attachmentBtn").on("click", () => $("#fileInput").click());

$("#fileInput").on("change", function () {

    if (!this.files.length || activeFacility === null) return;

    const file = this.files[0];

    $("#uploadArea").show();
    $("#uploadFileInfo").html(`<b>${file.name}</b>`);
    $("#uploadProgressBar").css("width", "0%");

    const fd = new FormData();
    fd.append("chat_file", file);
    fd.append("sender",   myFacility);
    fd.append("receiver", activeFacility);

    $.ajax({
        url: "assets/get/upload_chat_file.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        xhr: function () {
            const x = $.ajaxSettings.xhr();
            x.upload.onprogress = function (e) {
                if (e.lengthComputable) {
                    const p = Math.round((e.loaded / e.total) * 100);
                    $("#uploadProgressBar").css("width", p + "%");
                }
            };
            return x;
        },
        success: function () {
            $("#uploadArea").fadeOut(800);
            $("#fileInput").val("");
        },
        error: function () {
            alert("Upload failed");
            $("#uploadArea").hide();
        }
    });
});

/* =========================
   SEARCH FACILITY
========================== */
$("#facilitySearch").on("keyup", function () {
    const q = this.value.toLowerCase();
    $(".chat-list-item").each(function () {
        $(this).toggle($(this).text().toLowerCase().includes(q));
    });
});
</script>


<?php include("assets/head/f.php"); ?>