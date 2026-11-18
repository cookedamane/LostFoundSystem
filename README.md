# DepFind: Department-Based Lost and Found Management System

This project contains three connected parts that use the same MySQL database:

```text
Student Ionic app  -> PHP JSON API  -> MySQL lost_found_db
Staff Laravel web  -----------------> MySQL lost_found_db
```

DepFind serves one configured campus department only. It covers that department’s buildings and designated collection point; it is not a campus-wide service and does not transport items between departments. Students and employees register and sign in through the app. Admins and Staff sign in through the Laravel portal.

## Phone quick access

Start XAMPP MySQL, run `start-system.bat` once from the project folder, and leave its server windows open. The script prints phone-ready links for each active private Wi-Fi/hotspot adapter. Use the links matching the network connected to the phone. The browser app automatically targets the API on the same laptop address, port `8000`.

For an installed APK, use **Connect to your laptop** on the login screen. Copy the matching **APK API address** printed by `start-system.bat` (for example `http://192.168.137.1:8000/api` when the laptop hotspot is being used), then tap **Test connection**. The address is saved on that phone; if you switch networks, replace it with the matching address printed for the new Wi-Fi. Verify using the matching API health link. Keep both devices on the same Wi-Fi/hotspot.

## Run locally on Windows / XAMPP

Moving the project to a teammate's PC or copying the database? Follow [MIGRATION.md](MIGRATION.md) first. It explains what GitHub contains, how to create demo data, and how to securely transfer the existing database and item photos.

### Requirements

- XAMPP with PHP 8.2 and MySQL/MariaDB
- Composer (the Laravel dependencies are in `lostfound-admin/vendor`)
- Node.js and npm (the Ionic dependencies are installed in `ionic-app/node_modules`)
- A database named `lost_found_db`

### Start the system

1. Start MySQL in the XAMPP Control Panel and leave it running. The application uses port `3306` and the `root` account with a blank password by default. If XAMPP reports that port `3306` is occupied, identify the existing MySQL service before starting another one.
2. After updating the project, apply the Laravel database migrations once from `lostfound-admin` with `php artisan migrate`. This updates legacy staff roles and creates the department settings shared by the portal and API. Do not use `migrate:fresh` on a database with records.
3. From the project root, run `start-system.bat`. It opens separate command windows for the API, admin site, and app. Keep those windows open while using the system. If the script reports that MySQL did not start, check the MySQL window and XAMPP logs.
4. Confirm the API is available at <http://127.0.0.1:8000/api/health>. It should return JSON with `"ok":true`.
5. Sign in as Admin at `http://127.0.0.1:8001`, open **Department settings**, and replace the placeholder department/building/pickup values with the actual assigned locations before using the app. On a phone, use the `Phone browser` link printed by `start-system.bat`.

If the windows are already open, do not launch a second copy on the same ports. Stop the old server with `Ctrl+C` in its window before restarting it.

### Demo accounts

| Role | Login | Password |
| --- | --- | --- |
| Admin (website only) | `admin@lostfound.test` | `Admin@12345` |
| Student (app only) | `student@lostfound.test` | `Student@12345` |

These are development/demo credentials. Change them before using real student or item data. Students and employees create their own accounts in the app; Admins create Staff accounts from **Accounts** in the admin site.

## Roles and permissions

Only three roles are used. Server-side middleware enforces access on every protected portal route.

| Role | Access and purpose |
| --- | --- |
| Admin (`admin`) | Manages staff accounts, department locations and pickup settings, categories, backups, and activity records; can review all items and claims. |
| Staff (`staff`) | Records and updates department found items, reviews claims, records decisions and returns, and coordinates pickup at the department’s collection point. Cannot manage accounts or system settings. |
| User (`user`) | Students and employees use the app to report items lost within the department, browse its found-item listings, submit claims, and follow claim/return status. No Laravel portal access. |

An Admin creates Staff accounts under **Accounts → Create staff account**. The migration `2026_11_19_000001_consolidate_depfind_roles` maps legacy Officer, Security, Registrar, and IT Administrator accounts to Staff, preserving their accounts and history while removing the old role types. Existing Admin and User accounts keep their roles.

