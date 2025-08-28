<?php
/**
 * Header Template für das Employee Dashboard Plugin
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/css/dashboard.css'; ?>">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/dashboard.js'; ?>" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm p-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-white" href="#">📊 Employee Dashboard</a>

            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" id="notifications-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    🔔 <span class="badge bg-danger" id="notification-count">3</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="notifications-dropdown">
                    <li><p class="dropdown-item text-muted">Keine neuen Benachrichtigungen</p></li>
                </ul>
            </div>
        </div>
    </nav>
