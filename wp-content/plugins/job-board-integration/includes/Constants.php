<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class ApplicationConstants {
    const STATUS_NEW = 'new';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const HASH_LENGTH = 8;
    const HASH_MAX_ATTEMPTS = 10;
    const HASH_CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
}

class PersonalDataFields {
    const STANDARD_FIELDS = [
        'full_name', 'first_name', 'last_name', 
        'email', 'phone', 'address'
    ];
    
    const SENSITIVE_FIELDS = [
        'passport', 'id_number', 'social_security',
        'bank_account', 'credit_card', 'ssn'
    ];
}

class APIConstants {
    const TIMEOUT = 30;
    const MAX_RETRIES = 3;
    const RATE_LIMIT_WINDOW = 300;
    const RATE_LIMIT_MAX_REQUESTS = 10;
}

class DatabaseConstants {
    const TEMPLATES_TABLE = 'neo_job_board_templates';
    const APPLICATIONS_TABLE = 'neo_job_board_applications';
    const API_LOGS_TABLE = 'neo_job_board_api_logs';
}

class SecurityConstants {
    const NONCE_ACTION = 'neo_job_board_nonce';
    const RATE_LIMIT_PREFIX = 'neo_rate_limit_';
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    const SESSION_TIMEOUT = 3600;
}

class ErrorCodes {
    const VALIDATION_ERROR = 'validation_error';
    const SECURITY_ERROR = 'security_error';
    const API_ERROR = 'api_error';
    const DATABASE_ERROR = 'database_error';
    const FILE_ERROR = 'file_error';
    const PERMISSION_ERROR = 'permission_error';
}

class HTTPStatus {
    const OK = 200;
    const CREATED = 201;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const INTERNAL_SERVER_ERROR = 500;
    const SERVICE_UNAVAILABLE = 503;
}
