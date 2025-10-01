# WordPress Job Board Plugin - Next.js API Integration Documentation

## Обзор изменений

WordPress плагин отправляет данные заявок на Next.js API по адресу `http://192.168.1.102:3000/api/admin/applications` со следующими параметрами аутентификации:
- `api_key`: `wp_admin_key_2025`
- `user_id`: `cmg1kapf90000v32kqqp0f0f7`

## Специальные типы полей

Плагин поддерживает 5 специальных типов полей с фиксированными названиями:

1. **position** - Желаемые позиции (множественные)
2. **bildung** - Образование (множественное)
3. **berufserfahrung** - Опыт работы (множественный)
4. **sprachkenntnisse** - Языковые навыки (множественные)
5. **fuehrerschein** - Водительские права (checkbox list)

## Структура JSON данных для каждого специального поля

### 1. Position (Желаемые позиции)

**Название поля:** `position`
**Тип:** Массив объектов

```json
{
  "positions": [
    {
      "position": "Software Developer",
      "priority": 1
    },
    {
      "position": "Full Stack Developer", 
      "priority": 2
    },
    {
      "position": "DevOps Engineer",
      "priority": 3
    }
  ]
}
```

**Поля:**
- `position` (string) - Название желаемой позиции
- `priority` (integer) - Приоритет позиции (1, 2, 3)

### 2. Bildung (Образование)

**Название поля:** `bildung`
**Тип:** Массив объектов

```json
{
  "bildung": [
    {
      "institution": "Technische Universität München",
      "degree": "Bachelor of Science",
      "start_date": "2018-09-01",
      "end_date": "2022-07-31",
      "is_current": 0
    },
    {
      "institution": "Gymnasium München",
      "degree": "Abitur",
      "start_date": "2016-09-01", 
      "end_date": "2018-06-30",
      "is_current": 0
    },
    {
      "institution": "TU München",
      "degree": "Master of Science",
      "start_date": "2022-09-01",
      "end_date": "",
      "is_current": 1
    }
  ]
}
```

**Поля:**
- `institution` (string) - Название учебного заведения
- `degree` (string) - Тип/название степени или диплома  
- `start_date` (string) - Дата начала обучения (YYYY-MM-DD)
- `end_date` (string) - Дата окончания обучения (YYYY-MM-DD или пустая)
- `is_current` (integer) - Флаг текущего обучения (1 = еще учится, 0 = закончил)

### 3. Berufserfahrung (Опыт работы)

**Название поля:** `berufserfahrung`
**Тип:** Массив объектов

```json
{
  "berufserfahrung": [
    {
      "position": "Software Developer",
      "company": "Tech Solutions GmbH",
      "start_date": "2022-08-01",
      "end_date": "2024-12-31",
      "is_current": 0
    },
    {
      "position": "Junior Developer",
      "company": "StartUp Berlin",
      "start_date": "2020-06-01",
      "end_date": "2022-07-31", 
      "is_current": 0
    },
    {
      "position": "Senior Developer",
      "company": "Big Corp AG",
      "start_date": "2025-01-01",
      "end_date": "",
      "is_current": 1
    }
  ]
}
```

**Поля:**
- `position` (string) - Должность
- `company` (string) - Название компании
- `start_date` (string) - Дата начала работы (YYYY-MM-DD)
- `end_date` (string) - Дата окончания работы (YYYY-MM-DD или пустая)
- `is_current` (integer) - Флаг текущей работы (1 = работает сейчас, 0 = уволился)

### 4. Sprachkenntnisse (Языковые навыки)

**Название поля:** `sprachkenntnisse`
**Тип:** Массив объектов

```json
{
  "sprachkenntnisse": [
    {
      "language": "Deutsch",
      "level": "C2"
    },
    {
      "language": "Englisch", 
      "level": "B2"
    },
    {
      "language": "Französisch",
      "level": "A2"
    },
    {
      "language": "Russisch",
      "level": "C1"
    }
  ]
}
```

**Поля:**
- `language` (string) - Название языка
- `level` (string) - Уровень владения языком

