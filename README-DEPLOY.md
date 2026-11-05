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

## Midwest Football League (mfl.billykulpa.com)

The league site shares this repo, the database and the CMS. Its entry point is
`public/mfl/`, which the normal deploy lands in `public_html/mfl/`. League data
is copied from Sleeper's public API by `app/mfl/sync.php`; no API key is needed.

**First deploy, in this order:**

1. Run `add-mfl-phase1.sql` in phpMyAdmin *before* pushing. The new login code
   reads `users.role`, so pushing first takes the CMS down until it's run.
2. Push to `main`.
3. hPanel → Domains → Subdomains: create `mfl`, using the custom folder `mfl`
   so its document root is `public_html/mfl`.
4. Cloudflare → DNS: add a proxied `CNAME` for `mfl` pointing at `billykulpa.com`.
5. `/admin/mfl` → **Sync now** for the first import.
6. hPanel → Advanced → Cron Jobs: run this once a day. Replace the path if
   File Manager shows a different one.
   ```
   /usr/bin/php /home/YOURUSER/domains/billykulpa.com/public_html/app/mfl/sync-cli.php
   ```

**Accounts:** `/admin/users` creates MFL Members, who can only open the MFL tab
and their own password page. New CMS routes are admin-only unless they pass
`ROLES_ALL` to `require_login()`.

**Power rankings:** the MFL tab's Power Rankings screen starts a set for a week
in the last published order. Save draft keeps it private; Publish rankings puts
it on `/rankings`. Authors edit their own sets and the admin can edit any. There
is one set per week. Records shown are through the week before the set's week.
Every change is logged under Save history in the editor, and Load puts any
earlier version back in the form; it only takes effect when saved.

**Polls:** MFL → Polls creates them with a title, answers and a closing time in
Central. Anyone can vote without an account, one vote per browser, held by a
random cookie; clearing cookies allows a second vote. Results appear after
voting, and to everyone once the poll closes. Answers lock at the first vote.

**Real names:** Sleeper doesn't share them. Enter them under MFL → Owners
(admin only). The site shows Username (First Last), or the username if blank.

**Each later phase** ships an `add-mfl-phaseN.sql`. Run it before pushing.

**History pages:** `/teams`, `/owners/<username>`, `/standings`, `/drafts` and
`/records` are worked out from the synced data in `app/mfl/history.php`. Records
and points are regular season. Playoff records count the title path only, so
third-place, fifth-place and losers-bracket games are left out. Places below the
playoff teams aren't ranked, because the losers bracket doesn't set a final order.

**Best Pickups:** `/pickups` ranks waiver and free-agent adds by regular-season
points in the adding team's starting lineup. Each add is its own pickup, and a
By player view combines them. The rules are in `app/mfl/reports.php`. Season
totals come from Sleeper's weekly stats times the league's scoring settings,
stored in `mfl_player_weeks`, so they include weeks as a free agent.

**Trades:** `/trades` follows every traded player and pick through weekly
rosters, so a trade counts from the first week the player was really on the new
team, after any review period. Each trade shows the season it happened and an
all-time set that keeps growing while the team holds the player. The rules are
in `app/mfl/trades.php`.

**Players:** `/players` and `/players/<id>` split everything a player has scored
into starter, bench and free agent, by team and season. The rules are in
`app/mfl/players.php`. Sleeper's public player page is linked from each one.

**Owner changes:** a franchise is Sleeper's `roster_id`. The sync starts a new
row in `mfl_tenures` when a roster's owner changes. Sleeper keeps no owner
history, so the sync can only date a change from when it first sees it.

**New seasons:** nothing to configure. The sync finds the renewed league through
the commissioner's league list.

**Rebuild finished seasons:** `php app/mfl/sync-cli.php --full`

**Local:** run the main-site server, plus this for the league site:
```
/Applications/MAMP/bin/php/php8.3.30/bin/php -S 127.0.0.1:8091 -t public/mfl router-mfl.php
```

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
