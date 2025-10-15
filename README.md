# MHRPCI Backend

A modern full-stack web application built with Laravel 12 and Vue 3, designed for managing medical supplies, equipment, blogs, and site information with a comprehensive API.

## 🚀 Features

### Core Functionality
- **Product Management**: Manage medical supplies and equipment with images, features, and categorization
- **Blog System**: Create, read, update, and delete blog posts with image support
- **User Management**: Role-based access control (System Admin, Admin, Staff)
- **Site Information**: Manage contact details, social media links, and company information
- **Site Settings**: Configure site name, logo, and branding
- **Database Backup & Restore**: Automated backup system with download and upload capabilities
- **Dashboard**: Real-time statistics, activity tracking, and system overview

### API Features
- **RESTful API**: Public API endpoints for blogs, products, and site information
- **Rate Limiting**: Intelligent throttling for different endpoint types
- **API Caching**: Automatic cache invalidation on data changes
- **CORS Support**: Ready for external integrations

### Authentication & Security
- **Laravel Fortify**: Secure authentication system
- **Laravel Sanctum**: API token authentication
- **Two-Factor Authentication**: Enhanced security for user accounts
- **Role-Based Permissions**: System Admin, Admin, and Staff roles

## 🛠 Tech Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.3+)
- **Authentication**: Laravel Fortify + Laravel Sanctum
- **Database**: MySQL 8.0
- **Cache**: Redis with automatic invalidation
- **Queue System**: Database queue driver

### Frontend
- **Framework**: Vue 3 with TypeScript
- **UI Library**: Reka UI (headless components)
- **Styling**: Tailwind CSS v4
- **Icons**: Lucide Vue Next
- **State Management**: VueUse composables
- **Build Tool**: Vite 7
- **Server-Side Rendering**: Inertia.js SSR support

### DevOps
- **Containerization**: Docker with multi-stage builds
- **Web Server**: Nginx with custom configuration
- **Process Manager**: Supervisor for scheduler
- **Testing**: Pest PHP for unit and feature tests
- **Code Quality**: Laravel Pint, ESLint, Prettier

## 📋 Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0
- Redis (for caching)
- Docker & Docker Compose (for containerized deployment)

## 🔧 Installation

### Local Development Setup

#### 1. Clone the Repository
```bash
git clone <repository-url>
cd mhrpci-backend
```

#### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Configure Environment
Edit `.env` file and configure:
```env
APP_NAME="MHRPCI Admin"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mhrpci
DB_USERNAME=root
DB_PASSWORD=

# For Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### 5. Run Migrations
```bash
php artisan migrate
```

#### 6. Seed Database (Optional)
```bash
php artisan db:seed
```

#### 7. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

#### 8. Run the Application
```bash
# Run all services concurrently (server, queue, vite)
composer dev

# Or run separately:
php artisan serve
php artisan queue:work
npm run dev
```

### Docker Deployment Setup

#### 1. Clone the Repository
```bash
git clone <repository-url>
cd mhrpci-backend
```

#### 2. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Update .env for Docker
```

