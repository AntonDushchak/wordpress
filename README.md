# Neo Dashboard Framework

Современный фреймворк для создания WordPress плагинов с красивым дашбордом.

## 🚀 Быстрый старт

### Требования
- Docker Desktop
- Git

### Установка
```bash
# Клонируйте репозиторий
git clone https://github.com/your-username/neo-dashboard-framework.git
cd neo-dashboard-framework

# Запустите через Docker
./start-docker.bat          # Windows
./start-docker.sh           # Linux/Mac

# Или вручную
docker-compose up -d
```

### Доступ
- **WordPress**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Данные для входа**: root / root_password

## 📁 Структура проекта

```
├── docker-compose.yml          # Docker конфигурация
├── start-docker.bat           # Скрипт запуска для Windows
├── start-docker.sh            # Скрипт запуска для Linux/Mac
├── .dockerignore              # Исключения для Docker
├── .gitignore                 # Исключения для Git
├── wp-content/
│   └── plugins/
│       ├── neo-dashboard/           # Основной плагин
│       ├── neo-dashboard-examples/  # Примеры использования
│       └── plugin-template/         # Шаблон для новых плагинов
└── README.md                  # Этот файл
```

## 🔌 Плагины

### Neo Dashboard Core
Основной плагин, предоставляющий:
- Систему управления секциями
- Виджеты
- Боковую панель
- Уведомления
- AJAX обработчики

### Neo Dashboard Examples
Примеры использования фреймворка:
- Демо секции
- Виджеты
- Уведомления

### Plugin Template
Шаблон для создания новых плагинов на основе Neo Dashboard.

## 🛠️ Создание нового плагина

1. Скопируйте `plugin-template` в новую папку
2. Измените заголовки в главном файле
3. Добавьте свои секции, виджеты, уведомления
4. Используйте хуки Neo Dashboard для интеграции

## 🎨 Особенности

- **Bootstrap 5** для современного дизайна
- **Адаптивный интерфейс** для всех устройств
- **Система хуков** для расширения функциональности
- **REST API** для интеграции
- **AJAX** для динамических обновлений

## 📚 Документация

Подробная документация по использованию фреймворка находится в папке `docs/`.

## 🤝 Вклад в проект

1. Форкните репозиторий
2. Создайте ветку для новой функции
3. Внесите изменения
4. Создайте Pull Request

## 📄 Лицензия

MIT License - см. файл LICENSE для деталей.

## 🆘 Поддержка

Если у вас есть вопросы или проблемы:
- Создайте Issue в GitHub
- Опишите проблему подробно
- Приложите логи и скриншоты

---

**Создано с ❤️ для WordPress сообщества**