An Administrator can delete an account from **Accounts**. Deletion blocks future sign-in, revokes mobile API tokens, erases the account's profile and claimant-entered ownership details, and preserves related item/claim statuses and audit history with the account anonymized. The current signed-in Administrator and the last active Administrator cannot be deleted.

## Phone and APK setup

### Build an APK with GitHub Actions

The workflow at `.github/workflows/android-apk.yml` builds the DepFind Android debug APK when code is pushed to `main` or `master`, on pull requests to those branches, or when started manually from the repository's **Actions** tab. To use it, put this project in a GitHub repository and push the project files. Open **Actions → Build Android APK**, select a completed run, and download the `DepFind-apk` artifact. The APK inside is named `DepFind.apk`. The artifact is temporary and retained for 14 days. This is a debug APK for installation/testing, not a Play Store signed release.

On Windows with Java and the Android SDK installed, run `build-depfind-apk.bat` from the project folder. It writes the installable debug APK to `ionic-app\DepFind.apk`.

The APK starts with the default API address from `ionic-app/src/environments/environment.prod.ts`, and its login screen lets the user set and test the current laptop API address. GitHub Actions builds the app package; it does not host the API or database.

The browser app derives the API host from the address used to open the app, so use the `Phone browser` address printed by `start-system.bat`. `127.0.0.1` on the phone means the phone itself, not the laptop.

1. Connect the phone and PC to the same trusted Wi-Fi network.
2. Read the phone links printed by `start-system.bat`. Use the `Phone browser` URL on the phone. In the APK, enter the matching `APK API address` on its login screen and tap **Test connection**. If the script lists several adapters, choose the one on the same Wi-Fi/hotspot as the phone.
3. Start the system with `start-system.bat`. The API listens on `0.0.0.0:8000` and Ionic listens on `0.0.0.0:4200` for phone browser access.
4. If Windows Firewall blocks the connection, allow inbound TCP ports `8000` and `4200` only on the Private profile and local subnet. Do not expose the development servers to public networks.
5. From `ionic-app`, build and sync the Android app:

   ```cmd
   npm.cmd install
   npm.cmd run build
   npx cap add android
   npx cap sync android
   npx cap open android
   ```

   In Android Studio, build the APK. After changing the API URL or Capacitor configuration, run `npm.cmd run build` and `npx cap sync android` again.

## Day-to-day workflow

1. A student or employee registers or signs in, then searches found items from the configured department locations.
2. To report a lost item, choose a department building/area and submit its name, category, date, description, and optional photo. Locations outside the configured list are rejected by both the app API and staff portal. The report appears in **Department items** for staff review.
3. To claim a found item, open its details and provide proof of ownership. The request appears in **Claims**.
4. Staff review the report or claim and set the decision/status with clear remarks. Students see status updates in **Activity**.
5. When the owner collects the item at the designated collection point, staff open the approved claim and choose **Mark item as returned**. This records the return; DepFind does not transport items between departments.

Admins can set the department name, its allowed buildings/areas, and its designated pickup point in **Department settings**. Configure these values before use; the initial defaults are placeholders.

Use general, non-sensitive descriptions for public found-item listings. Do not put personal contact details, serial numbers, or all identifying details in a found-item description; ask the claimant to provide distinguishing proof privately in the claim form.

## Safe data management

- Keep MySQL running while the API and admin site are in use. Both applications write to `lost_found_db`; do not point one application at a different database.
- Do not run `php artisan migrate:fresh` on a database with records. It drops existing tables and data.
- Use **Reports** for item/claim summaries and CSV exports. Check the date filters before exporting, and store exports where access is limited because they contain claimant details.
- Use the admin **Backup** link to download a SQL backup. Store it outside the web root and verify backups can be restored before relying on them.
- Back up item photos separately from SQL data. They are stored in `php-api/public/uploads/`; the SQL backup stores photo filenames, not the image files.
- To restore, create/select the intended database, import the SQL backup using phpMyAdmin or the MySQL command line, then restore the corresponding `php-api/public/uploads/` files. Avoid restoring over current data until you have made a fresh backup.
- Staff should deactivate accounts when access is no longer needed and reset compromised passwords. Do not share admin credentials with students.

