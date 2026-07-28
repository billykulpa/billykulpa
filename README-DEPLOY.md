# billykulpa.com — deploy guide (shared PHP hosting)

Stack: PHP 8+ / MySQL (or MariaDB) / vanilla JS. No Composer, no build step, no dependencies.

## Folder layout

```
app/        ← application code (keep OUTSIDE the web root if possible)
public/     ← web root (docroot). Everything the browser can touch.
schema.sql  ← run once to create tables + seed page rows
router.php  ← local development only (php -S). Not needed in production.
```

## Steps

1. **Create the database.** In cPanel → MySQL Databases: create a database and a
   user, grant the user all privileges on it.

2. **Import the schema.** cPanel → phpMyAdmin → select the database → Import →
   `schema.sql`. This creates the tables and seeds the four editable pages
   (Home, About, Work, Notes) with starter copy.

3. **Upload the files.**
   - Contents of `public/` → into `public_html/` (including `.htaccess`).
   - The `app/` folder → ideally *next to* `public_html/`, not inside it
     (i.e., `/home/youruser/app/`). Then edit the two `require` lines at the top
     of `public_html/index.php` from `../app/...` to wherever `app/` landed —
     with the layout above, `../app/...` already works.
   - If your host forces everything inside `public_html`, put `app/` inside it
     and add this to `.htaccess` to block direct access:
     ```
     RewriteRule ^app/ - [F,L]
     ```

4. **Configure.** Copy `app/config.sample.php` to `app/config.php` and fill in
   the database name/user/password from step 1.

5. **Create your admin account.** Visit `https://billykulpa.com/admin/setup`
   and set your email + password (12+ chars). This page self-destructs after
   first use — it only works while the users table is empty.

6. **Verify.** `/` renders, `/admin` signs in, editing a page H1 shows up on
   the live page, a test post appears under `/notes`.

## Local development (macOS + MAMP)

Uses MAMP's MySQL and PHP, but PHP's built-in web server instead of MAMP's
Apache — no docroot or vhost changes, and it coexists with other MAMP projects.

1. Start MAMP's servers (only MySQL is actually needed).
2. In phpMyAdmin (MAMP's WebStart page → Tools):
   `CREATE DATABASE billykulpa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   then, with the `billykulpa` database selected, Import → `schema.sql`,
   then Import → `content-import.sql` (the migrated blog posts).
3. `cp app/config.sample.php app/config.php` and set the MAMP values:
   host `127.0.0.1`, port `8889`, user `root`, pass `root`.
4. From the project root (adjust the PHP version dir to whatever
   `ls /Applications/MAMP/bin/php/` shows):
   ```
   /Applications/MAMP/bin/php/php8.3.30/bin/php -S 127.0.0.1:8090 -t public router.php
   ```
5. Open http://127.0.0.1:8090 — first visit http://127.0.0.1:8090/admin/setup
   to create your admin account (12+ char password). Ctrl-C stops the server.

## What the CMS edits

- Per page (Home, About, Work, Notes): **H1, meta title, meta description**
- Posts: full CRUD, markdown body, draft/published, custom slug + meta

Everything else (case studies, layout) is code by design — full control over
presentation, and easy to extend the same pattern later (e.g., a `case_studies`
table) if you want portfolio entries in the CMS too.

## Security notes

- Passwords: `password_hash()` (bcrypt). Sessions: httponly, SameSite=Lax.
- All queries are prepared statements; all output is escaped.
- CSRF tokens on every form. Admin pages send `noindex`.
- `config.php` holds credentials — never commit it or put it in the web root.

## To do before launch

- Replace placeholder images (grid blocks labeled "asset needed").
- Replace placeholder copy (marked with HTML comments `PLACEHOLDER`).
- Update the LinkedIn URL in `app/views/layout.php`.
- Add a favicon and og:image.
- Optional: self-host the two fonts (Archivo, IBM Plex Mono) instead of
  Google Fonts for speed and privacy.
