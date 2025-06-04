# API RESTful de Gestión de PQRS

Esta es una API RESTful desarrollada en Laravel para la gestión de Peticiones, Quejas, Reclamos y Sugerencias (PQRS). Permite a los usuarios y administradores interactuar con los diferentes módulos del sistema para crear, consultar, actualizar y eliminar PQRS, así como gestionar roles, estados, tipos de documento y respuestas.

## Tabla de Contenidos

1.  [Características](#características)
2.  [Tecnologías Utilizadas](#tecnologías-utilizadas)
3.  [Requisitos](#requisitos)
4.  [Instalación](#instalación)
5.  [Estructura del Proyecto](#estructura-del-proyecto)
6.  [Endpoints de la API](#endpoints-de-la-api)
    * [Status](#status)
    * [Role](#role)
    * [Type](#type)
    * [User](#user)
    * [PQRS](#pqrs)
    * [Answer](#answer)
7.  [Manejo de Errores](#manejo-de-errores)
8.  [Contribución](#contribución)
9.  [Licencia](#licencia)

## 1. Características

* Gestión completa de PQRS (Creación, Listado, Actualización, Eliminación Lógica).
* Manejo de usuarios con roles y tipos de documento.
* Gestión de estados para PQRS.
* Gestión de tipos de documento y roles de usuario.
* Funcionalidad de respuestas a PQRS.
* Validación robusta de datos de entrada.
* Respuestas JSON consistentes para éxito y error.
* Uso de DTOs (Data Transfer Objects) para una gestión de datos limpia.
* Implementación de Soft Deletes para algunos modelos.

## 2. Tecnologías Utilizadas

* **Laravel Framework:** `v10.x` (o la versión que estés utilizando)
* **PHP:** `^8.1` (o la versión que estés utilizando)
* **Base de Datos:** PostgreSQL (o la que hayas configurado)
* **Servidor Web:** Nginx o Apache (Laragon/Valet para desarrollo)
* **Composer:** Para gestión de dependencias de PHP.

## 3. Requisitos

Asegúrate de tener instalado lo siguiente en tu entorno de desarrollo:

* PHP `^8.1` (con extensiones como `pdo_pgsql`, `mbstring`, `openssl`, etc.)
* Composer
* Una base de datos PostgreSQL (o la que uses)
* Git

## 4. Instalación

Sigue estos pasos para poner en marcha el proyecto en tu máquina local:

1.  **Clonar el Repositorio:**
    ```bash
    git clone [https://github.com/tu-usuario/nombre-de-tu-repo.git](https://github.com/tu-usuario/nombre-de-tu-repo.git)
    cd nombre-de-tu-repo
    ```

2.  **Instalar Dependencias de Composer:**
    ```bash
    composer install
    ```

3.  **Configurar el Archivo `.env`:**
    * Copia el archivo de ejemplo `.env.example` a `.env`:
        ```bash
        cp .env.example .env
        ```
    * Abre el archivo `.env` y configura tus credenciales de base de datos y otras variables de entorno.
        ```dotenv
        APP_NAME="PQRS API"
        APP_ENV=local
        APP_KEY=
        APP_DEBUG=true
        APP_URL=http://localhost:8000 # O la URL de tu entorno de desarrollo

        DB_CONNECTION=pgsql # O mysql, sqlite
        DB_HOST=127.0.0.1
        DB_PORT=5432 # O 3306 para MySQL
        DB_DATABASE=your_database_name
        DB_USERNAME=your_db_username
        DB_PASSWORD=your_db_password
        ```

4.  **Generar la Clave de Aplicación:**
    ```bash
    php artisan key:generate
    ```

5.  **Ejecutar Migraciones de Base de Datos:**
    ```bash
    php artisan migrate
    ```
    Si deseas poblar la base de datos con datos de prueba (seeders):
    ```bash
    php artisan db:seed
    ```

6.  **Iniciar el Servidor de Desarrollo (opcional, para desarrollo local):**
    ```bash
    php artisan serve
    ```
    La API estará disponible en `http://localhost:8000`.

## 5. Estructura del Proyecto

La API sigue una arquitectura limpia y modular con los siguientes componentes clave:

* **`app/Http/Controllers/API/`**: Contiene los controladores RESTful para cada recurso (Status, Role, Type, User, Pqrs, Answer).
* **`app/Services/`**: Contiene la lógica de negocio principal para cada modelo. Los controladores delegan las operaciones complejas a estos servicios.
* **`app/DTOs/`**: Directorio para Data Transfer Objects (DTOs). Se utilizan para encapsular y validar los datos de entrada de manera estructurada, separando la validación del controlador y facilitando la inmutabilidad de los datos. Ej: `UserDTO`, `EditUserDTO`.
* **`app/Models/`**: Definición de los modelos Eloquent de la base de datos (`Status`, `Role`, `Type`, `User`, `Pqrs`, `Answer`). Algunos de estos modelos utilizan `SoftDeletes`.
* **`database/migrations/`**: Esquemas de la base de datos para cada tabla.
* **`routes/api.php`**: Definición de todas las rutas de la API, utilizando `Route::apiResource` cuando es apropiado y rutas personalizadas para acciones específicas (ej. `restore`, `forceDelete`).

## 6. Endpoints de la API

Todas las rutas están prefijadas con `/api/`. Por ejemplo, `GET /api/statuses`.

### Convenciones Generales:

* Todas las respuestas de éxito incluyen un mensaje y, si aplica, el recurso en formato JSON.
* Las validaciones devuelven un estado `422 Unprocessable Entity` con un objeto `errors` detallando los campos inválidos.
* Los recursos no encontrados devuelven `404 Not Found`.
* Errores del servidor devuelven `500 Internal Server Error` con un mensaje de error.
* Conflictos (ej. intentar eliminar un recurso ya eliminado) devuelven `409 Conflict`.

### Status

Gestión de los diferentes estados que puede tener una PQRS (ej. "Pendiente", "En Proceso", "Resuelto", "Cerrado").

| Método | Endpoint                    | Descripción                        | Body (JSON)             | Respuesta (JSON)               |
| :----- | :-------------------------- | :--------------------------------- | :---------------------- | :----------------------------- |
| `GET`  | `/api/statuses`             | Lista todos los estados.           | N/A                     | `[{id: 1, name: "Pendiente"}, ...]` |
| `POST` | `/api/statuses`             | Crea un nuevo estado.              | `{ "name": "Resuelto" }` | `{"message": "Estado creado...", "status": {...}}` |
| `PUT`  | `/api/statuses/{status}`    | Actualiza un estado existente.     | `{ "name": "Cerrado" }` | `{"message": "Estado actualizado...", "status": {...}}` |
| `DELETE` | `/api/statuses/{status}`  | Elimina lógicamente un estado.     | N/A                     | `{"message": "Estado eliminado..."}` |
| `POST` | `/api/statuses/{id}/restore`| Restaura un estado eliminado.      | N/A                     | `{"message": "Estado restaurado...", "status": {...}}` |
| `DELETE` | `/api/statuses/{id}/force-delete`| Elimina permanentemente un estado.| N/A                     | `{"message": "Estado eliminado permanentemente."}` |

### Role

Gestión de los roles de usuario (ej. "Administrador", "Usuario", "Soporte").

| Método | Endpoint              | Descripción                 | Body (JSON)             | Respuesta (JSON)               |
| :----- | :-------------------- | :-------------------------- | :---------------------- | :----------------------------- |
| `GET`  | `/api/roles`          | Lista todos los roles.      | N/A                     | `[{id: 1, name: "Administrador"}, ...]` |
| `POST` | `/api/roles`          | Crea un nuevo rol.          | `{ "name": "Usuario" }` | `{"message": "Rol creado...", "role": {...}}` |
| `PUT`  | `/api/roles/{role}`   | Actualiza un rol existente. | `{ "name": "Soporte" }` | `{"message": "Rol actualizado...", "role": {...}}` |
| `DELETE` | `/api/roles/{role}` | Elimina un rol.             | N/A                     | `{"message": "Rol eliminado..."}` |

### Type

Gestión de los tipos de documento (ej. "Cédula de Ciudadanía", "NIT", "Tarjeta de Identidad").

| Método | Endpoint              | Descripción                 | Body (JSON)             | Respuesta (JSON)               |
| :----- | :-------------------- | :-------------------------- | :---------------------- | :----------------------------- |
| `GET`  | `/api/types`          | Lista todos los tipos.      | N/A                     | `[{id: 1, name: "C.C."}, ...]` |
| `POST` | `/api/types`          | Crea un nuevo tipo.         | `{ "name": "NIT" }`     | `{"message": "Tipo creado...", "type": {...}}` |
| `PUT`  | `/api/types/{type}`   | Actualiza un tipo existente.| `{ "name": "Pasaporte" }`| `{"message": "Tipo actualizado...", "type": {...}}` |
| `DELETE` | `/api/types/{type}` | Elimina un tipo.            | N/A                     | `{"message": "Tipo eliminado..."}` |

### User

Gestión de los usuarios del sistema.

| Método | Endpoint              | Descripción                 | Body (JSON)                                                                  | Respuesta (JSON)                 |
| :----- | :-------------------- | :-------------------------- | :--------------------------------------------------------------------------- | :------------------------------- |
| `GET`  | `/api/users`          | Lista todos los usuarios.   | N/A                                                                          | `[{id: 1, name: "Juan", ...}, ...]` |
| `POST` | `/api/users`          | Crea un nuevo usuario.      | `{ "name": "...", "document_type_id": 1, "document": "...", "role_id": 1, "email": "...", "phone": "...", "status_id": 1, "password": "..." }` | `{"message": "Usuario creado...", "user": {...}}` |
| `PUT`  | `/api/users/{user}`   | Actualiza un usuario existente.| `{ "name": "...", "document_type_id": 1, "document": "...", "role_id": 1, "email": "...", "phone": "...", "status_id": 1, "password": "(opcional)" }` | `{"message": "Usuario actualizado...", "user": {...}}` |
| `DELETE` | `/api/users/{user}` | Elimina lógicamente un usuario. | N/A                                                                          | `{"message": "Usuario eliminado..."}` |

### PQRS

Gestión de las Peticiones, Quejas, Reclamos y Sugerencias.

| Método | Endpoint              | Descripción                 | Body (JSON)                                                                  | Respuesta (JSON)                 |
| :----- | :-------------------- | :-------------------------- | :--------------------------------------------------------------------------- | :------------------------------- |
| `GET`  | `/api/pqrs`           | Lista todas las PQRS.       | N/A                                                                          | `[{id: 1, subject: "...", ...}, ...]` |
| `POST` | `/api/pqrs`           | Crea una nueva PQRS.        | `{ "user_id": 1, "subject": "...", "description": "...", "type_id": 1, "status_id": 1 }` | `{"message": "PQRS creada...", "pqrs": {...}}` |
| `PUT`  | `/api/pqrs/{pqrs}`    | Actualiza una PQRS existente.| `{ "subject": "...", "description": "...", "type_id": 1, "status_id": 1 }` | `{"message": "PQRS actualizada...", "pqrs": {...}}` |
| `DELETE` | `/api/pqrs/{pqrs}`  | Elimina lógicamente una PQRS.| N/A                                                                          | `{"message": "PQRS eliminada..."}` |

### Answer

Gestión de las respuestas a las PQRS.

| Método | Endpoint              | Descripción                 | Body (JSON)                                                                  | Respuesta (JSON)                 |
| :----- | :-------------------- | :-------------------------- | :--------------------------------------------------------------------------- | :------------------------------- |
| `GET`  | `/api/answers`        | Lista todas las respuestas. | N/A                                                                          | `[{id: 1, pqrs_id: 1, ...}, ...]` |
| `POST` | `/api/answers`        | Crea una nueva respuesta.   | `{ "pqrs_id": 1, "user_id": 1, "answer_text": "..." }`                      | `{"message": "Respuesta creada...", "answer": {...}}` |
| `PUT`  | `/api/answers/{answer}`| Actualiza una respuesta existente.| `{ "answer_text": "..." }`                                                   | `{"message": "Respuesta actualizada...", "answer": {...}}` |
| `DELETE` | `/api/answers/{answer}`| Elimina lógicamente una respuesta.| N/A                                                                          | `{"message": "Respuesta eliminada..."}` |

## 7. Manejo de Errores

La API utiliza códigos de estado HTTP estándar y respuestas JSON para indicar el resultado de las operaciones.

* `200 OK`: La solicitud fue exitosa.
* `201 Created`: El recurso fue creado exitosamente.
* `204 No Content`: La solicitud fue exitosa, pero no hay contenido que devolver (ej. para algunas eliminaciones sin mensaje).
* `400 Bad Request`: La solicitud es inválida o malformada.
* `401 Unauthorized`: El usuario no está autenticado.
* `403 Forbidden`: El usuario no tiene permisos para realizar la acción.
* `404 Not Found`: El recurso solicitado no existe.
* `409 Conflict`: La solicitud no pudo ser completada debido a un conflicto con el estado actual del recurso (ej. intentar eliminar un recurso ya eliminado).
* `422 Unprocessable Entity`: La validación de los datos falló.
* `500 Internal Server Error`: Ocurrió un error inesperado en el servidor.

Ejemplo de respuesta de error de validación (`422`):

```json
{
    "message": "Los datos proporcionados no son válidos para la actualización.",
    "errors": {
        "email": [
            "El campo email ya ha sido tomado."
        ],
        "password": [
            "El campo password debe tener al menos 8 caracteres."
        ]
    }
}
```
Ejemplo de respuesta de error general (`500`):

```json
{
    "message": "Ocurrió un error al intentar crear el usuario.",
    "error": "SQLSTATE[42P01]: Undefined table: 7 ERROR: relation \"document_types\" does not exist"
}
