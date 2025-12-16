<?php
// facility_profile_with_cert_beauty.php  (Flat Able compact + Leaflet + BS4 DataTables)
include("assets/head/h.php"); ?>
<style>
/* ---------- Chat Widget Styling (Modern & Clean) ---------- */

.chat-wrapper {
    width: 100%;
    background: #fff;
    border-radius: 14px;
    padding: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #eb5d25ff, #1d4ed8);
    color: #fff;
    padding: 18px;
    text-align: center;
}

.chat-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.chat-header small {
    font-size: 12px;
    opacity: 0.9;
}

#messages {
    height: 55vh;
    overflow-y: auto;
    padding: 15px;
    background: #f8fafc;
}

.msg {
    margin: 12px 0;
    display: flex;
    width: 100%;
}

.msg.user {
    justify-content: flex-end;
}
.msg.bot {
    justify-content: flex-start;
}

.bubble {
    max-width: 75%;
    padding: 12px 16px;
    font-size: 15px;
    line-height: 1.5;
    border-radius: 14px;
    word-break: break-word;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.user .bubble {
    background: #2563eb;
    color: #fff;
    border-bottom-right-radius: 4px;
}

.bot .bubble {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #1e293b;
    border-bottom-left-radius: 4px;
}

.input-area {
    display: flex;
    gap: 10px;
    padding: 14px;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    position: sticky;
    bottom: 0;
}

#query {
    flex: 1;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 15px;
    background: #f8fafc;
}

#query:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

#sendBtn {
    background: #2563eb;
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    min-width: 90px;
}

#sendBtn:hover {
    background: #1e40af;
}
</style>

<div class="pcoded-main-container mt-3">
  <div class="pcoded-content">
    <div class="row">
      <div class="col-12">
        <div class="pc-card">
          <div class="card-body">

            <!-- Modern Chat UI -->
            <div class="chat-wrapper">

                <div class="chat-header">
                    <h2>NQAS Assistant</h2>
                    <small>Ask about NQAS, Kayakalp, LaQshya, PHC/CHC/DH, Quality Standards</small>
                </div>

                <div id="messages"></div>

                <div class="input-area">
                    <input type="text" id="query" placeholder="Ask your question... (e.g., NQAS क्या है?)">
                    <button id="sendBtn">Send</button>
                </div>

            </div>
            <!-- End Chat UI -->

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const messagesEl = document.getElementById("messages");
const queryEl = document.getElementById("query");
const sendBtn = document.getElementById("sendBtn");

function addMessage(text, who = "bot") {
    let div = document.createElement("div");
    div.className = "msg " + who;

    let bubble = document.createElement("div");
    bubble.className = "bubble";
    bubble.innerHTML = text;

    div.appendChild(bubble);
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
}

async function sendQuestion() {
    let q = queryEl.value.trim();
    if (q === "") return;

    addMessage(q, "user");
    queryEl.value = "";
    sendBtn.disabled = true;

    const fd = new FormData();
    fd.append("query", q);

    try {
        let res = await fetch("/chatbot.php", {
            method: "POST",
            body: fd
        });

        let txt = await res.text();
        console.log("RAW RESPONSE:", txt);

        let data = JSON.parse(txt);

        if (data && data.ok) {
            let msg = data.answer;
            if (data.description) {
                msg += "<br><small>" + data.description + "</small>";
            }
            addMessage(msg, "bot");
        } else {
            addMessage("Error: cannot get response.", "bot");
        }

    } catch (e) {
        addMessage("Network error: " + e.message, "bot");
    }

    sendBtn.disabled = false;
}

sendBtn.onclick = sendQuestion;

queryEl.addEventListener("keydown", function (e) {
    if (e.key === "Enter") sendQuestion();
});

addMessage("Hello! I am your NQAS Assistant. Ask me anything.");
</script>

<?php include("assets/head/f.php"); ?>


