@echo off
REM This script calls WordPress cron which runs scheduled tasks
REM Configure Task Scheduler to run this every minute: */1 * * * *

curl -s http://localhost:8080/wp-cron.php > nul 2>&1
