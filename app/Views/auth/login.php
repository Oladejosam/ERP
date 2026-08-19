<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'ERP Login'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #07111f 0%, #17408a 100%);
            font-family: "Segoe UI", Arial, sans-serif;
        }  

        .auth-shell {
            max-width: 1100px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .hero-panel {
            background: linear-gradient(145deg, #0f172a 10%, #1e3a8a 100%);
        }

        .form-control {
            border-radius: 12px;
            padding: 0.8rem 0.95rem;
        }

        .btn-primary {
            border-radius: 12px;
            padding: 0.8rem 1rem;
        }
        .chatbot-float {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            z-index: 1050;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .chatbot-float:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
        }
        .chatbot-panel {
            position: fixed;
            right: 24px;
            bottom: 96px;
            width: min(360px, calc(100vw - 32px));
            z-index: 1050;
            display: none;
        }
        .chatbot-panel.show {
            display: block;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="auth-shell row g-0 bg-white w-100">
        <div class="hero-panel col-lg-6 p-5 text-white d-flex flex-column justify-content-between">
            <div>
                <span class="badge bg-light text-primary mb-3">Secure Access</span>
                <h2 class="fw-bold">Construction ERP</h2>
                <p class="mt-3 mb-0 opacity-75">Manage procurement, payroll, inventory and finance from one secure portal.</p>
            </div>
            <div class="mt-4">
                <small class="opacity-75">Need help signing in? Contact your system administrator.</small>
            </div>
        </div>
        <div class="col-lg-6 p-4 p-lg-5">
            <div class="text-center mb-4">
                <h3 class="fw-bold">Welcome Back</h3>
                <p class="text-muted mb-0">Sign in to continue to your workspace</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/ERP/public/login" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($emailValue ?? ''); ?>" autocomplete="email" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="passwordField" name="password" autocomplete="current-password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" id="forgotPasswordLink" class="small text-primary">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
    </div>
</div>
<button type="button" class="btn btn-primary shadow-lg chatbot-float d-flex align-items-center justify-content-center" id="chatbotToggle" aria-label="Open ERP assistant">
    <i class="bi bi-chat-dots-fill fs-4"></i>
</button>
<div class="chatbot-panel bg-white rounded-4 shadow-lg border" id="chatbotPanel">
    <div class="d-flex align-items-center justify-content-between border-bottom px-3 py-2">
        <div>
            <h6 class="mb-0">ERP Assistant</h6>
            <small class="text-muted">Ask me about the system</small>
        </div>
        <button type="button" class="btn btn-sm btn-light" id="chatbotClose" aria-label="Close assistant">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="p-3">
        <div class="alert alert-info mb-3 py-2 px-3 mb-3">
            Need help signing in or choosing the right module? I can help you get started.
        </div>
        <div class="d-grid gap-2">
            <a href="/ERP/public/login" class="btn btn-outline-primary btn-sm">Refresh login</a>
            <a href="/ERP/public/" class="btn btn-outline-secondary btn-sm">Open dashboard</a>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('passwordField');

    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', () => {
            const isHidden = passwordField.type === 'password';
            passwordField.type = isHidden ? 'text' : 'password';
            togglePassword.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }

    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotPanel = document.getElementById('chatbotPanel');

    if (chatbotToggle && chatbotPanel) {
        chatbotToggle.addEventListener('click', () => {
            chatbotPanel.classList.toggle('show');
        });
    }

    if (chatbotClose && chatbotPanel) {
        chatbotClose.addEventListener('click', () => {
            chatbotPanel.classList.remove('show');
        });
    }

    const forgotLink = document.getElementById('forgotPasswordLink');
    if (forgotLink) {
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            alert('If you have forgotten your password, please contact your system administrator to reset your account.');
        });
    }
</script>
</body>
</html>
