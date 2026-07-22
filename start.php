<?php
// Public landing page: apply the same response security policy without the
// authenticated session/authorization bootstrap.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/assets/security/security.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaQshi - Public Health Facility Quality Assessment Platform</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha512-t4GWSVZO1eC8BM339Xd7Uphw5s17a86tIZIj8qRxhnKub6WoyhnrxeCIMeAqBPgdZGlCcG2PrZjMc+Wr78+5Xg==" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous">

    <style>

    html { opacity: 0; transition: opacity 1s ease; }
    html.loaded { opacity: 1; }

    /* Floating Background Circles */
    body {
        font-family: 'Inter', sans-serif;
        color: #333;
        background: radial-gradient(circle at top left, #fff4e3, #fffceb);
        position: relative;
    }

    body::before, body::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        opacity: 0.20;
        animation: float 10s infinite ease-in-out alternate;
    }

    body::before {
        width: 350px; height: 350px;
        background: radial-gradient(circle, #ffb560, #ff914d);
        top: 12%; left: 5%;
    }
    body::after {
        width: 280px; height: 280px;
        background: radial-gradient(circle, #ffe8a6, #ffd96d);
        bottom: 10%; right: 5%;
    }

    @keyframes float {
        from { transform: translateY(0px); }
        to { transform: translateY(25px); }
    }

    /* Hero Section with Background Image + Animated Gradient Overlay */
    .hero {
        background:
            linear-gradient(
                270deg, 
                rgba(255,126,95,0.35), 
                rgba(255,180,123,0.35), 
                rgba(255,126,95,0.35)
            ),
            url('assets/images/3.png') center center no-repeat;
        background-size: cover;
        animation: gradientMove 15s ease infinite;
        padding: 120px 20px;
        text-align: center;
        color: white;
        position: relative;
        border-bottom-left-radius: 40px;
        border-bottom-right-radius: 40px;
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .hero h1 {
        font-size: 4rem;
        font-weight: 800;
        letter-spacing: -1px;
        text-shadow: 0 4px 15px rgba(0,0,0,0.6);
    }

    .hero p {
        font-size: 1.3rem;
        opacity: 0.95;
        text-shadow: 0 2px 10px rgba(0,0,0,0.6);
    }

    .btn-learn {
        background: rgba(255,255,255,0.85);
        color: #ff6a3d;
        padding: 12px 32px;
        border-radius: 40px;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(255,255,255,0.25);
        transition: 0.3s ease;
    }

    .btn-learn:hover {
        transform: scale(1.08);
        background: white;
        box-shadow: 0 8px 25px rgba(255,255,255,0.5);
    }

    /* Feature Cards */
    .feature-card {
        border-radius: 20px;
        padding: 35px;
        text-align: center;
        background: rgba(255,255,255,0.55);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255,255,255,0.3);
        box-shadow: 0 10px 30px rgba(255,120,60,0.15);
        height: 100%;
        opacity: 0;
        transform: translateY(25px);
        transition: 0.35s ease;
    }

    .feature-card.in-view {
        opacity: 1;
        transform: translateY(0);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 14px 30px rgba(255,120,60,0.25);
    }

    .feature-icon i {
        font-size: 3rem;
        color: #ff7e5f;
        margin-bottom: 12px;
        filter: drop-shadow(0 5px 12px rgba(255,120,60,0.4));
    }

    /* Side Buttons */
    #requestDemoBtn, #loginBtn, #linkedinBtn {
        position: fixed;
        top: 50%;
        transform: translateY(-50%);
        padding: 18px 55px;
        border-radius: 0 40px 40px 0;
        border: none;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        z-index: 100;
        cursor: pointer;
        animation: glowPulse 2s infinite;
    }

    #requestDemoBtn { left: 0; background: #ff7e5f; }
    #loginBtn { right: 0; background: #1A6D2E; border-radius: 40px 0 0 40px; }
    #linkedinBtn { left: 0; top: 62%; background: #0077b5; padding: 15px 40px; }

    @keyframes glowPulse {
        0% { box-shadow: 0 0 8px rgba(255,255,255,0.5); }
        50% { box-shadow: 0 0 18px rgba(255,255,255,1); }
        100% { box-shadow: 0 0 8px rgba(255,255,255,0.5); }
    }

    /* About Card */
    #about .card {
        background: rgba(255,255,255,0.6);
        border-radius: 20px;
        padding: 30px;
        border: none;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        opacity: 0;
        transform: translateY(20px);
        transition: 0.4s ease;
    }

    #about .card.in-view {
        opacity: 1;
        transform: translateY(0px);
    }

    /* Matrix Toggle Button */
    .matrix-toggle-btn {
        background: #ff7e5f;
        color: white;
        padding: 14px 30px;
        border-radius: 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: 0.3s ease;
        box-shadow: 0 10px 25px rgba(255,126,95,0.5);
    }

    .matrix-toggle-btn:hover {
        transform: scale(1.05);
        background: #ff5e3a;
    }

    .matrix-container {
        display: none;
        margin-top: 20px;
    }

    /* Matrix Table */
    .matrix-table {
        background: rgba(255,255,255,0.8);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }

    .matrix-table th {
        background: #ff7e5f !important;
        color: white !important;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }

    /* Footer */
    footer {
        background: #ff7e5f;
        padding: 25px;
        color: white;
        text-align: center;
        opacity: 0;
        transition: 1s ease;
    }

    footer.in-view { opacity: 1; }

    /* Back to Top */
    #backToTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 55px;
        height: 55px;
        background: #ff7e5f;
        color: white;
        border-radius: 50%;
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        font-size: 26px;
        z-index: 150;
        transition: 0.3s ease;
    }

    #backToTop:hover { transform: scale(1.15); }

    </style>
