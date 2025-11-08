#!/bin/bash

set -euo pipefail

usage() {
  cat <<'EOF'
Usage: set_domain.sh <domain> [scheme]

<domain>  — new domain without http(s)
[scheme]  — optional parameter (http or https). By default, it takes from the current WordPress settings.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

if [[ $# -lt 1 ]]; then
  echo "Fehler: Sie müssen einen Domain übergeben." >&2
  usage
  exit 1
fi

NEW_DOMAIN="${1}"
SCHEME_INPUT="${2:-}"
WP_ROOT="${WP_ROOT:-/var/www/html}"

if [[ ! -d "${WP_ROOT}" ]]; then
  echo "Fehler: WordPress-Verzeichnis (${WP_ROOT}) nicht gefunden." >&2
  exit 1
fi

if [[ ! -f "${WP_ROOT}/wp-load.php" ]]; then
  echo "Fehler: Datei ${WP_ROOT}/wp-load.php nicht gefunden." >&2
  exit 1
fi

validate_domain() {
  local domain="$1"
  if [[ "${domain}" =~ ^[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$ ]]; then
    return 0
  fi
  return 1
}

if ! validate_domain "${NEW_DOMAIN}"; then
  echo "Fehler: Ungültiges Domain-Format: ${NEW_DOMAIN}" >&2
  exit 1
fi

normalize_scheme() {
  local scheme="$1"
  case "${scheme}" in
    http|https) echo "${scheme}" ;;
    *)
      echo "Fehler: Die Schema muss http oder https sein (erhalten: ${scheme})" >&2
      exit 1
      ;;
  esac
}

if [[ -n "${SCHEME_INPUT}" ]]; then
  SCHEME="$(normalize_scheme "${SCHEME_INPUT}")"
else
  SCHEME=""
fi

export NEW_DOMAIN SCHEME WP_ROOT

OLD_URL="$(php -r "require '${WP_ROOT}/wp-load.php'; echo get_option('home');" 2>/dev/null || true)"

php <<'PHP'
<?php
declare(strict_types=1);

$wpRoot = getenv('WP_ROOT') ?: '/var/www/html';
require_once $wpRoot . '/wp-load.php';

if (!function_exists('update_option')) {
    fwrite(STDERR, "Fehler: WordPress Funktionen sind nicht verfügbar.\n");
    exit(1);
}

$newDomain = getenv('NEW_DOMAIN');
$schemeInput = getenv('SCHEME');

$currentHome = get_option('home');
$currentSiteurl = get_option('siteurl');

if (!$schemeInput) {
    $fromHome = parse_url($currentHome, PHP_URL_SCHEME);
    $schemeInput = $fromHome ?: 'http';
}

$schemeInput = strtolower($schemeInput) === 'https' ? 'https' : 'http';
$newUrl = $schemeInput . '://' . $newDomain;

update_option('home', $newUrl);
update_option('siteurl', $newUrl);

if (function_exists('flush_rewrite_rules')) {
    do_action('init');
    flush_rewrite_rules(false);
}

echo "Domain updated to {$newUrl}\n";
PHP

WP_CLI_BIN="${WP_CLI_BIN:-/usr/local/bin/wp}"
if [[ -x "${WP_CLI_BIN}" ]]; then
  OLD_HOST="$(php -r "echo parse_url('${OLD_URL}', PHP_URL_HOST);" 2>/dev/null || true)"
  if [[ -n "${OLD_HOST}" && "${OLD_HOST}" != "${NEW_DOMAIN}" ]]; then
    "${WP_CLI_BIN}" --path="${WP_ROOT}" search-replace "${OLD_HOST}" "${NEW_DOMAIN}" --skip-columns=guid --all-tables --allow-root || true
  fi
fi

echo "Fertig."