**Доступные языки:**
- Deutsch, Englisch, Französisch, Spanisch, Italienisch, Russisch, Türkisch, Polnisch, Niederländisch, Portugiesisch, Chinesisch, Japanisch, Arabisch

**Доступные уровни:**
- A1 (Anfänger)
- A2 (Grundkenntnisse) 
- B1 (Mittlere Kenntnisse)
- B2 (Gute Kenntnisse)
- C1 (Sehr gute Kenntnisse)
- C2 (Muttersprachlich)

### 5. Führerschein (Водительские права)

**Название поля:** `fuehrerschein`
**Тип:** Массив строк (checkbox values)

```json
{
  "fuehrerschein": [
    "Klasse B (PKW)",
    "Klasse C (LKW)",
    "Klasse D (Bus)"
  ]
}
```

**Стандартные опции:**
- Klasse AM (Moped)
- Klasse A1 (Leichtkraftrad)
- Klasse A2 (Kraftrad)
- Klasse A (Schweres Kraftrad)
- Klasse B (PKW)
- Klasse BE (PKW mit Anhänger)
- Klasse C1 (Kleiner LKW)
- Klasse C (LKW)
- Klasse CE (LKW mit Anhänger)
- Klasse D1 (Kleinbusse)
- Klasse D (Bus)
- Klasse DE (Bus mit Anhänger)

## Полный пример JSON заявки

```json
{
  "template_id": 123,
  "wordpress_application_id": 456,
  "user_id": "cmg1kapf90000v32kqqp0f0f7",
  "api_key": "wp_admin_key_2025",
  "filled_data": {
    "full_name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "address": "Musterstraße 123, 80331 München",
    "cover_letter": "Sehr geehrte Damen und Herren...",
    "salary_expectation": "€55,000 - €65,000",
    "availability_type": "sofort",
    "availability_date": "2025-01-15",
    
    "positions": [
      {
        "position": "Software Developer",
        "priority": 1
      },
      {
        "position": "Full Stack Developer",
        "priority": 2
      }
    ],
    
    "bildung": [
      {
        "institution": "TU München",
        "degree": "Bachelor Informatik",
        "start_date": "2018-09-01",
        "end_date": "2022-07-31",
        "is_current": 0
      }
    ],
    
    "berufserfahrung": [
      {
        "position": "Junior Developer",
        "company": "Tech GmbH",
        "start_date": "2022-08-01",
        "end_date": "2024-12-31",
        "is_current": 0
      }
    ],
    
    "sprachkenntnisse": [
      {
        "language": "Deutsch",
        "level": "C2"
      },
      {
        "language": "Englisch",
        "level": "B2"
      }
    ],
    
    "fuehrerschein": [
      "Klasse B (PKW)",
      "Klasse BE (PKW mit Anhänger)"
    ]
  }
}
```

## GDPR Compliance

Важно! Поля, помеченные как персональные данные (`is_personal = true`) в шаблоне, НЕ отправляются на внешний API. Они сохраняются только в локальной базе данных WordPress.

Персональные данные всегда исключаются из `filled_data` перед отправкой на Next.js API.

## Заголовки HTTP запроса

```
POST /api/admin/applications
Content-Type: application/json
User-Agent: WordPress/Neo-Job-Board-Plugin
```

## Эндпоинты API

1. **Создание/обновление шаблона:**
   - URL: `http://192.168.1.102:3000/api/admin/templates` 
   - Method: POST
   - Auth: api_key + user_id

2. **Отправка заполненной заявки:**
   - URL: `http://192.168.1.102:3000/api/admin/applications`
   - Method: POST  
   - Auth: api_key + user_id

## Обновления для Next.js API

Ваш Next.js API должен быть обновлен для поддержки:

1. **Новое поле `positions`** (массив объектов с position и priority)
2. **Множественные записи** для всех специальных полей
3. **Правильная обработка** структуры данных с вложенными объектами
4. **Валидация** специальных полей согласно их схемам

Все специальные поля имеют фиксированные названия и не могут быть изменены пользователем в админке WordPress.