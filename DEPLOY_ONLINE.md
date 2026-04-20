## HARVEE Online Deployment (PHP + MySQL)

This project is a PHP/MySQL app. Deploy it on hosting with PHP and MySQL support (cPanel/shared hosting or VPS).

### 1) Prepare hosting
- Create a domain/subdomain.
- Ensure PHP 8.x and MySQL are enabled.
- Enable HTTPS/SSL.

### 2) Create database
- In cPanel -> MySQL Databases:
- Create DB: `harvee_marketplace` (or your preferred name)
- Create DB user and assign to DB with full privileges

### 3) Import schema
- Open phpMyAdmin on hosting
- Select your DB
- Import: [`database/harvee_marketplace_all_in_one.sql`](/c:/xampppp/htdocs/HARVEE/database/harvee_marketplace_all_in_one.sql)

### 4) Upload project files
- Upload all project files to `public_html` (or your domain root)
- Exclude unnecessary folders like `node_modules`

### 5) Set production environment variables
Set these in hosting environment config or `.htaccess` (if supported):

- `DB_HOST=localhost`
- `DB_PORT=3306`
- `DB_NAME=harvee_marketplace`
- `DB_USER=your_db_user`
- `DB_PASS=your_db_password`
- `DB_CHARSET=utf8mb4`
- `APP_DEBUG=0`

`config/database.php` already reads these values with local fallbacks.

### 6) Quick test
- Open homepage
- Register/login
- Create product/order flow
- Confirm DB records are being inserted

### 7) Recommended security hardening
- Keep `APP_DEBUG=0` in production
- Restrict phpMyAdmin access
- Use strong DB/user passwords
- Schedule automatic DB backups