</head>

<body>

    <!-- HERO -->
    <header class="hero">
        <h1>SaQshi</h1>
        <p class="lead">Public Health Facility Quality Assessment Platform</p>

        <a href="#about" class="btn btn-learn">About</a>
        <a href="#features" class="btn btn-learn">Features</a>
    </header>

    <!-- Side Buttons -->
    <button id="requestDemoBtn" data-bs-toggle="modal" data-bs-target="#demoModal">Request Demo</button>
    <button id="loginBtn" type="button">Login</button>
    <button id="linkedinBtn" type="button">
        <i class="fab fa-linkedin"></i> LinkedIn
    </button>

    <!-- FEATURES -->
    <section id="features" class="container py-5">
        <h2 class="text-center fw-bold" style="color:#ff7e5f;">Key Features</h2>

        <div class="row g-4 mt-3">

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clipboard-check"></i></div>
                    <h6>Comprehensive Facility Assessment</h6>
                    <p>Digitizes NQAS, LaQshya, MusQan, Kayakalp checklists with department-wise tracking.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-tasks"></i></div>
                    <h6>Action Plan Management</h6>
                    <p>Auto-generates and tracks corrective action plans for identified gaps in quality standards.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h6>Outcome Indicator Monitoring</h6>
                    <p>Captures and visualizes health outcome indicators with monthly and departmental trends.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <h6>KPI Tracking</h6>
                    <p>Real-time KPI dashboards for efficiency, service availability, equipment functionality, and more.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                    <h6>Certification Tracking</h6>
                    <p>Monitors facility readiness and progress toward state and national quality certifications.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                    <h6>Advanced Reporting & Analytics</h6>
                    <p>Generates interactive drill-down reports for facility, district, division, and state level monitoring.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-desktop"></i></div>
                    <h6>Enhanced UI/UX</h6>
                    <p>Modern, mobile-responsive design with intuitive data entry workflows.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-code-branch"></i></div>
                    <h6>Open Source</h6>
                    <p>Built as an open-source platform for easy adoption and customization.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="container py-5">
        <h2 class="text-center fw-bold" style="color:#ff7e5f;">About SaQshi</h2>

        <div class="card p-4">
            <p>
               <b> SaQshi (System for Assessing Quality Standards in Health Institutions) </b> is a comprehensive digital platform designed to monitor, assess, and improve the quality of healthcare services across public health facilities. Developed as a scalable and modular solution, SaQshi supports multiple national and state-specific quality frameworks, including NQAS, LaQshya, and MusQan. It enables real-time tracking of assessments, gap identification, action plan management, and performance reporting at facility, district, and state levels. With role-based access, dynamic dashboards, and integration-ready APIs, SaQshi empowers health departments to drive quality improvement initiatives with efficiency, transparency, and accountability.
            </p>
            <p>
                The system supports real-time data capture, automated scoring, non-compliance analysis, and evidence-based action plan tracking using structured indicators mapped to each standard. Built on modular architecture with interoperability in mind, SaQshi provides role-based access and granular drill-down analytics.
            </p>
            <p>
                It is aimed at strengthening accountability, improving service delivery, and enabling policy-makers to make informed decisions for continuous quality enhancement at scale.
            </p>
        </div>
    </section>

    <!-- COLLAPSIBLE MATRIX -->
    <div class="container mt-5 text-center">

        <button id="toggleMatrixBtn" class="matrix-toggle-btn">
            <span id="toggleIcon">＋</span> Key Feature Evolution Matrix (MVP → V1 → V2)
        </button>

        <div id="matrixContent" class="matrix-container">

            <div class="matrix-table table-responsive mt-4">
                <table class="table table-bordered text-center align-middle">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>MVP</th>
                            <th>V1</th>
                            <th>V2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Facility Assessment (Checklist-based)</td><td>✔️</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>Single facility type focus</td><td>✔️</td><td>❌</td><td>❌</td></tr>
                        <tr><td>Basic Reports</td><td>✔️</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>Facility Assessment (Multiple facility types)</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>Action Plan Management</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>Outcome Indicators Tracking</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>KPI Tracking</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>User Role & Login Management</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>District / State Dashboard</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>Basic Analytics</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                        <tr><td>All Facility Types supported</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Reports as per NHRC Formats</td><td>❌</td><td>Partial</td><td>✔️</td></tr>
                        <tr><td>District, Division & State Analytics</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>New UI/UX Enhancements</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Certification Tracking</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Mobile & Tab friendly design</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Downloadable Reports</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Department-wise Action Plans</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                        <tr><td>Advanced Visualizations</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        © 2025 SaQshi  
        <p class="mb-0">Piramal Foundation. All Rights Reserved.</p>
    </footer>

    <div id="backToTop">&#8679;</div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha512-VK2zcvntEufaimc+efOYi622VN5ZacdnufnmX7zIhCPmjhKnOi9ZDMtg1/ug5l183f19gG1/cBstPO4D8N/Img==" crossorigin="anonymous"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.documentElement.classList.add("loaded");
            document.getElementById("loginBtn")?.addEventListener("click", () => {
                window.location.assign("login.php");
            });
            document.getElementById("linkedinBtn")?.addEventListener("click", () => {
                window.open("https://www.linkedin.com/search/results/all/?keywords=%23SaQshi", "_blank", "noopener");
            });
        });

        /* Animate on Scroll */
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting){ entry.target.classList.add("in-view"); }
            });
        });

        document.querySelectorAll(".feature-card, #about .card, footer").forEach(el => observer.observe(el));

        /* Back to Top */
        const backToTop = document.getElementById("backToTop");
        window.onscroll = () => backToTop.style.display =
            window.scrollY > 200 ? "flex" : "none";
        backToTop.onclick = () => window.scrollTo({top: 0, behavior: "smooth"});

        /* Matrix Toggle Logic */
        const btn = document.getElementById("toggleMatrixBtn");
        const matrix = document.getElementById("matrixContent");
        const icon = document.getElementById("toggleIcon");

        btn.onclick = () => {
            if(matrix.style.display === "none" || matrix.style.display === ""){
                matrix.style.display = "block";
                icon.textContent = "−";
            } else {
                matrix.style.display = "none";
                icon.textContent = "＋";
            }
        };
    </script>
<!-- Request Demo Modal -->
<div class="modal fade" id="demoModal" tabindex="-1" aria-labelledby="demoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="demoModalLabel">Request a Demo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form action="submit_demo.php" method="POST">

                    <!-- NAME (letters only) -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control" 
                            id="name"
                            placeholder="Your Name" 
                            required
                            oninput="this.value = this.value.replace(/[^A-Za-z ]/g,'');"
                        >
                    </div>

                    <!-- EMAIL (email format only) -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control" 
                            id="email"
                            placeholder="name@example.com" 
                            required
                        >
                    </div>

                    <!-- PHONE (numbers only) -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control" 
                            id="phone"
                            placeholder="Your Phone Number"
                            maxlength="10"
                            oninput="this.value = this.value.replace(/[^0-9]/g,'');"
                        >
                    </div>

                    <!-- MESSAGE -->
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea 
                            name="message" 
                            class="form-control" 
                            id="message" 
                            rows="3" 
                            placeholder="Your Message" 
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>

                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>
