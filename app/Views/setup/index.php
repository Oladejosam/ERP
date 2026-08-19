<?php
$companyName = (string)($settings['company_name'] ?? '');
$themeColor = (string)($settings['theme_color'] ?? '#1d4ed8');
$logoPath = trim((string)($settings['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' ? BASE_URL . '/uploads/' . ltrim($logoPath, '/') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Company Setup'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --theme-color: <?php echo htmlspecialchars($themeColor); ?>; }
        body { background: #f4f7fb; }
        .setup-card { max-width: 760px; border-top: 5px solid var(--theme-color); }
        .logo-preview { max-height: 72px; max-width: 240px; object-fit: contain; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="setup-card card shadow-sm border-0 mx-auto">
        <div class="card-body p-4 p-lg-5">
            <div class="mb-4">
                <p class="text-uppercase text-muted small fw-semibold mb-2">System Setup</p>
                <h1 class="h3 mb-2"><?php echo $isRegistered ? 'Company Settings' : 'Register Your Company'; ?></h1>
                <p class="text-muted mb-0">Set the company identity used across the ERP workspace.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label" for="company_name">Company name</label>
                    <input class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($companyName); ?>" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="company_logo">Company logo</label>
                    <?php if ($logoUrl !== ''): ?>
                        <div class="mb-2"><img class="logo-preview" src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Current company logo"></div>
                    <?php endif; ?>
                    <input class="form-control" type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, PNG, or WEBP. Maximum 2 MB.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="theme_color">System theme color</label>
                    <div class="d-flex align-items-center gap-3">
                        <input class="form-control form-control-color" type="color" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($themeColor); ?>" title="Choose theme color">
                        <span class="text-muted small">This color is used for the main navigation bar and setup accents.</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <?php if ($isRegistered): ?>
                        <a class="btn btn-outline-secondary" href="/ERP/public/">Cancel</a>
                    <?php else: ?>
                        <a class="btn btn-outline-secondary" href="/ERP/public/login">Login</a>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit">Save Company Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