Edit `.env` for Docker configuration:
```env
APP_NAME="MHRPCI Admin"
APP_URL=http://localhost:4003

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=mhrpci
DB_USERNAME=mhrpci_user
DB_PASSWORD=mhrpci-admin@2025

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

#### 3. Build and Start Docker Containers
```bash
docker-compose up -d --build
```

#### 4. Initial Setup
```bash
# Enter the app container
docker-compose exec app bash

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Exit container
exit
```

#### 5. Access the Application
- **Application**: http://localhost:4003
- **PHPMyAdmin**: http://localhost:4004
- **MySQL**: localhost:3037
- **Redis**: localhost:6382

For detailed Docker setup instructions, see [Docker Setup Guide](documentations/DOCKER_SETUP.md).

## 📁 Project Structure

```
mhrpci-backend/
├── app/
│   ├── Http/Controllers/    # API and web controllers
│   ├── Models/              # Eloquent models
│   ├── Middleware/          # Custom middleware
│   ├── ProductType.php      # Product type enum
│   └── UserRole.php         # User role enum
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Database seeders
│   └── factories/          # Model factories
├── resources/
│   ├── js/
│   │   ├── components/     # Vue components
│   │   ├── layouts/        # Page layouts
│   │   ├── pages/          # Inertia pages
│   │   ├── composables/    # Vue composables
│   │   └── types/          # TypeScript types
│   └── css/                # Stylesheets
├── routes/
│   ├── web.php             # Web routes
│   ├── api.php             # API routes
│   ├── auth.php            # Authentication routes
│   └── settings.php        # Settings routes
├── docker/                 # Docker configuration
│   ├── nginx/              # Nginx configuration
│   └── supervisor/         # Supervisor configuration
├── documentations/         # Feature documentation
└── tests/                  # Test files
```

## 🔑 User Roles

### System Admin
- Full system access
- Manage site settings (logo, name)
- Reset system configurations
- All admin and staff permissions

### Admin
- Manage database backups
- Manage site information
- Manage users, products, and blogs
- All staff permissions

### Staff
- View and manage assigned content
- Create and edit products and blogs
- Basic CRUD operations

## 📡 API Endpoints

### Public API (v1)

#### Blogs
- `GET /api/v1/blogs` - List all blogs (paginated)
- `GET /api/v1/blogs/latest?limit=10` - Get latest blogs
- `GET /api/v1/blogs/{id}` - Get single blog
- `GET /api/v1/blogs/{id}/related` - Get related blogs

#### Products
- `GET /api/v1/products` - List all products (paginated)
- `GET /api/v1/products/latest?limit=10` - Get latest products
- `GET /api/v1/products/{id}` - Get single product

#### Site Information
- `GET /api/v1/contacts/email` - Get email
- `GET /api/v1/contacts/tel` - Get telephone
- `GET /api/v1/contacts/phone` - Get phone
- `GET /api/v1/contacts/address` - Get address
- `GET /api/v1/contacts/all` - Get all contact info
- `GET /api/v1/social/{platform}` - Get social media links

#### Site Settings
- `GET /api/site-settings` - Get site name and logo

### Protected API (Dashboard)
- `GET /api/dashboard/stats` - Dashboard statistics
- `GET /api/dashboard/overview` - System overview
- `GET /api/dashboard/recent-activity` - Recent activities
- `POST /api/dashboard/clear-cache` - Clear system cache

## 🧪 Testing

```bash
# Run all tests
composer test

# Run tests with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=BlogTest

# In Docker
docker-compose exec app php artisan test
```

## 🎨 Code Quality

```bash
# Format PHP code
./vendor/bin/pint

# Format JavaScript/Vue code
npm run format

# Lint JavaScript/Vue code
npm run lint

# In Docker
docker-compose exec app ./vendor/bin/pint
docker-compose exec app npm run format
```

## 📦 Database Backup

### Creating Backups
```bash
# Local
php artisan backup:database

# Docker
docker-compose exec app php artisan backup:database
```

### Restoring Backups
- Via Web Interface: Navigate to Database Backup page (Admin only)
- Via Command: Use the restore functionality in the admin panel

## 🐳 Docker Commands Quick Reference

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f

# Restart a service
docker-compose restart app

# Execute commands in container
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear

# Access container shell
docker-compose exec app bash

# Rebuild containers
docker-compose up -d --build
```

For detailed Docker documentation, see [Docker Setup Guide](documentations/DOCKER_SETUP.md).

## 🔒 Security

- All user inputs are validated and sanitized
- CSRF protection enabled
- XSS protection via Vue
- SQL injection prevention via Eloquent ORM
- Rate limiting on API endpoints
- Secure password hashing with bcrypt
- Two-factor authentication support
- Environment-based configuration
- Secure session management

## 📚 Documentation

Detailed documentation for features:

- [Docker Setup Guide](documentations/DOCKER_SETUP.md)

Additional documentation will be added as features are implemented.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- Laravel Framework
- Vue.js Community
- Inertia.js
- Tailwind CSS
- Docker Community
- All contributors and maintainers

## 📞 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Check the documentation in `/documentations/`
- Review existing issues and pull requests

## 🔄 Version History

- **v1.0.0** - Initial release
  - Docker setup with Nginx, MySQL, Redis, PHPMyAdmin
  - Laravel 12 + Vue 3 with Inertia.js
  - Product and Blog management
  - User authentication and authorization
  - API endpoints with rate limiting
  - Dashboard with real-time stats

---

**Built with ❤️ using Laravel and Vue.js**
