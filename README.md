# API de Inventario del Restaurante

Este microservicio proporciona una API RESTful para gestionar el inventario de ingredientes del restaurante y el historial de compras.

## Endpoints

La API expone los siguientes endpoints:

* **`GET /api/v1/inventory`**:
    * Devuelve un listado de todos los ingredientes disponibles y sus cantidades actuales.
    * Ejemplo de respuesta:
        ```json
        [
            {
                "ingredient": "tomato",
                "quantity": 5,
                "created_at": "...",
                "updated_at": "..."
            },
            {
                "ingredient": "lemon",
                "quantity": 5,
                "created_at": "...",
                "updated_at": "..."
            },
            // ... otros ingredientes
        ]
        ```
* **`GET /api/v1/inventory/{ingredient}`**:
    * Devuelve la información detallada de un ingrediente específico.
    * Ejemplo de respuesta:
        ```json
        {
            "ingredient": "tomato",
            "quantity": 5,
            "created_at": "...",
            "updated_at": "..."
        }
        ```
* **`POST /api/v1/inventory/request`**:
    * Recibe una solicitud de ingredientes de la cocina.
    * Verifica la disponibilidad de los ingredientes y descuenta las cantidades si están disponibles.
    * Si algún ingrediente no tiene suficiente cantidad, se crea un trabajo en segundo plano para comprarlo en la plaza de mercado (API de farmers-market de alegra.com).
    * Ejemplo de solicitud:
        ```json
        {
            "ingredients": [
                {
                    "name": "tomato",
                    "quantity": 1
                },
                {
                    "name": "lemon",
                    "quantity": 1
                }
            ]
        }
        ```
    * Ejemplo de respuesta (éxito):
        ```json
        {
            "data": {
                "message": "Ingredientes solicitados procesados"
            },
            "message": "Peticion exitosa, todo salio bien!",
            "success": true
        }
        ```
* **`GET /api/v1/purchases`**:
    * Devuelve un listado de todas las compras realizadas en la plaza de mercado.
    * Ejemplo de respuesta:
        ```json
        [
            {
                "id": 1,
                "ingredient": "tomato",
                "quantity": 10,
                "created_at": "...",
                "updated_at": "..."
            },
            // ... otras compras
        ]
        ```

## Manejo de Solicitudes de Ingredientes

El endpoint `/api/v1/inventory/request` maneja las solicitudes de ingredientes de la siguiente manera:

1.  Recibe un arreglo de ingredientes y cantidades solicitadas.
2.  Valida que los ingredientes existan en el inventario y que las cantidades solicitadas estén disponibles.
3.  Si todos los ingredientes están disponibles, descuenta las cantidades del inventario y devuelve una respuesta de éxito.
4.  Si algún ingrediente no está disponible en la cantidad solicitada:
    * Rechaza la petición.
    * Crea un trabajo en segundo plano (`BuyIngredientJob`) para comprar el ingrediente faltante en la plaza de mercado utilizando la API de farmers-market de alegra.com (`https://recruitment.alegra.com/api/farmers-market/buy`).
    * El trabajo en segundo plano actualiza el inventario una vez que la compra se realiza con éxito.

## Integración con la API de la Plaza de Mercado

El microservicio se integra con la API de la plaza de mercado para comprar ingredientes faltantes.

* La API de la plaza de mercado se encuentra en `https://recruitment.alegra.com/api/farmers-market/buy`.
* Recibe el nombre del ingrediente como parámetro (`ingredient`).
* Devuelve la cantidad comprada (`quantitySold`) en formato JSON.
* Si el ingrediente no está disponible, `quantitySold` será 0.

## Configuración y Ejecución con Docker

Para ejecutar este microservicio utilizando Docker, sigue estos pasos:

1.  **Copia el archivo `.env.example` a `.env`:**
    * `cp .env.example .env`
    * Asegúrate de configurar las variables de entorno en el archivo `.env` (especialmente las de la base de datos).

2.  **Levanta los contenedores con Docker Compose:**
    * `docker-compose up --build -d`
    * Este comando construirá las imágenes de Docker y levantará los contenedores en modo "detached" (en segundo plano).

3.  **Ejecuta las migraciones y semillas en el contenedor `api`:**
    * `docker-compose exec api php artisan migrate --seed`
    * Este comando ejecutará las migraciones de la base de datos y los seeders para poblar la base de datos con datos iniciales.

4.  **Genera la clave de la aplicación Laravel:**
    * `docker-compose exec api php artisan key:generate`
    * Este comando generará una clave de aplicación única para tu instalación de Laravel.

5.  **Accede a la API:**
    * La API estará disponible en `http://localhost:8003/api/v1/`.

## Tecnologías Utilizadas

* PHP 8.1+
* Laravel 10
* MySQL

## Docker Compose

El archivo `docker-compose.yml` define los siguientes servicios:

* **`api`**: El contenedor de la aplicación PHP (Laravel).
* **`webserver`**: El contenedor del servidor web Nginx.
* **`database`**: El contenedor del servidor de base de datos MySQL.

## Tests

Este microservicio incluye tests unitarios para verificar el correcto funcionamiento de los endpoints. Los tests cubren los siguientes escenarios:

* **`test_index_success_with_default_parameters`**: Verifica que el endpoint `/api/v1/inventory` devuelve una lista de ingredientes con la estructura correcta y un código de estado 200.
* **`test_index_success_with_custom_parameters`**: Verifica que el endpoint `/api/v1/inventory` acepta parámetros personalizados (como `take`) y devuelve la cantidad correcta de ingredientes.
* **`test_show_success`**: Verifica que el endpoint `/api/v1/inventory/{ingredient}` devuelve la información correcta del ingrediente con la estructura correcta y un código de estado 200.
* **`test_show_inventory_not_found`**: Verifica que el endpoint `/api/v1/inventory/{ingredient}` devuelve un código de estado 404 cuando se solicita un ingrediente que no existe.
* **`test_request_success`**: Verifica que el endpoint `/api/v1/inventory/request` procesa correctamente una solicitud de ingredientes cuando hay suficiente cantidad disponible.
* **`test_request_insufficient_quantity`**: Verifica que el endpoint `/api/v1/inventory/request` crea un trabajo en segundo plano para comprar ingredientes cuando no hay suficiente cantidad disponible.
* **`test_request_ingredient_not_found`**: Verifica que el endpoint `/api/v1/inventory/request` devuelve un error cuando se solicita un ingrediente que no existe.
* **`test_request_validation_errors`**: Verifica que el endpoint `/api/v1/inventory/request` devuelve errores de validación cuando los datos de la solicitud son inválidos.
* **`test_index_success_with_default_parameters` (Purchases)**: Verifica que el endpoint `/api/v1/purchases` devuelve una lista de compras con la estructura correcta y un código de estado 200.
* **`test_index_success_with_custom_parameters` (Purchases)**: Verifica que el endpoint `/api/v1/purchases` acepta parámetros personalizados (como `take`) y devuelve la cantidad correcta de compras.

Para ejecutar los tests, puedes utilizar el siguiente comando dentro del contenedor `api`:

```bash
docker-compose exec api php artisan test