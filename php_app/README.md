Laravel migration scaffold (contractSama)

This folder is a scaffold for migrating the Flask app to Laravel. It contains starter files and instructions — run the full Laravel project locally using Composer.

Quick start (Windows PowerShell):

1. Install Composer if you don't have it: https://getcomposer.org/download/
2. From repository root run (creates a new laravel project in php_app):

    composer create-project laravel/laravel php_app

3. Change directory and install auth scaffold + packages:

    cd php_app
    composer require laravel/breeze --dev
    php artisan breeze:install blade
    composer require spatie/laravel-permission
    composer require guzzlehttp/guzzle
    npm install && npm run dev

4. Configure `.env` (copy from `.env.example` below). Important env values:

    DB_CONNECTION=sqlite
    DB_DATABASE=database/database.sqlite
    PDF_SERVICE_URL=http://127.0.0.1:8001

5. Run migrations and generate app key:

    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate

6. Start dev server:

    php artisan serve --host=127.0.0.1 --port=8000

Integration with Python PDF microservice:
- The ContractController includes an example `pdf()` method that uses Guzzle to call the service endpoints `/extract_positions` and `/render_overlay` then returns/merges the resulting PDF.
- Ensure the Python service is running (see `services/pdf_service/README.md`) and `PDF_SERVICE_URL` points to it.

Next steps I will take after you confirm this scaffold:
- Add auth (Breeze/Jetstream) and policies/middlewares mapping the Flask role behavior (manager/user).
- Translate `app.py` routes into Controllers and Blade templates.
- Port models to Eloquent and create migrations based on current `models.py`.
- Implement PDF pipeline integration (call the Python microservice and merge overlay using a PHP PDF library or shell tools).

If you want this scaffold in a new repo instead of `php_app/` here, tell me and I'll adapt.
