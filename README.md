# API de Gestión de Tareas Personales

Esta es una API REST desarrollada en Laravel 9+ para gestionar tareas personales, con autenticación via Sanctum y integraciones externas con OpenWeatherMap (clima) y Brevo (emails).

## Requisitos
- PHP 8+
- Composer
- MySQL
- API Keys: OpenWeatherMap y Brevo
- Git

## Instrucciones de Despliegue
1. Clona el repositorio:
   ```bash
   git clone https://github.com/julian-medina-98/task-manager-api.git
   cd task-manager-api
   ```
2. Instala dependencias:
   ```bash
   composer install
   ```
3. Copia `.env.example` a `.env` y configura las siguientes variables:
   ```env
   APP_NAME=TaskManagerApi
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=task_manager
   DB_USERNAME=root
   DB_PASSWORD=

   OPENWEATHERMAP_API_KEY=tu_key
   BREVO_API_KEY=tu_key
   ```
4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```
5. Migra la base de datos:
   ```bash
   php artisan migrate
   ```
6. Inicia el servidor:
   ```bash
   php artisan serve
   ```
7. Accede a la API en `http://127.0.0.1:8000/api/`.

## Endpoints

### Autenticación
- POST `/api/register` - Body: {name, email, password} - Registra usuario.
- POST `/api/login` - Body: {email, password} - Inicia sesión, retorna token.
- POST `/api/logout` - Cierra sesión (requiere token).
- GET `/api/profile` - Datos del usuario (requiere token).

### Tareas (requiere token en header: Authorization: Bearer {token})
- GET `/api/tasks` - Lista tareas paginadas.
- POST `/api/tasks` - Body: {title, description?, status, due_date?} - Crea tarea. Envía email si due_date (Brevo).
- GET `/api/tasks/{id}` - Obtiene tarea.
- PUT `/api/tasks/{id}` - Actualiza tarea.
- DELETE `/api/tasks/{id}` - Elimina tarea.

### Integraciones
- GET `/api/tasks/{id}/weather` - Clima para due_date (Opción A, OpenWeatherMap).
- POST `/api/tasks/{id}/send-reminder` - Envía recordatorio por email (Opción B, Brevo).

## Validaciones
- **Usuarios**: Email único, password ≥8 con letras/números, name ≤100 caracteres.
- **Tareas**: Title ≤200, description ≤1000, due_date ≥ hoy (2025-08-13), status en ['pending', 'in_progress', 'completed'].

## Configuración de Emails (Brevo)
- Regístrate en Brevo y obtén una API key.
- Agrega `BREVO_API_KEY=tu_key` en `.env`.
- Al crear una tarea con `due_date`, se envía un email automáticamente.
- Verifica envíos en el dashboard de Brevo.

## Configuración de Clima (OpenWeatherMap)
- Regístrate en OpenWeatherMap y obtén una API key.
- Agrega `OPENWEATHERMAP_API_KEY=tu_key` en `.env`.

## Pruebas
1. Usa Postman/Insomnia para probar endpoints.
2. Registra usuario (POST `/api/register`), inicia sesión (POST `/api/login`), y guarda el token.
3. Prueba tareas:
   - Crea tarea con `due_date` (POST `/api/tasks`) y verifica email en Brevo.
   - Lista tareas (GET `/api/tasks`), actualiza (PUT `/api/tasks/{id}`), elimina (DELETE `/api/tasks/{id}`).
   - Obtén clima (GET `/api/tasks/{id}/weather`) y verifica datos de OpenWeatherMap.
   - Envía recordatorio manual (**POST** `/api/tasks/{id}/send-reminder`) y verifica email en Brevo.
4. Prueba errores: campos inválidos (422), sin token (401), ID no existente (404).
5. Nota: Usa **POST** para `/api/tasks/{id}/send-reminder`.
6. Verifica logs (`storage/logs/laravel.log`) si hay problemas.
