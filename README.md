# Property Management Web Application

A web-based property management system for managing properties, users, and generating reports. Built with PHP and MySQL.

## Features

- User authentication (Admin & Client roles)
- Add, edit, and delete properties
- Property inventory and financial reports
- Export reports to Excel (CSV) and PDF
- Custom export with field selection and filters
- Responsive dashboard for admins and clients
- User management (admin only)
- Data validation and security best practices

## Project Structure

- `db.php` - Database connection and utility functions
- `login.php`, `logout.php`, `register.php` - Authentication
- `dashboard_admin.php`, `dashboard_client.php` - Dashboards for admin and client
- `add_property.php`, `edit_property.php`, `delete_property.php` - Property CRUD
- `property_report.php`, `property_report_improved.php`, `financial_report.php`, `reports.php` - Reporting
- `export_properties.php`, `export_pdf.php`, `export_financial_excel.php`, `export_financial_pdf.php`, `export_custom.php` - Export features
- `users.php` - User management (admin only)
- `setup.php`, `setup_database.sql`, `schema.sql` - Database setup scripts
- `style.css` - Custom styles

## Setup Instructions

1. **Clone or copy the project files** to your web server directory (e.g., `htdocs` for XAMPP).
2. **Create the database**:
   - Import [`setup_database.sql`](setup_database.sql) or [`schema.sql`](schema.sql) using phpMyAdmin or MySQL CLI.
   - Or run [`setup.php`](setup.php) in your browser for automatic setup.
3. **Configure database connection** in [`db.php`](db.php) if needed (default: `root`/no password).
4. **Start the server** (e.g., XAMPP/Apache) and open [`index.php`](index.php) in your browser.
5. **Login** using the default admin account:
   - Username: `admin`
   - Email: `admin@example.com` or `admin@gmail.com`
   - Password: `admin123` or `admin@123` (see [`setup.php`](setup.php) and [`set_admin.php`](set_admin.php))

## Usage

- **Admins** can manage all properties, users, and view all reports.
- **Clients** can manage only their own properties and view their reports.
- Use the sidebar to navigate between dashboard, reports, and export options.

## Export & Reports

- Export property and financial reports to Excel (CSV) or PDF.
- Use custom export to select specific fields and filters.

## Security Notes

- Passwords are hashed using bcrypt.
- Input validation and sanitization are implemented.
- Session management for authentication.

## Screenshots

_Add screenshots of dashboard, reports, and export pages here._

## License

This project is for educational/demo purposes.

---

**Developed by [Dharmik Mangukiya]**