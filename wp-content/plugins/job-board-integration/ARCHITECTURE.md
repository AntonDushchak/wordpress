# Архитектура Neo Job Board (Улучшенная версия)

## Структура классов

```
NeoJobBoard/
├── AJAXV2.php (Главный роутер)
├── SecurityValidator.php (Проверка безопасности)
├── DataSanitizer.php (Санитизация данных)
├── FileValidator.php (Валидация файлов)
├── ErrorHandler.php (Обработка ошибок)
├── Constants.php (Константы)
├── URLBuilder.php (Построение URL)
├── PersonalDataManager.php (GDPR compliance)
├── SettingsCache.php (Кеширование)
├── APIClientV2.php (API клиент)
├── Services/
│   ├── TemplateService.php (Работа с шаблонами)
│   └── ApplicationService.php (Работа с заявками)
└── Database.php (Работа с БД)
```

## Поток данных

### 1. AJAX запрос
```
Frontend → AJAXV2 → SecurityValidator → Service → Database/API
```

### 2. Безопасность
```
Request → SecurityValidator.verify_ajax_security()
├── verify_nonce()
├── verify_admin_access()
└── check_rate_limit()
```

### 3. Обработка данных
```
Raw Data → DataSanitizer.sanitize_*() → Service → Database
```

### 4. API взаимодействие
```
Service → APIClientV2 → URLBuilder → External API
```

### 5. Обработка ошибок
```
Exception → ErrorHandler.handle_*_error() → Log → Response
```

## Принципы безопасности

### 1. Defense in Depth
- Nonce проверка
- Capability проверка
- Rate limiting
- Input validation
- Output sanitization

### 2. Least Privilege
- Разные уровни доступа для разных операций
- Минимальные необходимые права

### 3. Fail Secure
- При ошибках - отказ в доступе
- Логирование всех действий
- Graceful degradation

## GDPR Compliance

### 1. Data Minimization
- Отправка в API только не-персональных данных
- Фильтрация персональных полей

### 2. Right to be Forgotten
- Анонимизация данных
- Удаление по запросу

### 3. Data Portability
- Экспорт персональных данных
- Стандартные форматы

### 4. Transparency
- Логирование всех операций с персональными данными
- Уведомления о обработке

## Кеширование

### 1. Settings Cache
- Настройки API
- Конфигурация плагина

### 2. Data Cache
- Шаблоны
- Заявки
- Активные шаблоны

### 3. Cache Invalidation
- Автоматическая очистка при изменениях
- TTL для временных данных

## Мониторинг

### 1. API Logs
- Все запросы к внешнему API
- Статус ответов
- Время выполнения

### 2. Personal Data Logs
- Доступ к персональным данным
- Операции изменения/удаления
- IP адреса и User-Agent

### 3. Error Logs
- Все ошибки приложения
- Stack traces
- Контекст ошибок

## Производительность

### 1. Database Optimization
- Индексы на часто используемые поля
- Prepared statements
- Batch operations

### 2. Caching Strategy
- In-memory cache для настроек
- TTL-based cache для данных
- Lazy loading

### 3. API Optimization
- Connection pooling
- Timeout handling
- Retry mechanism

## Расширяемость

### 1. Service Layer
- Легко добавлять новые сервисы
- Единый интерфейс
- Dependency injection

### 2. Plugin Architecture
- Хуки для расширений
- Фильтры для кастомизации
- Actions для интеграций

### 3. API Versioning
- Поддержка разных версий API
- Backward compatibility
- Graceful degradation
