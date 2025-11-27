<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaQshi - Public Health Facility Quality Assessment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
    html {
        opacity: 0;
        transition: opacity 1s ease;
    }
    html.loaded {
        opacity: 1;
    }

    /* Floating circles */
    body {
        font-family: 'Open Sans', sans-serif;
        background: radial-gradient(circle at top left, #fff3e0, #fffde7);
        color: #333;
        scroll-behavior: smooth;
        position: relative;
    }

    body::before, body::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        opacity: 0.2;
        z-index: 0;
        animation: float 10s infinite ease-in-out alternate;
    }

    body::before {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, #ffcc80, #ffb74d);
        top: 10%;
        left: 5%;
    }

    body::after {
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, #ffecb3, #ffe082);
        bottom: 10%;
        right: 5%;
    }

    @keyframes float {
        from { transform: translateY(0px); }
        to { transform: translateY(20px); }
    }

    /* Hero section */
    .hero {
        background: linear-gradient(270deg, #ff7e5f, #feb47b, #ff7e5f);
        background-size: 600% 600%;
        animation: gradientMove 15s ease infinite;
        color: white;
        padding: 80px 20px;
        text-align: center;
        overflow: hidden;
        position: relative;
        z-index: 1;
    }

    @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: 700;
        animation: zoomIn 1s ease forwards;
    }

    @keyframes zoomIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }

    .section-title {
        font-size: 2rem;
        margin-bottom: 20px;
        font-weight: 600;
        color: #ff7e5f;
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .feature-card, #about .card {
        border: none;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.4), rgba(255, 87, 34, 0.4));
        box-shadow: 0 8px 32px 0 rgba(255, 140, 0, 0.5);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        opacity: 0;
        transform: translateY(20px);
        position: relative;
        z-index: 1;
    }

    .feature-card.in-view, #about .card.in-view {
        opacity: 1;
        transform: translateY(0);
    }

    .feature-card:hover, #about .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 40px rgba(255, 87, 34, 0.6);
    }

    .feature-icon {
        font-size: 3rem;
        color: #ff9800;
        margin-bottom: 15px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }

    footer {
        background-color: #ff7e5f;
        color: white;
        padding: 20px 0;
        text-align: center;
        margin-top: 40px;
        position: relative;
        z-index: 1;
        opacity: 0;
        transition: opacity 1s ease;
    }

    footer.in-view {
        opacity: 1;
    }

    a.btn-learn {
        background-color: #fff;
        color: #ff7e5f;
        border: 2px solid #fff;
        border-radius: 30px;
        padding: 10px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
        box-shadow: 0 0 0 0 rgba(255, 126, 95, 0.5);
        animation: glow-ring 2s infinite;
        margin: 5px;
    }

    a.btn-learn:hover {
        background-color: #ff7e5f;
        color: white;
        border-color: #fff;
        box-shadow: 0 0 15px 5px rgba(255, 126, 95, 0.5);
    }

    @keyframes glow-ring {
        0% { box-shadow: 0 0 0 0 rgba(255, 126, 95, 0.5); }
        70% { box-shadow: 0 0 15px 15px rgba(255, 126, 95, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 126, 95, 0); }
    }

    #backToTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background-color: #ff7e5f;
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: none;
        justify-content: center;
        align-items: center;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 99;
        transition: all 0.3s ease;
    }

    #backToTop:hover {
        background-color: #ff5e3a;
    }
      #requestDemoBtn {
        position: fixed;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        background-color: #ff7e5f;
        color: white;
        border: none;
        padding: 12px 20px;
        font-weight: bold;
        border-radius: 0 30px 30px 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 100;
        transition: background-color 0.3s ease;
    }

    #requestDemoBtn:hover {
        background-color: #ff5e3a;
    }
    #loginBtn {
        position: fixed;
        top: 50%;
        right: 0;
        transform: translateY(-50%);
        background-color: #90ee90;
        color: white;
        border: none;
        padding: 12px 20px;
        font-weight: bold;
        border-radius: 30px 0 0 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 100;
        transition: background-color 0.3s ease;
    }

    #loginBtn:hover {
        background-color: #66cc66;
    }
 .hero {
    background: url('assets/images/3.png') center center no-repeat;
    background-size: cover;
    position: relative;
    color: white; /* Make text white */
    padding: 100px 20px;
    text-align: center;
    z-index: 1;
}

.hero::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(0, 0, 0, 0.5); /* black overlay with 50% opacity */
    z-index: 0;
}

.hero * {
    position: relative;
    z-index: 1;
}
@keyframes glowPulse {
    0% { box-shadow: 0 0 5px rgba(229, 3, 3, 0.92), 0 0 10px rgba(255, 255, 255, 0.4); }
    50% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.8), 0 0 30px rgba(255, 255, 255, 0.6); }
    100% { box-shadow: 0 0 5px rgba(255, 255, 255, 0.5), 0 0 10px rgba(255, 255, 255, 0.4); }
}

#requestDemoBtn {
    /* your existing styles... */
    animation: glowPulse 2s infinite;
}

#loginBtn {
    /* your existing styles... */
    animation: glowPulse 2s infinite;
}

#linkedinBtn {
    position: fixed;
    top: 60%; /* lower than Request Demo (which is 50%) */
    left: 0;
    transform: translateY(-50%);
    background-color: #0077b5; /* LinkedIn blue */
    color: white;
    border: none;
    padding: 12px 20px;
    font-weight: bold;
    border-radius: 0 30px 30px 0;
    box-shadow: 0 4px 12px rgb(210, 8, 8);
    cursor: pointer;
    z-index: 100;
    transition: background-color 0.3s ease;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: glowPulse 2s infinite;
}

