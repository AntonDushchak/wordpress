<?php
/**
 * Standalone cron script for Job Board Integration sync
 * 
 * This script should NOT be used directly.
 * Instead, configure system cron to call wp-cron.php:
 * 
 * */1 * * * * curl -s http://localhost:8080/wp-cron.php > /dev/null 2>&1
 * 
 * Or on Windows, use Task Scheduler to call:
 * curl http://localhost:8080/wp-cron.php
 * 
 * WordPress Cron will automatically run scheduled tasks based on intervals.
 */

require_once __DIR__ . '/../../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

error_log('JBI Sync: This script is deprecated. Use wp-cron.php instead via system cron.');
