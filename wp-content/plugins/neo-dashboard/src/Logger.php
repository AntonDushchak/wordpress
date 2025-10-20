<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use Exception;

class Logger
{
    /**
     * Schreibt eine Logzeile mit Level, Nachricht und optionalen Daten.
     */
    public static function log(string $message, array $data = [], string $level = 'INFO'): void
    {
        try {
            $timestamp = current_time('Y-m-d H:i:s');
            $logPath   = self::get_log_path();
            $logDir    = dirname($logPath);

            // Prüfe ob die uploads Direktorie existiert und erstelle sie bei Bedarf
            if (!file_exists($logDir)) {
                if (!wp_mkdir_p($logDir)) {
                    // Fallback: Wenn uploads nicht erstellt werden kann, keine Logs schreiben
                    return;
                }
            }

            // Prüfe ob die Direktorie beschreibbar ist
            if (!is_writable($logDir)) {
                return;
            }

            $line = sprintf(
                "[%s] [%s] %s | Data: %s\n",
                $timestamp,
                strtoupper($level),
                $message,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );

            // Sichere Datei schreibung mit error handling
            $result = @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
            
            // Wenn das Schreiben fehlschlägt, mache nichts (keine weitere Fehlerausgabe)
            if ($result === false) {
                return;
            }
        } catch (Exception $e) {
            // Bei jedem Fehler einfach nichts tun (keine Logs ausgeben)
            return;
        }
    }

    /**
     * Convenience-Methode für Warnungen.
     */
    public static function warn(string $message, array $data = []): void
    {
        self::log($message, $data, 'WARNUNG');
    }

    /**
     * Convenience-Methode für Fehler.
     */
    public static function error(string $message, array $data = []): void
    {
        self::log($message, $data, 'ERROR');
    }

    /**
     * Convenience-Methode für Informationen.
     */
    public static function info(string $message, array $data = []): void
    {
        self::log($message, $data, 'INFO');
    }

    /**
     * Gibt den vollständigen Pfad zur Logdatei zurück.
     */
    public static function get_log_path(): string
    {
        // Fallback für lokale Entwicklung - verwende das plugin Verzeichnis
        $uploads_dir = WP_CONTENT_DIR . '/uploads';
        
        // Wenn uploads Verzeichnis nicht existiert, verwende plugin Verzeichnis
        if (!file_exists($uploads_dir) || !is_writable($uploads_dir)) {
            return plugin_dir_path(__FILE__) . '../logs/neo-dashboard.log';
        }
        
        return $uploads_dir . '/neo-dashboard.log';
    }

    /**
     * Löscht (leert) die Logdatei, behält aber die Datei.
     *
     * @return bool true bei Erfolg, false bei Fehler.
     */
    public static function clear(): bool
    {
        $path = self::get_log_path();
        if (file_exists($path)) {
            // Datei leeren, behalte die Datei erhalten
            $result = @file_put_contents($path, '', LOCK_EX);
            return $result !== false;
        }
        return false;
    }
}