#linkedinBtn:hover {
    background-color: #005582;
}


    </style>

</head>

<body>

   <header class="hero">
    <h1>SaQshi</h1>
    <p class="lead">Public Health Facility Quality Assessment Platform</p>
    <a href="#about" class="btn btn-learn">About</a>
    <a href="#features" class="btn btn-learn">Features</a>
   </header>

  <button id="requestDemoBtn" data-bs-toggle="modal" data-bs-target="#demoModal">Request Demo</button>
  <button id="loginBtn" onclick="window.location.href='login.php'">Login</button>
<button id="linkedinBtn" onclick="window.open('https://www.linkedin.com/search/results/all/?keywords=%23SaQshi&origin=GLOBAL_SEARCH_HEADER', '_blank')">
    <i class="fab fa-linkedin"></i> LinkedIn
</button>

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
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" id="phone" placeholder="Your Phone Number">
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" class="form-control" id="message" rows="3" placeholder="Your Message" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

            </div>
        </div>
    </div>

    <section id="features" class="container py-5">
        <h2 class="section-title">Key Features</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-clipboard-check"></i></div>
                    <h6>Comprehensive Facility Assessment</h6>
                    <p>Digitizes NQAS, LaQshya, MusQan checklists for all facility types with department-wise tracking.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-tasks"></i></div>
                    <h6>Action Plan Management</h6>
                    <p>Auto-generates and tracks corrective action plans for identified gaps in quality standards.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h6>Outcome Indicator Monitoring</h6>
                    <p>Captures and visualizes health outcome indicators with monthly and departmental trends.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <h6>KPI Tracking</h6>
                    <p>Real-time KPI dashboards for efficiency, service availability, equipment functionality, and more.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                    <h5>Certification Tracking</h5>
                    <p>Monitors facility readiness and progress towards state and national quality certifications.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                    <h6>Advanced Reporting & Analytics</h6>
                    <p>Generates interactive drill-down reports for facility, district, division, and state level monitoring.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-desktop"></i></div>
                    <h6>Enhanced UI/UX</h6>
                    <p>Modern, mobile-responsive design with optimized workflows for fast and intuitive data entry.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon"><i class="fas fa-code-branch"></i></div>
                    <h6>Open Source</h6>
                    <p>Built as an open-source platform for easy adoption and customization.</p>
                </div>
            </div>
        </div>
    </section>
  <section id="about" class="container py-5">
        <h2 class="section-title">About SaQshi</h2>
        <div class="row">
            <div class="col-md-12">
                <div class="card p-4">
                    <p>
                        SaQshi (System for Assessing Quality Standards in Health Institutions) is a comprehensive digital platform designed to monitor, assess, and improve the quality of healthcare services across public health facilities. Developed as a scalable and modular solution, SaQshi supports multiple national and state-specific quality frameworks, including NQAS, LaQshya, and MusQan. It enables real-time tracking of assessments, gap identification, action plan management, and performance reporting at facility, district, and state levels. With role-based access, dynamic dashboards, and integration-ready APIs, SaQshi empowers health departments to drive quality improvement initiatives with efficiency, transparency, and accountability.
                    </p>
                    <p>
                        The system supports real-time data capture, automated scoring, non-compliance analysis, and evidence-based action plan tracking using structured indicators mapped to each standard. Built on modular architecture with interoperability in mind, SaQshi provides role-based access and granular drill-down analytics.
                    </p>
                    <p>
                        It is aimed at strengthening accountability, improving service delivery, and enabling policy-makers to make informed decisions for continuous quality enhancement at scale.
                    </p>
                </div>
            </div>
        </div>
    </section>
<!-- Feature Matrix Table -->
<div class="row mt-5">
    <div class="col-md-12">
        <h3 class="section-title">Key Feature Evolution Matrix (MVP → V1 → V2)</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center align-middle">
                <thead class="table-dark">
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
                    <tr><td>Facility Assessment (Multiple facility types supported)</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>Action Plan Management (MOIC / Department wise)</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>Outcome Indicators Tracking</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>KPI Tracking</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>User Role & Login Management</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>District / State Dashboard</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>Basic Analytics</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>All Facility Types supported (DH, SDH, CHC, PHC, HWCs, SubCenters, etc.)</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>KPI & Outcome Indicator Tracking</td><td>❌</td><td>✔️</td><td>✔️</td></tr>
                    <tr><td>Reports as per NHRC Formats</td><td>❌</td><td>Partial</td><td>✔️</td></tr>
                    <tr><td>District, Division & State level Analytics</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>New UI/UX Enhancements</td><td>❌</td><td>❌</td><td>✔️</td></tr>                   
                    <tr><td>Certification Tracking</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>Mobile & Tab friendly responsive design</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>Downloadable Reports (PDF, Excel, PNG)</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>Full Department-wise Action Plans</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                    <tr><td>Advanced Dashboard Visualizations</td><td>❌</td><td>❌</td><td>✔️</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


   <footer>
        &copy; 2025 SaQshi  <p class="mb-0 text-muted"><?= date('Y') ?> Piramal Foundation. All Rights Reserved.</p>
    </footer>

    <button id="backToTop" title="Go to top">&#8679;</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.documentElement.classList.add("loaded");
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.feature-card, footer, #about .card').forEach(el => {
            observer.observe(el);
        });

        const backToTop = document.getElementById('backToTop');

        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        };

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

</body>

</html>
