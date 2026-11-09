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

## League sites: one engine, one database per league

`app/mfl/` runs every league site. `app/mfl/league.php` lists the leagues
(name, address, Sleeper IDs, which database, which value model). Scoring,
lineup, team count and playoff format come from each league's Sleeper settings,
so they need no entry. Each league has its own database with `league-schema.sql`
imported, named under `league_db` in `config.php` (see `config.sample.php`):
`u908084654_mfl` and `u908084654_ssffl` on Hostinger. MFL moved out of the
main database in November 2026; its old `mfl_*` tables there are a backup
until they're dropped. CMS accounts stay in the
main database for all leagues.

**League admins and accounts.** Each league's CMS is at its own site's
`/admin` (`mfl.billykulpa.com/admin`, `ssffl.billykulpa.com/admin`), signed in
with that league's own accounts (`mfl_accounts` in its database), not
billykulpa.com's. A league admin runs everything there, including accounts
under Members; a member writes power rankings and polls and comments on
awards. New accounts and resets get a temporary password shown once.
`billykulpa.com/admin/mfl/...` and `/admin/ssffl/...` forward to the league
sites. The shared admin stylesheet, script and fonts are served to the league
sites by `mfl_serve_shared_asset()`.

CLI scripts take `--league=<key>` (default `mfl`):
```
php app/mfl/sync-cli.php --league=ssffl
php app/mfl/model-build.php --league=ssffl
```

**SSFFL (ssffl.billykulpa.com)** has its own entry point, `public/ssffl/`
(deployed to `public_html/ssffl/`). It has no logo yet, so the header shows its
name; `logo` in `league.php` turns logo files in `public/ssffl/assets` on. The
shared CSS, JS and fonts stay in `public/mfl/assets` and `public/ssffl/index.php`
serves them. Its admin is at `ssffl.billykulpa.com/admin`. Locally: database
`ssffl_dev`, preview with `router-ssffl.php` on port 8093.

SSFFL server setup, in this order:

1. hPanel → Databases: create a database and user for SSFFL.
2. phpMyAdmin, with that database selected: Import → `league-schema.sql`.
3. Add its `league_db` block (see `config.sample.php`) to the server's
   `app/config.php`. Until this exists, only the SSFFL site errors; MFL and
   the main site don't read it.
4. Push to `main`.
5. hPanel → Domains → Subdomains: create `ssffl`, custom folder `ssffl`.
6. Cloudflare → DNS: proxied `CNAME` `ssffl` → `billykulpa.com`.
7. `/admin/ssffl` → **Sync now** (all seasons since 2022, then trade values).
8. hPanel → Cron Jobs, once a day:
   ```
   /usr/bin/php /home/YOURUSER/domains/billykulpa.com/public_html/app/mfl/sync-cli.php --league=ssffl
   ```

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

**League emails:** publishing rankings can email the owners through Resend. Add
`billykulpa.com` in Resend with its DNS-only records in Cloudflare, and don't
enable Resend's Receiving, because its MX would take over inbound mail. Then add
the `mfl_email` block from `config.sample.php` to the server's `config.php`, and
enter addresses under MFL → Owners. Each set emails once on publish; the editor
has a test send and a deliberate resend.

**Awards:** `/awards` calculates its winners in `app/mfl/awards.php`, so it stays
current. MFL → Awards adds a comment to any award. The page reads Midseason
Awards during a season and "<year> Season Awards" once it's complete.

**Trade values** (`/trades/values`, signed-in owners only): the daily sync
values every player and pick two ways (`app/mfl/values.php`). The model is
points above a replacement starter for this season and the next four. The
market is FantasyCalc's dynasty values. Both feeds are unofficial, so a
failure leaves yesterday's values, and the sync summary says "Trade values not
updated." Run `php app/mfl/sync-cli.php --values` to recompute on demand.
The trade machine at the top of `/trades` (owners only) runs in the browser
(`public/mfl/assets/trade-machine.js`) from the values the page embeds, so it
needs nothing beyond the daily values. The slow-moving part, aging curves and what each rookie draft slot has
produced, lives in `app/mfl/model.json` (`model-ssffl.json` for SSFFL). Rebuild
each locally once a year, after the NFL season, with `php app/mfl/model-build.php`
(add `--league=ssffl` for SSFFL, after syncing it locally), then commit it.

**Poll voting:** only signed-in owners can vote, one vote per owner on any device.
Owners sign in with an emailed link (`app/mfl/owners.php`), which needs league
email set up and their address under MFL → Owners. Everyone else sees the poll,
and results once it closes.

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
