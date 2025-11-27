<?php
include("assets/head/h.php");
$my_facility = $_SESSION['u_facilityid'] ?? 0;
?>

<style>
/* ============================
   FULL WHATSAPP STYLE CHAT UI
=============================== */

.date-separator {
    text-align: center;
    margin: 15px 0;
    position: relative;
}
.date-separator span {
    background: #d9e3f0;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    color: #333;
    font-weight: 500;
    display: inline-block;
}

.chat-container {
    height: 85vh;
    display: flex;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.chat-sidebar {
    width: 30%;
    background: #fcfcfc;
    border-right: 1px solid #e5e5e5;
    display: flex;
    flex-direction: column;
}

.chat-search {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
.chat-search input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    background: #fafafa;
}

.chat-list { flex: 1; overflow-y: auto; }

.chat-list-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    transition: 0.25s;
}
.chat-list-item:hover { background: #eef5ff; }
.chat-list-item.active { background: #dce9ff; }

.fac-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #007bff33;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    color: #007bff;
    margin-right: 12px;
}

.unread-badge {
    background: #007bff;
    color: #fff;
    padding: 3px 7px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: auto;
}

.chat-window {
    width: 70%;
    background: #eef2f9;
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: #fff;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.header-avatar {
    width: 38px;
    height: 38px;
    background: #007bff33;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 10px;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.message {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 18px;
    margin-bottom: 14px;
    font-size: 15px;
    line-height: 1.4;
    display: inline-block;
    clear: both;
}

.sent {
    background: #007bff;
    color: #fff;
    float: right;
}
.received {
    background: #fff;
    border: 1px solid #ddd;
    float: left;
}

.message-time {
    display: block;
    font-size: 11px;
    opacity: 0.6;
    margin-top: 4px;
    text-align: right;
}

.chat-input {
    display: flex;
    background: #fff;
    padding: 10px;
    border-top: 1px solid #ddd;
}

.chat-input textarea {
    width: 100%;
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 10px;
    resize: none;
    height: 45px;
}

.send-btn {
    background: #007bff;
    border-radius: 10px;
    padding: 0 22px;
    color: #fff;
    border: none;
    margin-left: 10px;
}
</style>


<div class="pcoded-main-container">
<div class="pcoded-content">

<h4 class="fw-bold text-primary mb-3">Facility Chat</h4>

<div class="chat-container">

    <div class="chat-sidebar">
        <div class="chat-search">
            <input type="text" id="facilitySearch" placeholder="Search facility...">
        </div>
        <div class="chat-list" id="facilityList"></div>
    </div>

    <div class="chat-window">
        <div class="chat-header" id="chatTitle">Select facility to start chat</div>
        <div class="chat-messages" id="chatMessages"></div>

        <form id="sendMessageForm" class="chat-input">
            <textarea id="messageBox" placeholder="Type message..."></textarea>
            <button class="send-btn">Send</button>
        </form>
    </div>

</div>
</div>
</div>


<script>
let myFacility = <?= $my_facility ?>;
let activeFacility = null;
let lastMessageId = 0;

/* Load facility list */
loadFacilityList();
setInterval(loadFacilityList, 5000);

function loadFacilityList() {
    $.get("assets/get/chat_backend.php", { load_facilities: 1 }, function(data) {
        $("#facilityList").html(data);
    });
}

/* OPEN CHAT */
function openChat(toFacility, name) {
    activeFacility = toFacility;
    lastMessageId = 0;

    $("#chatTitle").html(`
        <div class='header-avatar'>${name.charAt(0).toUpperCase()}</div> ${name}
    `);

    $(".chat-list-item").removeClass("active");
    $("#fac_" + toFacility).addClass("active");

    $("#chatMessages").html(""); // fresh start
    loadMessages(true);
}

/* LOAD NEW MESSAGES ONLY */
function loadMessages(forceScroll) {
    if (activeFacility === null) return;

    $.get("assets/get/chat_backend.php", {
        load_messages: 1,
        sender: myFacility,
        receiver: activeFacility,
        last_id: lastMessageId
    }, function(data) {
        
        if (data.trim() !== "") {
            $("#chatMessages").append(data);

            // get last message id
            let last = $(".message-item:last").data("id");
            if (last) lastMessageId = last;

            if (forceScroll)
                $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
        }
    });
}

/* AUTO SILENT REFRESH */
setInterval(() => loadMessages(false), 2000);

/* SEND MESSAGE */
$("#sendMessageForm").submit(function(e) {
    e.preventDefault();
    let msg = $("#messageBox").val().trim();
    if (!msg || activeFacility === null) return;

    $.post("assets/get/chat_backend.php", {
        send_message: 1,
        sender: myFacility,
        receiver: activeFacility,
        message: msg
    }, function() {
        $("#messageBox").val("");
        loadMessages(true);
    });
});

/* SEARCH FILTER */
$("#facilitySearch").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    $(".chat-list-item").filter(function() {
        $(this).toggle($(this).text().toLowerCase().includes(value));
    });
});
</script>

<?php include("assets/head/f.php"); ?>
