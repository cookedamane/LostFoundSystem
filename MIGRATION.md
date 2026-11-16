# Move the Lost and Found System to Another Windows PC

This guide explains how to set up the project on a teammate's PC and how to bring across the existing records. **GitHub contains the application source code; it does not contain the live MySQL database, local `.env` settings, installed dependencies, or uploaded item photos.**

## Choose the right setup

- **New demo database:** use Laravel migrations and the seeder. This creates the tables and sample records only.
- **Copy the current system and its records:** export and import the complete `lost_found_db` database through phpMyAdmin, then copy the item-photo folder separately.

`php artisan migrate` creates or updates the **database structure** (tables, columns, indexes, and relationships). It does not restore records from the old PC. `php artisan db:seed` adds the project's demo accounts and sample data; it does not restore your real records. Never use `php artisan migrate:fresh` on a database you need to keep; it drops the tables first.

## 1. Prepare the new PC

1. Install XAMPP with PHP 8.2 and MySQL/MariaDB, preferably at `C:\xampp`. The provided `start-system.bat` uses that fixed path.
2. Install Git, Composer, and Node.js/npm.
3. In CMD, clone the repository into XAMPP's `htdocs` folder:

   ```cmd
   cd /d C:\xampp\htdocs
   git clone https://github.com/cookedamane/LostFoundSystem.git
   cd LostFoundSystem
   ```

The repository intentionally excludes `vendor` and `node_modules`, so each PC installs its own dependencies below.

## 2. Option A: create a clean demo database

1. Open the XAMPP Control Panel and start **MySQL**. Start **Apache** temporarily if you need phpMyAdmin (`http://localhost/phpmyadmin`).
2. In phpMyAdmin, create a database named `lost_found_db` using `utf8mb4` / `utf8mb4_unicode_ci` collation.
3. Configure Laravel. In CMD:

   ```cmd
   cd /d C:\xampp\htdocs\LostFoundSystem\lostfound-admin
   copy .env.example .env
   composer install
   ```

   Edit `lostfound-admin\.env` and set these values for the local XAMPP database:

   ```dotenv
   APP_ENV=local
   APP_DEBUG=false
   APP_URL=http://127.0.0.1:8001
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lost_found_db
   DB_USERNAME=root
   DB_PASSWORD=
   SESSION_DRIVER=file
   CACHE_STORE=file
   ```

   These database credentials match a default local XAMPP installation. If MySQL has a password or a different username, use that account instead. Keep this `.env` file private and local.

4. From `lostfound-admin`, generate the local Laravel key and create the schema plus demo data:

   ```cmd
   php artisan key:generate
   php artisan migrate --seed
   ```

   This seeds the sample admin and student accounts listed in the root `README.md`; they are demo credentials and should be changed before using real data.

## 3. Configure the PHP API and build the student app

The API connects to the **same** `lost_found_db` database. In CMD:

```cmd
cd /d C:\xampp\htdocs\LostFoundSystem\php-api
copy .env.example .env
```

Edit `php-api\.env` and verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` match the Laravel database settings. Set `CORS_ALLOWED_ORIGINS` to the exact trusted app origins for this PC. Do not copy another computer's `.env` file; create local settings for this machine.

Build the web assets that `start-system.bat` serves:

```cmd
cd /d C:\xampp\htdocs\LostFoundSystem\ionic-app
npm.cmd ci
npm.cmd run build
```

The build creates `ionic-app\www\index.html`. To start the API, admin site, and app, return to the project root and run `start-system.bat`. Keep MySQL and the opened server windows running. The admin site is `http://127.0.0.1:8001`; the local app is `http://127.0.0.1:4200`.

## 4. Option B: transfer the existing database and photos

Use this instead of the demo seeder when the teammate needs your actual users, items, categories, claims, and decisions.

### Export from the old PC

1. Start XAMPP **MySQL and Apache**, then open `http://localhost/phpmyadmin`.
2. Select `lost_found_db` and choose **Export**. Choose **Custom** if needed and make sure the export includes **both Structure and Data** for all tables. Export as SQL and download the file.
3. Copy `php-api\public\uploads\` if item photos need to move too. The database stores photo filenames; the image files themselves are separate.
4. Send the SQL file and photo folder to the teammate privately (for example, using a private USB drive or restricted file-sharing link). **Do not commit them to this GitHub repository.** They may contain student identifiers, contact details, claim evidence, and photos.

### Import on the new PC

1. Install the code and configure its local environment as described above. Start XAMPP MySQL and open phpMyAdmin.
2. Create/select a database named `lost_found_db`, select it, choose **Import**, select the SQL file, and start the import.
3. Copy the photo files into `C:\xampp\htdocs\LostFoundSystem\php-api\public\uploads\`, keeping the filenames intact.
4. Configure both Laravel and PHP API `.env` files to point to this local database. Then build the Ionic app and start the system as described above.

The full phpMyAdmin export includes the schema and records, so **do not run `php artisan migrate --seed` after importing it**. That seeder creates duplicate demo accounts and sample records. If the source code has newer migrations than the export, first make a separate backup, then run only `php artisan migrate` to apply pending schema changes. Never run `migrate:fresh` on the imported database.

The Laravel **Reports → Download database backup** is not a full phpMyAdmin export: it writes rows for application tables but does not create the database schema. For a new PC, use a full phpMyAdmin export with Structure and Data selected.

## 5. Phone network and APK

The APK starts with the address in `ionic-app\src\environments\environment.prod.ts`. Its login screen lets the user enter and test `http://<their-PC-ip>:8000/api`; the address is saved on the phone and can be changed after network changes without rebuilding. On phone browser, open `http://<their-PC-ip>:4200`; the app derives its API host from the page address. The API accepts browser app origins on the same host at port `4200`; Capacitor origins remain controlled by `CORS_ALLOWED_ORIGINS` in the API `.env`.

The phone and PC must be on the same trusted Wi-Fi/hotspot, and the PHP API must be running on the PC. GitHub Actions builds the APK but does not host the database or API. Check `http://<PC-IP>:8000/api/health` from the phone before trying to sign in. Allow the app/API ports only on a trusted Private network if Windows Firewall blocks local connections.

## What lives where

| Data or component | Location / transfer method |
| --- | --- |
| Application source | GitHub repository |
| MySQL accounts, items, categories, claims, and activity | XAMPP MySQL database `lost_found_db`; transfer using phpMyAdmin SQL export/import |
| Item photos | `php-api/public/uploads/`; copy separately |
| Per-PC database and app settings | `lostfound-admin/.env` and `php-api/.env`; create locally, never commit |
| Installed libraries | `lostfound-admin/vendor/` and `ionic-app/node_modules/`; recreated with Composer and `npm ci` |
| Built Ionic web assets | `ionic-app/www/`; recreate with `npm run build` |
