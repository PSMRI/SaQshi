<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaQshi | Assessment</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }

        h2 {
            margin-bottom: 16px;
            color: #0d6efd;
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            max-width: 600px;
        }

        .question {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .options label {
            display: inline-block;
            margin-right: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .actions {
            margin-top: 16px;
        }

        button {
            background: #0d6efd;
            border: none;
            color: #fff;
            padding: 10px 18px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        button:hover {
            background: #0b5ed7;
        }

        .progress-box {
            margin-top: 30px;
            max-width: 600px;
        }

        pre {
            background: #111;
            color: #0f0;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body>

    <!-- Title -->
    <h2>SaQshi – Assessment</h2>

    <!-- Question Card -->
    <div class="card">

        <div id="question" class="question">
            Loading assessment...
        </div>

        <div class="options">
            <label>
                <input type="radio" name="f" value="0"> 0
            </label>

            <label>
                <input type="radio" name="f" value="1"> 1
            </label>

            <label>
                <input type="radio" name="f" value="2"> 2
            </label>
        </div>

        <div class="actions">
            <button onclick="saveResponse()">Save & Next</button>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="progress-box">
        <h3>Progress</h3>
        <pre id="progress">Loading...</pre>
    </div>

    <!-- JS -->
    <script src="api/assessment/v1/js/assessment.js"></script>

</body>
</html>
