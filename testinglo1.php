<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Live PHP Log</title>

<style>
:root {
  --bg: #0d1117;
  --panel: #161b22;
  --text: #c9d1d9;
  --green: #3fb950;
  --red: #f85149;
  --border: #30363d;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  background: var(--bg);
  color: var(--text);
}

/* Header */
.header {
  position: sticky;
  top: 0;
  background: var(--panel);
  padding: 12px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h3 {
  margin: 0;
  font-size: 16px;
}

.status {
  font-size: 13px;
  color: var(--green);
}

/* Controls */
.controls {
  padding: 10px 16px;
  border-bottom: 1px solid var(--border);
  background: #0f141b;
}

button {
  background: #21262d;
  color: var(--text);
  border: 1px solid var(--border);
  padding: 6px 10px;
  font-size: 12px;
  cursor: pointer;
  border-radius: 4px;
}

button:hover {
  background: #30363d;
}

/* Log panel */
.log-container {
  padding: 12px;
}

pre {
  height: calc(100vh - 120px);
  overflow-y: auto;
  padding: 12px;
  background: #010409;
  border: 1px solid var(--border);
  border-radius: 6px;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 13px;
  line-height: 1.4;
}
</style>
</head>

<body>

<div class="header">
  <h3>🟢 Live PHP Error Log</h3>
  <div class="status" id="status">Connected</div>
</div>

<div class="controls">
  <button onclick="clearLog()">Clear</button>
</div>

<div class="log-container">
  <pre id="log"></pre>
</div>

<script>
let lastSize = 0;
let autoScroll = true;

function fetchLog() {
  fetch('lgstream.php?size=' + lastSize, { cache: 'no-store' })
    .then(res => {
      if (!res.ok) throw new Error(res.status);
      document.getElementById('status').textContent = 'Connected';
      document.getElementById('status').style.color = '#3fb950';
      return res.text();
    })
    .then(data => {
      if (data) {
        const logEl = document.getElementById('log');
        logEl.textContent += data;
        if (autoScroll) logEl.scrollTop = logEl.scrollHeight;
        lastSize += data.length;
      }
    })
    .catch(err => {
      const st = document.getElementById('status');
      st.textContent = 'Error';
      st.style.color = '#f85149';
      console.error('Fetch error:', err);
    });
}

function clearLog() {
  document.getElementById('log').textContent = '';
  lastSize = 0;
}

setInterval(fetchLog, 1000);
</script>

</body>
</html>
