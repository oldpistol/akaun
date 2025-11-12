# Akaun

A modern invoicing and quotation management system built with Laravel, Filament, and Domain-Driven Design (DDD) architecture.

## Features

### Customer Management
- Complete CRUD operations for customers (Individual & Business)
- Support for multiple customer types (Individual, Business)
- Risk level assessment (Low, Medium, High)
- Credit limit tracking
- Multiple addresses per customer
- Soft delete support

### Invoice Management
- Full invoice lifecycle management (Draft → Sent → Paid/Overdue → Cancelled/Void)
- Auto-generated invoice numbers
- Invoice items with quantity, unit price, and tax calculations
- PDF generation for invoices
- Payment tracking with:
  - Payment methods (database-backed)
  - Payment references
  - Payment receipts (file upload)
  - Payment date tracking
- Receipt PDF generation
- Soft delete support

### Quotation Management
- Complete quotation workflow (Draft → Sent → Accepted/Rejected/Expired)
- Auto-generated quotation numbers
- Line items with discount support
- PDF generation for quotations
- Convert accepted quotations to invoices
- Expiration date tracking

### Payment Methods
- Pre-configured payment methods:
  - Bank Transfer
  - Cash
  - Credit Card
  - Debit Card
  - Cheque
  - E-Wallet
- Active/inactive status management
- Custom sort ordering

### State Management
- Malaysian states management
- Unique state codes
- Soft delete support

## Technology Stack

- **Framework**: Laravel 12
- **Admin Panel**: Filament 4
- **Frontend**: Livewire 3 + Alpine.js + Tailwind CSS 4
- **Architecture**: Domain-Driven Design (DDD)
- **Testing**: Pest 4
- **Code Quality**: Laravel Pint
- **Database**: MySQL/MariaDB
- **PDF Generation**: Barryvdh/Laravel-DomPDF

## Architecture

This application follows Domain-Driven Design principles with a clear separation of concerns:

```
src/
├── Domain/           # Business logic and entities
│   ├── Customer/
│   ├── Invoice/
│   ├── Quotation/
│   └── State/
├── Application/      # Use cases and DTOs
│   ├── Customer/
│   ├── Invoice/
│   ├── Quotation/
│   └── State/
└── Infrastructure/   # Persistence and external services
    ├── Customer/
    ├── Invoice/
    ├── Quotation/
    └── State/
```

### Key Architectural Patterns

- **Domain Layer**: Pure PHP entities with business rules
- **Application Layer**: Use cases orchestrating domain operations
- **Infrastructure Layer**: Eloquent models, repositories, and mappers
- **Presentation Layer**: Filament resources and components

## Requirements

- PHP 8.4+
- Composer
- Node.js & NPM
- MySQL 8.0+ or MariaDB 10.3+

## Installation

1. Clone the repository:
```bash
git clone https://github.com/oldpistol/akaun.git
cd akaun
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=akaun
DB_USERNAME=root
DB_PASSWORD=
```

5. Run migrations and seeders:
```bash
php artisan migrate --seed
```

6. Build assets:
```bash
npm run build
```

7. Create an admin user:
```bash
php artisan make:filament-user
```

8. Start the development server:
```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the admin panel.

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test tests/Feature/FilamentInvoicesResourceTest.php
```

Filter tests by name:
```bash
php artisan test --filter="it creates an invoice"
```

## Code Quality

Format code with Laravel Pint:
```bash
vendor/bin/pint
```

## Features in Detail

### Invoice Workflow

1. **Draft**: Initial state, can be edited freely
2. **Sent**: Invoice sent to customer, limited editing
3. **Paid**: Payment received and recorded
4. **Overdue**: Past due date without payment
5. **Cancelled**: Cancelled before payment
6. **Void**: Voided after creation

### Payment Tracking

Each paid invoice can track:
- Payment date
- Payment method (from predefined list)
- Payment reference number
- Payment receipt document (PDF/Image upload)

### PDF Generation

- **Invoices**: Download or view in browser
- **Receipts**: Generated for paid invoices with payment details
- **Quotations**: Download or view in browser

### Domain-Driven Design

The application maintains a clean separation between:
- **Domain entities**: Business logic in pure PHP
- **Eloquent models**: Database persistence
- **Mappers**: Conversion between domain and infrastructure
- **Repositories**: Data access abstraction

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
