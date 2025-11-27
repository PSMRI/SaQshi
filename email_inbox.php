<?php
include("assets/head/h.php");
$my_facility = $_SESSION['u_facilityid'] ?? 0;
?>
<style>
/* ============================
   WHATSAPP STYLE CHAT UI
============================= */
.date-separator {
    text-align: center;
    margin: 15px 0;
}
.date-separator span {
    background: #d9e3f0;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
}

.chat-container {
    height: 85vh;
    display: flex;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

/* Sidebar */
.chat-sidebar {
    width: 30%;
    background: #fcfcfc;
    border-right: 1px solid #e5e5e5;
    display: flex;
    flex-direction: column;
}
.chat-search { padding: 12px; border-bottom: 1px solid #eee; }
.chat-search input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.chat-list {
    flex: 1;
    overflow-y: auto;
}
.chat-list-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
}
.chat-list-item:hover { background: #eef5ff; }
.chat-list-item.active { background: #dce9ff; }

.fac-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: #007bff33;
    display: flex; justify-content: center; align-items: center;
    margin-right: 12px;
    font-size: 16px; font-weight: bold; color: #007bff;
}

/* Chat window */
.chat-window {
    width: 70%;
    background: #eef2f9;
    display: flex; flex-direction: column;
}

.chat-header {
    background: #fff; padding: 15px;
    border-bottom: 1px solid #ddd;
    font-size: 16px; font-weight: bold;
    display: flex; align-items: center;
}
.header-avatar {
    width: 38px; height: 38px;
    background: #007bff33;
    border-radius: 50%;
    display: flex; justify-content: center; align-items: center;
    margin-right: 10px; font-weight: bold;
}

/* Typing */
#typingIndicator { font-size: 12px; color: #007bff; margin-left: 10px; }

/* Messages */
.chat-messages { flex: 1; padding: 20px; overflow-y: auto; }
.message {
    max-width: 70%; padding: 12px 16px;
    border-radius: 18px; margin-bottom: 14px;
    display: inline-block; clear: both;
}
.sent { background:#007bff; color:#fff; float:right; }
.received { background:#fff; border:1px solid #ddd; float:left; }

.msg-ticks { font-size:12px; margin-left:6px; }

.message-time {
    font-size:11px; opacity:.6;
    margin-top:4px; text-align:right;
}

/* Upload Section */
#uploadArea {
    padding:12px; background:#fff;
    border-top:1px solid #ddd;
    display:none;
}
#uploadSuccess { color:#28a745; font-size:14px; margin-top:6px; }

/* Input bar */
.chat-input {
    display:flex; background:#fff;
    padding:8px; border-top:1px solid #ccc;
    align-items:center;
}

#attachmentBtn {
    font-size:22px; margin-right:10px;
    cursor:pointer; color:#007bff;
}

.chat-input textarea {
    flex:1; border:1px solid #ccc;
    border-radius:10px; padding:10px;
}

.send-btn {
    background:#007bff; color:#fff;
    padding:0 22px; border:none;
    border-radius:8px; margin-left:10px;
}
</style>

<div class="pcoded-main-container">
<div class="pcoded-content">

<h4 class="fw-bold text-primary mb-3">Facility Chat</h4>

<div class="chat-container">

    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-search">
            <input id="facilitySearch" placeholder="Search facility...">
        </div>
        <div id="facilityList" class="chat-list"></div>
    </div>

    <!-- Chat window -->
    <div class="chat-window">

        <div class="chat-header" id="chatTitle">
            Select facility to start chat
            <span id="typingIndicator"></span>
        </div>

        <div class="chat-messages" id="chatMessages"></div>

        <!-- Upload section -->
        <div id="uploadArea">
            <div id="uploadFileInfo"></div>
            <div style="background:#e3e7ef;height:8px;border-radius:10px;">
                <div id="uploadProgressBar"
                     style="height:8px;width:0%;background:#007bff;transition:.2s;">
                </div>
            </div>
            <button id="cancelUploadBtn"
                style="margin-top:8px;background:#dc3545;color:#fff;border:none;
                       padding:6px 12px;border-radius:6px;font-size:13px;">
                Cancel Upload
            </button>
        </div>

        <!-- Input -->
        <form id="sendMessageForm" class="chat-input">
            <span id="attachmentBtn">📎</span>
            <input type="file" id="fileInput" style="display:none;">
            <textarea id="messageBox" placeholder="Type message..."></textarea>
            <button class="send-btn">Send</button>
        </form>

    </div>

</div>
</div>
</div>

<script>

let myFacility     = <?= $my_facility ?>;
let activeFacility = null;
let lastMessageId  = 0;
let currentUpload  = null;

/* Load facility list every 5 sec */
loadFacilityList();
setInterval(() => loadFacilityList(), 5000);

/* Load facility list */
function loadFacilityList() {
    $.get("assets/get/chat_backend.php", { load_facilities:1 }, function(data){
        $("#facilityList").html(data);
    });
}

/* =========================
   OPEN CHAT
========================== */
function openChat(fid, name) {
    activeFacility = fid;
    lastMessageId  = 0;

    $("#chatTitle").html(`
        <div class='header-avatar'>${name.charAt(0)}</div> ${name}
        <span id="typingIndicator"></span>
    `);

    $(".chat-list-item").removeClass("active");
    $("#fac_" + fid).addClass("active");

    $("#chatMessages").html("");

    loadMessages(true);
}

/* =========================
   LOAD NEW MESSAGES
========================== */
function loadMessages(scrollDown) {
    if (activeFacility === null) return;

    $.get("assets/get/chat_backend.php", {
        load_messages : 1,
        sender        : myFacility,
        receiver      : activeFacility,
        last_id       : lastMessageId
    }, function(data){

        if (data.trim() !== "") {

            $("#chatMessages").append(data);

            let last = $(".message-item:last").data("id");
            if (last) lastMessageId = last;

            if (scrollDown)
                $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }
    });
}

/* Correct interval — NO duplicate execution */
setInterval(function(){
    loadMessages(false);
}, 1500);

/* =========================
   TYPING INDICATOR
========================== */
$("#messageBox").on("input", function(){
    if (!activeFacility) return;

    $.post("assets/get/chat_backend.php", {
        typing  : 1,
        sender  : myFacility,
        receiver: activeFacility
    });
});

setInterval(function(){
    if (!activeFacility) return;

    $.get("assets/get/chat_backend.php", {
        get_typing : 1,
        sender     : myFacility,
        receiver   : activeFacility
    }, function(data){
        $("#typingIndicator").html(data);
    });
}, 900);

/* =========================
   SEND TEXT MESSAGE
========================== */
$("#sendMessageForm").submit(function(e){
    e.preventDefault();

    let msg = $("#messageBox").val().trim();
    if (!msg) return;

    $.post("assets/get/chat_backend.php", {
        send_message:1,
        sender      :myFacility,
        receiver    :activeFacility,
        message     :msg
    }, function(){
        $("#messageBox").val("");
        /* ❗ DO NOT reload here — prevents duplicates */
    });
});

/* =========================
   FILE UPLOAD
========================== */
$("#attachmentBtn").click(() => $("#fileInput").click());

$("#fileInput").change(function(){
    if (!this.files.length) return;

    let file = this.files[0];
    let sizeMB = (file.size/(1024*1024)).toFixed(2);

    $("#uploadFileInfo").html(`<b>${file.name}</b> (${sizeMB} MB)`);
    $("#uploadProgressBar").css("width","0%");
    $("#uploadArea").show();

    let fd = new FormData();
    fd.append("chat_file", file);
    fd.append("sender", myFacility);
    fd.append("receiver", activeFacility);

    currentUpload = $.ajax({
        url:"assets/get/upload_chat_file.php",
        type:"POST",
        data:fd,
        contentType:false,
        processData:false,

        xhr:function(){
            let xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener("progress", function(e){
                    if (e.lengthComputable) {
                        let pct = Math.round((e.loaded/e.total)*100);
                        $("#uploadProgressBar").css("width", pct+"%");
                        $("#uploadFileInfo").html(`<b>${file.name}</b> (${pct}%)`);
                    }
                });
            }
            return xhr;
        },

        success:function(){
            $("#uploadFileInfo").html(`<span id="uploadSuccess">✔ Uploaded</span>`);
            $("#fileInput").val("");
            setTimeout(()=> $("#uploadArea").fadeOut(),800);
        },

        error:function(){
            $("#uploadFileInfo").html("<span style='color:red;'>❌ Upload failed</span>");
            setTimeout(()=> $("#uploadArea").fadeOut(),1200);
        }
    });
});

/* Cancel Upload */
$("#cancelUploadBtn").click(function(){
    if (currentUpload) {
        currentUpload.abort();
        $("#uploadFileInfo").html("<span style='color:#dc3545;'>Upload cancelled</span>");
        $("#uploadProgressBar").css("width","0%");
        setTimeout(()=> $("#uploadArea").fadeOut(),1000);
    }
});

/* Search */
$("#facilitySearch").on("keyup", function(){
    let v = $(this).val().toLowerCase();
    $(".chat-list-item").each(function(){
        $(this).toggle($(this).text().toLowerCase().includes(v));
    });
});

</script>

<?php include("assets/head/f.php"); ?>
