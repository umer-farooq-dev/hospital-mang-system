# Go-live runbook

This is for the person who installs the system at a hospital and moves the hospital onto it.
In the application, **Settings > Go-live check** shows what is still to fix, and **Settings > Data import** loads the starting data.

## 1. Server

- PHP 8.1 or later with these extensions: pdo_mysql, mbstring, gd, zip, intl, bcmath. Also MySQL 8 or MariaDB 10.4 or later, and Composer.
- Install the code: `composer install --no-dev --optimize-autoloader`, then `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force` and `php artisan storage:link`.
- Set these values in `.env`:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://…`
  - the database account (its own user, not root)
  - the `MAIL_*` lines for sending mail
- Cache the configuration: `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache`. Run them again after every update.
- Run the scheduler every minute. The nightly backup and the nightly accounting entries depend on it:
  - Linux (cron): `* * * * * php /path/to/hms/artisan schedule:run >> /dev/null 2>&1`
  - Windows: in Task Scheduler, create a task that repeats every minute and runs `php.exe C:\path\to\hms\artisan schedule:run`.
- Open **Go-live check** until every line in the Server and Security groups shows OK:
  - change the installation password;
  - rename the `admin@hms.com` account to a real person.

## 2. Backups

- **Go-live check > Backups and night tasks** sets:
  - the backup time;
  - how many copies to keep;
  - the path of `mysqldump` (on Windows XAMPP: `C:\xampp\mysql\bin\mysqldump.exe`);
  - a second folder for a copy on another disk or a network share;
  - whether the uploaded files are saved too.
- Backups are written to `storage/app/backups`. Download one from the screen to keep it off the server.
- To restore, stop the application first, then run:
  `gunzip -c hms_YYYYmmdd_HHMMSS.sql.gz | mysql -u USER -p DATABASE`
  If the files archive was used, unzip it into `storage/app/public`.
- Test a restore once on another machine before go-live.

## 3. Starting data (Settings > Data import)

Import in this order. For each file, download the empty template, fill it, **Check the file**, read the errors, then **Import**. A line that is already in the system is never created twice. After you fix the failed lines (download them with **Lines with errors**), import the same file again.

1. Master lists (Settings > Master lists): payment methods, payer types, ID document types, units. Imports match their values to these lists.
2. Stores, doctor departments, cash registers and the chart of accounts (Accounting > Setup).
3. Insurers, then suppliers.
4. Doctors and staff (login e-mail required; each person sets a password with "Forgot password").
5. Patients. Keep the old file number in the Number column, so the patient cards stay valid.
6. Insurance policies (patient number and insurer).
7. Medicines (Pharmacy > Medicines > Import), then opening stock by store and batch.
   - The screen shows the stock value at cost.
8. Opening balances, as one balanced entry on the go-live date:
   - cash, bank and mobile money;
   - money owed by patients and insurers;
   - money owed to suppliers;
   - the stock value from step 7;
   - equity as the balancing line.

Very large files can be run from the command line (checked only, unless `--commit` is given):

```
php artisan data:import patients patients.xlsx --prefix=226
php artisan data:import patients patients.xlsx --prefix=226 --commit
php artisan data:import opening_stock stock.csv --store=1 --commit
php artisan data:import opening_balances balances.csv --date=2027-01-01 --commit
```

## 4. Trial migration and reconciliation

- Do at least one full trial on a copy of the database: install, import everything, then compare with the old program.
  - Counts: the Data import history, and the Go-live check > Starting data line.
  - Stock: Pharmacy > Stock reports > Valuation, by store, compared with the old stock list.
  - Money: Accounting > Trial balance on the go-live date, compared with the old balances. Debit and credit totals must be equal.
- Note every difference and its cause. Fix the source files, not the database.
- Then wipe the trial database (`php artisan migrate:fresh --seed --force` on the trial copy only, never on the live database) and repeat until the numbers match.

## 5. Cut-over day

1. Stop work in the old program at the agreed time (end of day). Print or export its closing stock and balances.
2. Back up the old data and the new database.
3. Import the latest files (steps 3 to 8 above, using the closing figures).
4. Reconcile: check the stock valuation and the trial balance against the old closing figures.
5. On the Go-live check page, switch on the nightly accounting entries once the posting rules are checked (Accounting > Posting rules).
6. Open the cash registers and start working. Keep the old program read-only for look-ups.

If the go-live must be stopped, keep working in the old program. Nothing in the old data was changed.
