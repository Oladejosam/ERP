<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Assistant | Construction ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-primary text-white p-3">
            <h5 class="mb-0"><i class="bi bi-robot me-2"></i>Construction ERP Operational Assistant</h5>
        </div>
        <div class="card-body p-4" id="chatArea" style="height: 480px; overflow-y: auto;">
            <div class="alert alert-info">Hello! Ask me questions regarding ongoing projects, low-stock inventory, or pending material requisitions.</div>
        </div>
        <div class="card-footer bg-white p-3">
            <form id="chatForm" class="input-group">
                <input type="text" id="chatMsg" class="form-control" placeholder="Ask about projects, materials, requisitions..." required>
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
