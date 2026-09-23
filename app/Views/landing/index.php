<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Lubell Nigeria Limited'); ?></title>
    <meta name="description" content="Lubell Nigeria Limited delivers quality construction, design, remodelling, and turnkey project solutions across residential, hospitality, healthcare, industrial, commercial, and educational sectors.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --lubell-dark: #0d1b2a;
            --lubell-navy: #14213d;
            --lubell-gold: #d4a447;
            --lubell-gold-soft: #f3d79b;
            --lubell-slate: #f4f6f8;
            --lubell-muted: #556273;
            --lubell-card: #ffffff;
            --lubell-success: #1c9a6b;
            --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: var(--lubell-dark);
            line-height: 1.6;
        }

        a { text-decoration: none; }
        img { max-width: 100%; display: block; }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(13, 27, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-mark {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #fff;
            padding: 0.4rem 0;
        }

        .brand-logo {
            height: 52px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .navbar-nav .nav-link {
            border-radius: 999px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255,255,255,0.08);
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            padding: 0.7rem 0.9rem !important;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #fff !important;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--lubell-gold), #c88e1a);
            border: none;
            color: #111827;
            font-weight: 700;
            padding: 0.9rem 1.6rem;
            border-radius: 999px;
            box-shadow: 0 14px 32px rgba(212, 164, 71, 0.3);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #f0c76f, var(--lubell-gold));
            color: #111827;
        }

        .btn-outline-custom {
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-weight: 600;
            padding: 0.9rem 1.6rem;
            border-radius: 999px;
        }

        .hero {
            position: relative;
            background: linear-gradient(135deg, rgba(13,27,42,0.97), rgba(20,33,61,0.84)),
                        url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            color: #fff;
            padding: 130px 0 80px;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(13,27,42,0.82), rgba(13,27,42,0.2));
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--lubell-gold-soft);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(2.6rem, 4vw, 5rem);
            font-weight: 900;
            line-height: 1.08;
            margin: 1.2rem 0 1rem;
        }

        .lead {
            font-size: 1.08rem;
            color: rgba(255,255,255,0.78);
            max-width: 640px;
        }

        .hero-stats {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
        }

        .site-trust {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .site-trust-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            color: rgba(255,255,255,0.82);
            font-weight: 600;
            text-align: center;
        }

        .hero-stat {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            padding: 1rem 1.1rem;
            min-width: 150px;
        }

        .hero-stat strong {
            display: block;
            font-size: 1.6rem;
            color: #fff;
            font-weight: 800;
        }

        .hero-stat span {
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.72);
        }

        .hero-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 26px;
            padding: 1.4rem;
            backdrop-filter: blur(8px);
        }

        .mini-card {
            background: rgba(255,255,255,0.05);
            border-radius: 18px;
            padding: 1rem;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .section {
            padding: 100px 0;
        }

        .section-title {
            font-size: clamp(2rem, 3vw, 3rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            color: var(--lubell-muted);
            max-width: 760px;
            margin-bottom: 2rem;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 2rem;
            align-items: center;
        }

        .about-card {
            background: var(--lubell-card);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(15, 23, 42, 0.05);
        }

        .feature-list {
            margin: 1.6rem 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.9rem;
        }

        .feature-list li {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            color: var(--lubell-muted);
        }

        .feature-list i {
            color: var(--lubell-success);
            font-size: 1.1rem;
            margin-top: 0.2rem;
        }

        .metric-card {
            background: linear-gradient(135deg, #f5f8fb, #eef4ff);
            border-radius: 22px;
            padding: 2rem;
            box-shadow: var(--shadow-soft);
        }

        .metric-card .value {
            font-size: clamp(2rem, 3vw, 3rem);
            font-weight: 900;
            color: var(--lubell-dark);
        }

        .service-card {
            background: #fff;
            border: 1px solid rgba(15,23,42,0.06);
            border-radius: 24px;
            padding: 2rem;
            height: 100%;
            box-shadow: var(--shadow-soft);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 52px rgba(15, 23, 42, 0.12);
        }

        .service-icon {
            width: 62px;
            height: 62px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(212,164,71,0.18), rgba(20,33,61,0.06));
            color: var(--lubell-navy);
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .project-card {
            overflow: hidden;
            border: 1px solid rgba(15,23,42,0.06);
            border-radius: 24px;
            background: #fff;
            box-shadow: var(--shadow-soft);
            height: 100%;
        }

        .project-thumb {
            height: 260px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .project-thumb::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent, rgba(13,27,42,0.52));
        }

        .project-body {
            padding: 1.4rem 1.4rem 1.6rem;
        }

        .project-body .badge {
            background: rgba(212,164,71,0.12);
            color: #8c6617;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.55rem 0.8rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-size: 0.68rem;
        }

        .cta-band {
            background: linear-gradient(135deg, #0d1b2a, #14213d);
            color: #fff;
            border-radius: 28px;
            padding: 2.2rem 2rem;
            box-shadow: var(--shadow-soft);
        }

        .contact-box {
            background: #fff;
            border-radius: 22px;
            padding: 2rem;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(15,23,42,0.06);
        }

        .contact-item {
            display: flex;
            gap: 0.9rem;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(15,23,42,0.08);
        }

        .contact-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .contact-item i {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(212,164,71,0.12);
            color: var(--lubell-navy);
            font-size: 1.2rem;
        }

        .footer {
            background: #09131d;
            color: rgba(255,255,255,0.7);
            padding: 2.5rem 0;
        }

        .footer a { color: rgba(255,255,255,0.8); }

        @media (max-width: 991.98px) {
            .about-grid { grid-template-columns: 1fr; }
            .hero { padding-top: 100px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg topbar">
        <div class="container">
            <a class="navbar-brand brand-mark" href="#home" aria-label="Lubell Nigeria Limited home">
                <img class="brand-logo" src="https://lubell.com.ng/wp-content/uploads/2020/08/logos.png" alt="Lubell Nigeria Limited logo">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNav" aria-controls="primaryNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="primaryNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects">Projects</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-outline-custom" href="/ERP/public/login">ERP Portal</a></li>
                    <li class="nav-item"><a class="btn btn-primary-custom" href="#contact">Get a Quote</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero" id="home">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="eyebrow"><i class="bi bi-building-check"></i> Trusted since 1992</div>
                    <h1>Building with technique, constructing with devotion.</h1>
                    <p class="lead">Lubell Nigeria Limited is a leading indigenous construction company delivering high-quality residential, commercial, hospitality, healthcare, industrial and educational projects from design to completion.</p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a class="btn btn-primary-custom" href="#projects">View Projects</a>
                        <a class="btn btn-outline-custom" href="#about">Learn More</a>
                        <a class="btn btn-primary-custom" href="/ERP/public/login">ERP Portal</a>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <strong>30+</strong>
                            <span>Years experience</span>
                        </div>
                        <div class="hero-stat">
                            <strong>100+</strong>
                            <span>Projects executed</span>
                        </div>
                        <div class="hero-stat">
                            <strong>8+</strong>
                            <span>States covered</span>
                        </div>
                    </div>
                    <div class="site-trust">
                        <div class="site-trust-item">Quality</div>
                        <div class="site-trust-item">Safety</div>
                        <div class="site-trust-item">Timely Delivery</div>
                        <div class="site-trust-item">Integrity</div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hero-card">
                        <div class="mini-card mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-uppercase small text-white-50">Current specialty</span>
                                <span class="badge rounded-pill bg-warning text-dark">Top-tier delivery</span>
                            </div>
                            <h3 class="mb-1">Design, construction & turnkey solutions</h3>
                        </div>
                        <div class="mini-card mb-3">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong class="text-white">Client-focused project management</strong>
                            </div>
                            <p class="mb-0 text-white-50">We work closely with consultants and stakeholders to ensure timely delivery, cost efficiency and lasting value.</p>
                        </div>
                        <div class="mini-card mb-3">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="bi bi-building text-warning"></i>
                                <strong class="text-white">End-to-end capability</strong>
                            </div>
                            <p class="mb-0 text-white-50">From concept planning to execution, Lubell manages complex technical assignments with professionalism and accountability.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="section" id="about">
            <div class="container">
                <div class="about-grid">
                    <div>
                        <div class="eyebrow text-dark mb-3" style="background: rgba(212,164,71,0.12); border-color: rgba(212,164,71,0.2); color: #7d5b18 !important; letter-spacing: 0.08em; width: fit-content;">About Lubell</div>
                        <h2 class="section-title">A construction partner built on integrity, performance and value creation.</h2>
                        <p class="section-subtitle mb-3">As a corporate entity, Lubell Nigeria Limited has executed projects across residential, hospitality, industrial, healthcare, commercial and educational sectors. Our portfolio includes residential estates, shopping plazas, high-rise office buildings, and academic institutions.</p>
                        <p class="section-subtitle mb-0">Founded in Abuja in 1992, we have grown into a reputable construction and development company driven by technical expertise, strong project leadership, and a commitment to client satisfaction.</p>
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle-fill"></i><span>Experienced leadership team and project-focused professionals.</span></li>
                            <li><i class="bi bi-check-circle-fill"></i><span>Strong delivery capability across complex technical environments.</span></li>
                            <li><i class="bi bi-check-circle-fill"></i><span>Commitment to quality, transparency, and timely project completion.</span></li>
                        </ul>
                    </div>
                    <div class="metric-card">
                        <div class="mb-4">
                            <div class="text-uppercase small text-muted fw-bold">Our vision</div>
                            <div class="value mt-2">To be the leading indigenous construction company.</div>
                        </div>
                        <div class="mb-4">
                            <div class="text-uppercase small text-muted fw-bold">Our mission</div>
                            <p class="mb-0 mt-2 text-secondary">To build long-term relationships based on integrity, performance, value creation and client satisfaction.</p>
                        </div>
                        <div class="d-grid gap-2">
                            <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 bg-white">
                                <span class="fw-semibold">Project sectors</span>
                                <span class="badge bg-success-subtle text-success fw-bold">6</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 bg-white">
                                <span class="fw-semibold">Business locations</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Abuja & beyond</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section bg-light" id="services">
            <div class="container">
                <div class="text-center mx-auto" style="max-width:760px;">
                    <div class="eyebrow text-dark mb-3" style="background: rgba(212,164,71,0.12); border-color: rgba(212,164,71,0.2); color: #7d5b18 !important; letter-spacing: 0.08em; width: fit-content; margin:0 auto;">What we do</div>
                    <h2 class="section-title">Leading innovative construction solutions for complex projects.</h2>
                </div>
                <div class="row g-4 mt-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-rulers"></i></div>
                            <h4>Design</h4>
                            <p class="text-secondary mb-0">Dedicated in-house resources manage the design process to align with project objectives and operational requirements.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-cone-striped"></i></div>
                            <h4>Construction</h4>
                            <p class="text-secondary mb-0">We deliver diverse construction projects for both public and private sectors with a strong emphasis on quality and efficiency.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-house-door"></i></div>
                            <h4>Remodelling</h4>
                            <p class="text-secondary mb-0">Interior and exterior remodeling projects handled by a skilled team with attention to detail and durability.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-kanban"></i></div>
                            <h4>Turnkey Projects</h4>
                            <p class="text-secondary mb-0">Integrated design and construction teams deliver value-added solutions from planning through completion.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="projects">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
                    <div>
                        <div class="eyebrow text-dark mb-3" style="background: rgba(212,164,71,0.12); border-color: rgba(212,164,71,0.2); color: #7d5b18 !important; letter-spacing: 0.08em; width: fit-content;">Our projects</div>
                        <h2 class="section-title mb-0">Selected project highlights</h2>
                    </div>
                    <a href="https://lubell.com.ng/projects/" target="_blank" rel="noreferrer" class="btn btn-primary-custom mt-3 mt-md-0">View all projects</a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <article class="project-card">
                            <div class="project-thumb" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=80');"></div>
                            <div class="project-body">
                                <span class="badge">Healthcare</span>
                                <h4 class="mt-3 mb-2">Institute of Human Virology</h4>
                                <p class="text-secondary mb-0">FCT, Abuja</p>
                            </div>
                        </article>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <article class="project-card">
                            <div class="project-thumb" style="background-image: url('https://images.unsplash.com/photo-1511818966892-d7d671e672a2?auto=format&fit=crop&w=900&q=80');"></div>
                            <div class="project-body">
                                <span class="badge">Education</span>
                                <h4 class="mt-3 mb-2">Hostel Blocks / Medical Centre</h4>
                                <p class="text-secondary mb-0">FUGA, Yobe State</p>
                            </div>
                        </article>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <article class="project-card">
                            <div class="project-thumb" style="background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=900&q=80');"></div>
                            <div class="project-body">
                                <span class="badge">Academic</span>
                                <h4 class="mt-3 mb-2">Faculty of Science, Gombe State University</h4>
                                <p class="text-secondary mb-0">Gombe State</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section py-0">
            <div class="container">
                <div class="cta-band d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="text-uppercase small text-warning fw-bold mb-2">Commitment to excellence</div>
                        <h3 class="mb-0">Need a trusted construction partner for your next project?</h3>
                    </div>
                    <a href="#contact" class="btn btn-primary-custom">Talk to our team</a>
                </div>
            </div>
        </section>

        <section class="section" id="contact">
            <div class="container">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <div class="eyebrow text-dark mb-3" style="background: rgba(212,164,71,0.12); border-color: rgba(212,164,71,0.2); color: #7d5b18 !important; letter-spacing: 0.08em; width: fit-content;">Contact us</div>
                        <h2 class="section-title">Let’s discuss your next development.</h2>
                        <p class="section-subtitle">We are ready to support residential, commercial, hospitality, industrial and institutional projects with thoughtful execution and reliable delivery.</p>
                        <div class="contact-box">
                            <div class="contact-item">
                                <i class="bi bi-geo-alt-fill"></i>
                                <div>
                                    <div class="fw-bold mb-1">Head Office</div>
                                    <div class="text-secondary">Plot 1304, No. 15, Mogadish Street, Wuse Zone 4, FCT, Abuja</div>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-telephone-fill"></i>
                                <div>
                                    <div class="fw-bold mb-1">Phone</div>
                                    <div class="text-secondary">+234 (0) 815 9894 070</div>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-envelope-fill"></i>
                                <div>
                                    <div class="fw-bold mb-1">Email</div>
                                    <div class="text-secondary">lubellnigeria@gmail.com</div>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-building"></i>
                                <div>
                                    <div class="fw-bold mb-1">Branch Office</div>
                                    <div class="text-secondary">No1, Olusegun Obasanjo Way, Beside Treasury House, Gombe</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="contact-box">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Your name</label>
                                        <input type="text" class="form-control form-control-lg" placeholder="Enter your name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Your email</label>
                                        <input type="email" class="form-control form-control-lg" placeholder="Enter your email">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Subject</label>
                                        <input type="text" class="form-control form-control-lg" placeholder="Project inquiry">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Message</label>
                                        <textarea class="form-control form-control-lg" rows="5" placeholder="Tell us about your project requirements"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-custom">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
                <div class="fw-bold text-white mb-1">Lubell Nigeria Limited</div>
                <div>© 2025 Lubell.com.ng. All rights reserved.</div>
            </div>
            <div class="d-flex gap-3">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#projects">Projects</a>
                <a href="#contact">Contact</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
