<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Enable & Document Management | Construction ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid py-4 px-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Project Enable Document Management</h2>
            <p class="text-muted mb-0">Statutory clearances, engineering drawings, and site mobilization documentation.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-cloud-arrow-up me-1"></i>Upload Document
        </button>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash']['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Upload Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No documents found for this project.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($doc['title']) ?></td>
                                <td><?= htmlspecialchars($doc['project_name'] ?? 'General') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($doc['category']) ?></span></td>
                                <td><?= htmlspecialchars($doc['created_at']) ?></td>
                                <td class="text-end">
                                    <a href="/projects/enable/view/<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info">View</a>
                                    <a href="/projects/enable/download/<?= (int)$doc['id'] ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                    <form method="post" action="/projects/enable/delete/<?= (int)$doc['id'] ?>" class="d-inline" onsubmit="return confirm('Delete this document?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
