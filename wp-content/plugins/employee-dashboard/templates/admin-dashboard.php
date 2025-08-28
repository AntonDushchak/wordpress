<?php
/**
 * Admin Dashboard Template für das Employee Dashboard Plugin
 */
?>
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h1>📊 Employee Dashboard</h1>
    <div id="dashboard-widgets" class="row">
        <div class="col-md-4 widget" id="widget-1">
            <h3 class="drag-handle">🕒 Zeiterfassung</h3>
            <p>Arbeitszeit hier erfassen...</p>
        </div>
        <div class="col-md-4 widget" id="widget-2">
            <h3 class="drag-handle">📅 Urlaubsplanung</h3>
            <p>Verwalte deine Urlaubstage...</p>
        </div>
        <div class="col-md-4 widget" id="widget-3">
            <h3 class="drag-handle">📊 Statistik</h3>
            <p>Analysen und Berichte...</p>
        </div>
    </div>
    
    <h2>🔔 Live-Benachrichtigungen</h2>
    <div id="live-updates-container" class="notifications-box">
        <p>⚡ Lade aktuelle Benachrichtigungen...</p>
    </div>

    <h2>📁 Archivierte Benachrichtigungen</h2>
    <div id="archived-updates-container" class="notifications-box">
        <p>📂 Archivierte Benachrichtigungen werden geladen...</p>
    </div>
</div>

<?php include 'footer.php'; ?>

<style>
    .notifications-box {
        border: 1px solid #ddd;
        padding: 15px;
        background-color: #f9f9f9;
        margin-bottom: 20px;
    }
</style>
