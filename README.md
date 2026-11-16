# TEAM BANGAN Lost and Found Management System

This project contains three connected parts that use the same MySQL database:

```text
Student Ionic app  -> PHP JSON API  -> MySQL lost_found_db
Staff Laravel web  -----------------> MySQL lost_found_db
```

Students register and sign in through the app. Staff sign in through the admin website. The API rejects admin sign-ins; the website rejects student accounts.

## Phone quick access

**Phone app link (when the phone is connected to this laptop's hotspot):**

### [http://192.168.137.1:4200](http://192.168.137.1:4200)

Start XAMPP MySQL, run `start-system.bat` once from the project folder, and leave its server windows open. It serves the existing optimized build without rebuilding each time. After changing app code or the API address, run `build-phone-app.bat` once to regenerate the optimized bundle. Then open the link above in the phone browser. The app's API health link is <http://192.168.137.1:8000/api/health>; this is a diagnostic endpoint, not the app page. The laptop and phone must stay connected through the same hotspot.

If the app link does not open, run `ipconfig` on the laptop and check the `Local Area Connection*` IPv4 address. Replace `192.168.137.1` in this README and the app environment files if the hotspot address changed.

## Run locally on Windows / XAMPP

Moving the project to a teammate's PC or copying the database? Follow [MIGRATION.md](MIGRATION.md) first. It explains what GitHub contains, how to create demo data, and how to securely transfer the existing database and item photos.

### Requirements

- XAMPP with PHP 8.2 and MySQL/MariaDB
- Composer (the Laravel dependencies are in `lostfound-admin/vendor`)
- Node.js and npm (the Ionic dependencies are installed in `ionic-app/node_modules`)
- A database named `lost_found_db`

### Start the system

1. Start MySQL in the XAMPP Control Panel and leave it running. The application uses port `3306` and the `root` account with a blank password by default. If XAMPP reports that port `3306` is occupied, identify the existing MySQL service before starting another one.
2. From the project root, run `start-system.bat`. It opens separate command windows for the API, admin site, and app. Keep those windows open while using the system. If the script reports that MySQL did not start, check the MySQL window and XAMPP logs.
3. Confirm the API is available at <http://127.0.0.1:8000/api/health>. It should return JSON with `"ok":true`.
4. Open the user app at <http://192.168.137.1:4200> when the phone is connected to this PC's hotspot, or use the current Wi-Fi IPv4 address shown by `ipconfig`. On the PC, `http://127.0.0.1:4200` also works. The admin site is at <http://127.0.0.1:8001>.

If the windows are already open, do not launch a second copy on the same ports. Stop the old server with `Ctrl+C` in its window before restarting it.

### Demo accounts

| Role | Login | Password |
| --- | --- | --- |
| Admin (website only) | `admin@lostfound.test` | `Admin@12345` |
| Student (app only) | `student@lostfound.test` | `Student@12345` |

These are development/demo credentials. Change them before using real student or item data. Students create their own accounts in the app; admins create staff accounts from **Users** in the admin site.

## Staff roles and permissions

Students (`user`) register and sign in only in the mobile app. The web portal accepts active staff accounts only. **Only an Administrator can open Users, activate/deactivate accounts, reset passwords, delete accounts, and create staff accounts with a selected role.** No other staff role needs the account directory: Registrars verify a claimant from the claim record, while Officers and IT Administrators use their own workflow screens. Student accounts cannot be created from the web form. The server checks role permissions on every protected route; hiding a menu link is not the security control.

| Stakeholder role | Admin-site access | Purpose in the workflow |
| --- | --- | --- |
| Administrator (`admin`) | All modules, including Users, Categories, Reports, backup, and activity log | Manages staff access and system configuration and oversees lost/found, claim, decision, and return records. |
| Lost and Found Officer (`officer`) | Items, claims, and reports | Reviews student lost reports, manages item records, reviews ownership proof, records claim decisions, and marks physical returns. |
| Security Personnel (`security`) | View item list and register found items | Records property received by security and helps preserve found-item custody. Cannot edit/delete items, decide claims, manage accounts, or change categories. |
| Registrar (`registrar`) | Read-only claims and claimant details | Supports identity/student-record verification for a claim. Cannot approve/reject claims or administer user accounts. |
| IT Administrator (`it_admin`) | Dashboard, database backup, and activity log | Supports technical continuity and audits staff actions. Cannot access user management or make claim decisions. |
| Student (`user`) | No staff-site access | Self-registers in the Ionic app, reports lost property, searches found items, submits claims, and checks claim/return status. |

An Administrator creates staff accounts under **Users → Create staff account**, selects one of the stakeholder roles, and provides a unique email and password. The role takes effect immediately. Existing Administrator and Student accounts keep their current roles when the role migration is applied.

An Administrator can delete an account from **Users**. Deletion blocks future sign-in, revokes mobile API tokens, erases the account's profile and claimant-entered ownership details, and preserves related item/claim statuses and audit history with the account anonymized. The current signed-in Administrator and the last active Administrator cannot be deleted. This is intended to protect the record of decisions and returns while removing the person's account data.

## Phone and APK setup

### Build an APK with GitHub Actions

The workflow at `.github/workflows/android-apk.yml` builds the Ionic Android debug APK when code is pushed to `main` or `master`, on pull requests to those branches, or when started manually from the repository's **Actions** tab. To use it, put this project in a GitHub repository and push the project files. Open **Actions → Build Android APK**, select a completed run, and download the `team-bangan-lostfound-debug-apk` artifact. The artifact is temporary and retained for 14 days. This is a debug APK for installation/testing, not a Play Store signed release.

The APK is compiled with the API address in `ionic-app/src/environments/environment.prod.ts`. It currently points to `http://192.168.137.1:8000/api`, so for local use the phone must be on the PC's hotspot/network and the PHP API must be running on the PC. GitHub Actions builds the app package; it does not host the API or database. If the PC's LAN address changes, update the production environment file and push the change before downloading a new APK.

The browser development and production configurations use the PC LAN address in `ionic-app/src/environments/environment.ts` and `environment.prod.ts`. When this PC's Mobile Hotspot is active, its hosted adapter commonly uses `192.168.137.1`; confirm it with `ipconfig`. A phone cannot use `127.0.0.1` to reach the computer; it means the phone itself.

1. Connect the phone and PC to the same trusted Wi-Fi network.
2. Run `ipconfig` on the PC and find the IPv4 address for the network the phone uses. If the phone uses this PC's Mobile Hotspot, use the `Local Area Connection*` hosted adapter address (currently `192.168.137.1`), not the laptop's upstream Wi-Fi address. Update `apiUrl` in both environment files if the address changes.
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

1. A student registers or signs in, then searches the listed found items.
2. To report a lost item, submit its name, category, date, location, description, and optional photo. The report appears in **Items** for staff review.
3. To claim a found item, open its details and provide proof of ownership. The request appears in **Claims**.
4. Staff review the report or claim and set the decision/status with clear remarks. Students can refresh **Activity** to see the latest status and remarks.
5. When the item is physically handed to its owner, staff open the approved claim and choose **Mark item as returned**. This records the final returned status.

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

- Laravel database and session settings: `lostfound-admin/.env`. `lostfound-admin/.env.lostfound` is the project template.
- API database settings: `php-api/.env` if present; otherwise the API defaults to MySQL on `127.0.0.1:3306`, database `lost_found_db`, user `root`, blank password.
- API browser-origin allowlist: `CORS_ALLOWED_ORIGINS` in `php-api/.env`; add the exact trusted app origin if the hotspot/LAN address changes.
- Browser app API URL: `ionic-app/src/environments/environment.ts`.
- Phone/production API URL: `ionic-app/src/environments/environment.prod.ts`.
- Shared item photo folder: `php-api/public/uploads/`.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| App displays “Cannot reach the server” | Confirm the API command window is still open and `http://127.0.0.1:8000/api/health` responds. Check the LAN address in `environment.ts`, then restart `npm.cmd start` and reload the page. |
| Sign-in or registration says the server did not respond | Keep the API window open and confirm the app is using `http://192.168.137.1:8000/api` on the hotspot. The app now times out after 12 seconds with a clear message instead of spinning indefinitely. |
| API responds but sign-in/registration says the database is unavailable | Start MySQL in XAMPP and wait until it is running on port `3306`. The API now returns HTTP 503 with a clear database message if MySQL is down. |
| App shows a CORS/network error after changing network | Update the exact app origin in `php-api/.env` under `CORS_ALLOWED_ORIGINS`, restart the API, and confirm the phone and laptop use the same trusted subnet. |
| Health endpoint works, but sign-in/register fails | Confirm MySQL is running on port `3306`, `lost_found_db` exists, and Laravel/API `.env` values point to the same database. Inspect the PHP API command window for database errors. |
| XAMPP says port `3306` is in use | Another MySQL/MariaDB process owns the port. Do not start multiple servers; identify the service and use one database server consistently. |
| Phone cannot reach the API | Use the PC’s current LAN IP in `environment.prod.ts`, bind API to `0.0.0.0:8000`, keep the phone on the same Wi-Fi, and check the Private-network firewall rule. |
| Port already in use | Stop the old server window with `Ctrl+C`, then restart only one copy of that service. |
| Build fails after a dependency change | Run `npm.cmd install`, then `npm.cmd run build` from `ionic-app`; read the first Angular/TypeScript error rather than the later cascade. |

## Architecture and requirements map

| Area | Implementation |
| --- | --- |
| Database | `lostfound-admin/database/migrations/2026_11_01_000001_create_lost_found_tables.php`; users, categories, items, claims, activity logs, and API tokens. |
| Admin web | Laravel controllers, models, routes, and Blade pages under `lostfound-admin/`. Includes item and category management, claims, user accounts, reports/CSV, and SQL backup. |
| Student app | Standalone Ionic/Angular pages under `ionic-app/src/app/pages/`; registration/login, search, lost reports, claims, activity, profile, and password change. |
| API | `php-api/public/index.php`; JSON endpoints, token auth, validation, and prepared SQL statements. |
| Security | Separate role-specific sign-in, password hashes, expiring hashed tokens, rate limiting/lockout, CSRF-protected Laravel forms, validation, and photo type/size checks. |

The API accepts only the listed local app origins, validates JSON/photo sizes, and limits repeated registration and failed login attempts. Admin sessions use encrypted, HTTP-only, same-site cookies; Laravel debug output is disabled. Admin and user authorization are checked on the server for each protected request.

For production use, deploy behind HTTPS, set only the production app origin in `CORS_ALLOWED_ORIGINS`, use a non-root database account with a strong password, rotate demo credentials, move uploads outside the public web root, and use a production-grade web server instead of PHP's development server. Plain HTTP on a local hotspot does not encrypt passwords or bearer tokens over Wi-Fi; use only a trusted hotspot and do not use real sensitive data until HTTPS is configured.
