<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Install on Hostinger cPanel using Termux (Android)

These are concise, production‑ready steps to deploy this Laravel 10 app to a Hostinger cPanel/shared hosting account using Termux on Android.

### Prerequisites
- PHP 8.1+ on the server with required extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- MySQL database created in cPanel (note the DB name, user, password, and host)
- SSH access enabled in cPanel (note hostname, username, and port)
- A domain/subdomain that points to the app

### Termux setup (local)
```bash
pkg update -y
pkg install -y git openssh php nodejs-lts
# (Optional) Composer in Termux
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=$HOME/bin --filename=composer
composer --version
```

### Server setup in cPanel
- Set PHP version to 8.1+ for the domain (MultiPHP Manager or PHP Selector)
- Create a MySQL database and user, grant ALL privileges
- Enable SSH access (get host, port, username from cPanel)
- Prefer setting the domain’s document root to the app’s `public` directory if your panel allows custom doc roots

### Option A: Deploy directly on the server via SSH (recommended)
1) SSH into the server from Termux
```bash
ssh -p <PORT> <CPANEL_USER>@<HOST>
```
2) Clone the project into your home directory (or upload a zip and extract)
```bash
cd ~
git clone <YOUR_REPO_URL> invest-platform
cd invest-platform
```
3) Copy and edit environment
```bash
cp .env.example .env
nano .env  # set APP_NAME, APP_ENV=production, APP_DEBUG=false, APP_URL, DB_* values
```
4) Install PHP dependencies
```bash
# If composer is available on the server
composer install --no-dev --prefer-dist --optimize-autoloader

# If not, install locally in Termux and upload the vendor folder (see Option B)
```
5) Generate app key and run migrations
```bash
php artisan key:generate --ansi
php artisan migrate --force
php artisan storage:link
```
6) Build frontend assets
- Preferred: build locally in Termux and upload `public/build` (see Option B)
- If Node is available on server:
```bash
npm ci && npm run build
```
7) Set permissions
```bash
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```
8) Point domain to `public`
- Ideal: set the domain/subdomain document root to `/home/<USER>/invest-platform/public`
- If you must use `public_html`, move the contents of `public/` into `public_html/` and update `public_html/index.php` paths to one level up:
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```
9) Optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Option B: Build in Termux, then upload
1) Clone locally in Termux and install deps
```bash
cd ~
git clone <YOUR_REPO_URL> invest-platform
cd invest-platform
cp .env.example .env
# Edit .env locally or later on the server
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan key:generate --ansi
```
2) Package for upload (exclude .git and node_modules if desired)
```bash
zip -r invest-platform.zip . \
  -x "*.git*" "node_modules/*" "tests/*" "storage/logs/*" "storage/framework/cache/*"
```
3) Upload to server
- Via cPanel File Manager: upload `invest-platform.zip` to `~/` and extract
- Or via scp from Termux:
```bash
scp -P <PORT> invest-platform.zip <CPANEL_USER>@<HOST>:~/
```
4) SSH into server, extract, and finalize
```bash
ssh -p <PORT> <CPANEL_USER>@<HOST>
cd ~
unzip -q invest-platform.zip -d invest-platform
cd invest-platform
# If you edited .env locally, ensure DB_* values match server DB
php artisan migrate --force
php artisan storage:link
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```
5) Point domain to `public` or adjust `public_html/index.php` as in Option A
6) Optimize caches
```bash
php artisan config:cache route:cache view:cache
```

### Cron job (scheduler)
Set up a cron job in cPanel to run every minute:
```text
* * * * * php /home/<USER>/invest-platform/artisan schedule:run >> /dev/null 2>&1
```

### Notes and troubleshooting
- If Composer is not available on the server, installing in Termux and uploading the `vendor/` directory is fine
- For MySQL host, use the value provided by cPanel (often `localhost` on cPanel, or a specific host shown in DB details)
- If assets don’t load, ensure `npm run build` was executed and `public/build` exists, and that Blade uses Vite via `@vite`
- Ensure `.env` has `APP_URL` set to your domain and `APP_DEBUG=false` for production
- Clear caches after any `.env` or config changes: `php artisan optimize:clear && php artisan config:cache`