## Configuration reference

- Laravel database and session settings: `lostfound-admin/.env`; see `lostfound-admin/.env.example` for DepFind environment keys. The Admin **Department settings** page stores the department name, allowed locations, and collection point in the shared database.
- API database settings: `php-api/.env` if present; otherwise the API defaults to MySQL on `127.0.0.1:3306`, database `lost_found_db`, user `root`, blank password.
- API browser-origin allowlist: `CORS_ALLOWED_ORIGINS` in `php-api/.env`; browser apps on the same host at port `4200` are accepted automatically, while Capacitor origins use the configured allowlist.
- Browser app API URL: `ionic-app/src/environments/environment.ts`.
- Phone/production API URL: `ionic-app/src/environments/environment.prod.ts`.
- Shared item photo folder: `php-api/public/uploads/`.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| App displays “Cannot reach the server” | Confirm the API command window is still open and `http://127.0.0.1:8000/api/health` responds. Check the LAN address in `environment.ts`, then restart `npm.cmd start` and reload the page. |
| Sign-in or registration says the server did not respond | Keep the API window open. Copy the matching `APK API address` printed by `start-system.bat` into the APK login screen and test it. For phone browser, open its matching `Phone browser` link. |
| API responds but sign-in/registration says the database is unavailable | Start MySQL in XAMPP and wait until it is running on port `3306`. The API now returns HTTP 503 with a clear database message if MySQL is down. |
| App shows a CORS/network error after changing network | Confirm both devices use the same trusted network and restart the API after `.env` edits. Browser app origins on the same host and port `4200` are accepted automatically. |
| Health endpoint works, but sign-in/register fails | Confirm MySQL is running on port `3306`, `lost_found_db` exists, and Laravel/API `.env` values point to the same database. Inspect the PHP API command window for database errors. |
| XAMPP says port `3306` is in use | Another MySQL/MariaDB process owns the port. Do not start multiple servers; identify the service and use one database server consistently. |
| Phone cannot reach the API | Open the matching `API health check` link printed by `start-system.bat` on the phone. Confirm the API window is running on `0.0.0.0:8000`, both devices are on the same Wi-Fi/hotspot, and Windows Firewall permits local Private-network access. |
| Port already in use | Stop the old server window with `Ctrl+C`, then restart only one copy of that service. |
| Build fails after a dependency change | Run `npm.cmd install`, then `npm.cmd run build` from `ionic-app`; read the first Angular/TypeScript error rather than the later cascade. |

## Architecture and requirements map

| Area | Implementation |
| --- | --- |
| Database | Laravel migrations under `lostfound-admin/database/migrations/`; includes users, categories, department-scoped items and claims, department settings, activity logs, and API tokens. |
| Admin web | Laravel controllers, models, routes, and Blade pages under `lostfound-admin/`. Includes Admin/Staff permissions, department settings, scoped item/claim management, reports/CSV, and SQL backup. |
| Student app | Standalone Ionic/Angular pages under `ionic-app/src/app/pages/`; registration/login, search, lost reports, claims, activity, profile, and password change. |
| API | `php-api/public/index.php`; JSON endpoints, token auth, validation, and prepared SQL statements. |
| Security | Separate role-specific sign-in, password hashes, expiring hashed tokens, rate limiting/lockout, CSRF-protected Laravel forms, validation, and photo type/size checks. |

The API accepts only the listed local app origins, validates JSON/photo sizes, and limits repeated registration and failed login attempts. Admin sessions use encrypted, HTTP-only, same-site cookies; Laravel debug output is disabled. Admin and user authorization are checked on the server for each protected request.

For production use, deploy behind HTTPS, set only the production app origin in `CORS_ALLOWED_ORIGINS`, use a non-root database account with a strong password, rotate demo credentials, move uploads outside the public web root, and use a production-grade web server instead of PHP's development server. Plain HTTP on a local hotspot does not encrypt passwords or bearer tokens over Wi-Fi; use only a trusted hotspot and do not use real sensitive data until HTTPS is configured.
