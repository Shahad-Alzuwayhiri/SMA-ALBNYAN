# Laravel Migration Testing

## Setup Instructions

### 1. Install Dependencies
```bash
cd php_app
composer install
npm install && npm run dev
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 4. Start Services

#### Start Python PDF Service
```bash
# From project root
cd services/pdf_service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001
```

#### Start Laravel Development Server
```bash
# From php_app directory
php artisan serve --host=127.0.0.1 --port=8000
```

### 5. Test the Application

Visit: http://127.0.0.1:8000

## Current Migration Status

✅ **Completed:**
- Laravel scaffold with routes and controllers
- Blade templates (layouts, partials, contract views)
- Static assets copied to public/static/
- User and Contract models with migrations
- PdfService for hybrid Python integration
- Git branch `feature/php-migration` with all changes

🔄 **In Progress:**
- Database models and migrations
- Authentication setup
- PDF generation testing

⏳ **Next Steps:**
- Set up Laravel Breeze for authentication
- Add role-based permissions (spatie/laravel-permission)
- Test PDF generation with sample data
- Migrate existing SQLite data

## Testing PDF Generation

1. Ensure Python PDF service is running on port 8001
2. Place a design PDF at `php_app/storage/app/designs/contract_design.pdf`
3. Create a test contract via the web interface
4. Click "Download PDF" to test the hybrid integration

## File Structure
```
php_app/
├── app/
│   ├── Http/Controllers/ContractController.php
│   ├── Models/User.php
│   ├── Models/Contract.php
│   └── Services/PdfService.php
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── partials/topnav.blade.php
│   ├── partials/sidebar.blade.php
│   └── contracts/
├── public/static/ (copied from original static/)
└── database/migrations/
```