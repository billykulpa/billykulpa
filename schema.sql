-- billykulpa.com — schema
-- MySQL / MariaDB, utf8mb4

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  display_name VARCHAR(80) NOT NULL DEFAULT '',  -- author credit on MFL rankings
  password_hash VARCHAR(255) NOT NULL,
  -- Defaults to the least-privileged role; /admin/setup sets 'admin' explicitly.
  role ENUM('admin','mfl_member') NOT NULL DEFAULT 'mfl_member',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per editable page. h1, meta_title, meta_description are CMS-editable.
CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,      -- '' = home, 'about', 'work', 'notes'
  label VARCHAR(190) NOT NULL,            -- shown in admin list
  h1 VARCHAR(255) NOT NULL DEFAULT '',
  lede TEXT NULL,
  body_md MEDIUMTEXT NULL,
  body_html MEDIUMTEXT NULL,
  meta_title VARCHAR(255) NOT NULL DEFAULT '',
  meta_description VARCHAR(500) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  meta_title VARCHAR(255) NOT NULL DEFAULT '',
  meta_description VARCHAR(500) NOT NULL DEFAULT '',
  body_md MEDIUMTEXT NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status_published (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the editable pages with starter copy (all editable in /admin).
INSERT INTO pages (slug, label, h1, meta_title, meta_description) VALUES
('',      'Home',
 'A designer who leads, listens, and codes.',
 'Billy Kulpa — Creative Director, Designer & Developer',
 'Billy Kulpa is a creative leader in Roscoe, Illinois who runs brand systems from first pitch to final pixel — and writes the code that ships them.'),
('about', 'About',
 'A designer who counts. And reads.',
 'About — Billy Kulpa',
 'Creative manager, brand-systems designer, and front-end developer. More than twenty years across design, code, and creative leadership.'),
('work',  'Work',
 'Selected work',
 'Work — Billy Kulpa',
 'Case studies in brand systems, product design, and front-end engineering by Billy Kulpa.'),
('contact', 'Contact',
 'Hello there.',
 'Contact — Billy Kulpa',
 'Get in touch with Billy Kulpa — questions, projects, or just to say hello.'),
('resume', 'Resume',
 'Resume',
 'Billy Kulpa''s Resume',
 'The resume of Billy Kulpa, a creative director, designer, developer, and musician in Roscoe, Illinois'),
('work/restreak', 'Work / Restreak',
 'Restreak',
 'Restreak — daily sports trivia — Billy Kulpa',
 'Case study: designing, building, and shipping Restreak, a daily sports trivia game — product design, brand, and front-end engineering by one person.'),
('work/fma-social', 'Work / FMA Social',
 'FMA Social Brand Management',
 'FMA Social Brand Management — Billy Kulpa',
 'Managing one parent brand and two overlapping subbrands — FMA, The Fabricator, and SparkForce — across a constant cadence of social content.'),
('work/fma-email', 'Work / FMA Email',
 'Email Design at Scale',
 'Email Design at Scale — Billy Kulpa',
 'Rebuilding a national association''s email program on a new platform: a template system, staff training, and the governance rules behind two million sends a year.'),
('work/judes-reading-quest', 'Work / Reading Quest',
 'Jude''s Reading Quest',
 'Jude''s Reading Quest — Billy Kulpa',
 'A screen-time deal with my son, shipped as an app: read the daily chapter, pass a quiz, unlock the iPad.'),
('work/supporting-local-music', 'Work / Local Music',
 'Supporting Local Music',
 'Supporting Local Music — Billy Kulpa',
 'Concert posters, cassette packaging, and other work from the Rockford, Illinois music scene.'),
('notes', 'Notes',
 'Notes',
 'Notes — Billy Kulpa',
 'Writing on design systems, creative leadership, and building things by hand.')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- ---------------------------------------------------------------------------
-- Midwest Football League (mfl.billykulpa.com)
-- Everything below is filled by the Sleeper sync (app/mfl/sync.php), so a
-- table can be rebuilt with `php app/mfl/sync-cli.php --full`.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS mfl_seasons (
  season SMALLINT UNSIGNED PRIMARY KEY,
  league_id VARCHAR(32) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  status VARCHAR(20) NOT NULL,                -- pre_draft, drafting, in_season, complete
  num_teams TINYINT UNSIGNED NOT NULL,
  playoff_teams TINYINT UNSIGNED NOT NULL,
  playoff_week_start TINYINT UNSIGNED NOT NULL,
  last_scored_week TINYINT UNSIGNED NOT NULL DEFAULT 0,
  champion_franchise_id TINYINT UNSIGNED NULL,
  runner_up_franchise_id TINYINT UNSIGNED NULL,
  synced_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A franchise is the roster itself. Its id is Sleeper's roster_id, which stays
-- with the roster through league renewals and owner changes.
CREATE TABLE IF NOT EXISTS mfl_franchises (
  id TINYINT UNSIGNED PRIMARY KEY,
  name VARCHAR(120) NOT NULL                  -- latest team name
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_owners (
  id VARCHAR(32) PRIMARY KEY,                 -- Sleeper user_id
  display_name VARCHAR(120) NOT NULL,         -- Sleeper username
  real_name VARCHAR(120) NOT NULL DEFAULT '', -- entered in the CMS; Sleeper doesn't share it
  avatar VARCHAR(64) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who ran which franchise, and when. NULL end_season = current owner.
-- NULL end_week = through the end of end_season. Franchise stats ignore this
-- table; an owner's own record is the matchups inside their tenure range.
CREATE TABLE IF NOT EXISTS mfl_tenures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  franchise_id TINYINT UNSIGNED NOT NULL,
  owner_id VARCHAR(32) NOT NULL,
  start_season SMALLINT UNSIGNED NOT NULL,
  start_week TINYINT UNSIGNED NOT NULL DEFAULT 1,
  end_season SMALLINT UNSIGNED NULL,
  end_week TINYINT UNSIGNED NULL,
  UNIQUE KEY uq_tenure (franchise_id, start_season, start_week),
  KEY idx_owner (owner_id),
  FOREIGN KEY (franchise_id) REFERENCES mfl_franchises(id),
  FOREIGN KEY (owner_id) REFERENCES mfl_owners(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_team_seasons (
  season SMALLINT UNSIGNED NOT NULL,
  franchise_id TINYINT UNSIGNED NOT NULL,
  owner_id VARCHAR(32) NULL,                  -- NULL while a team is orphaned
  team_name VARCHAR(120) NOT NULL,
  wins TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- regular season only
  losses TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ties TINYINT UNSIGNED NOT NULL DEFAULT 0,
  points_for DECIMAL(8,2) NOT NULL DEFAULT 0,
  points_against DECIMAL(8,2) NOT NULL DEFAULT 0,
  max_points DECIMAL(8,2) NOT NULL DEFAULT 0, -- best possible lineup, per Sleeper
  faab_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  roster_json MEDIUMTEXT NULL,                -- latest player ids, kept for later features
  PRIMARY KEY (season, franchise_id),
  FOREIGN KEY (season) REFERENCES mfl_seasons(season),
  FOREIGN KEY (franchise_id) REFERENCES mfl_franchises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_matchups (
  season SMALLINT UNSIGNED NOT NULL,
  week TINYINT UNSIGNED NOT NULL,
  franchise_id TINYINT UNSIGNED NOT NULL,
  opponent_franchise_id TINYINT UNSIGNED NULL,
  points DECIMAL(7,2) NOT NULL DEFAULT 0,
  result CHAR(1) NULL,                        -- W, L, T; NULL = no opponent that week
  is_playoff TINYINT(1) NOT NULL DEFAULT 0,
  starters_json TEXT NULL,
  starter_points_json TEXT NULL,
  players_json TEXT NULL,                     -- the whole roster that week, bench included
  player_points_json TEXT NULL,               -- { player_id: points } for that whole roster
  PRIMARY KEY (season, week, franchise_id),
  KEY idx_franchise (franchise_id, season, week),
  FOREIGN KEY (season) REFERENCES mfl_seasons(season),
  FOREIGN KEY (franchise_id) REFERENCES mfl_franchises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_brackets (
  season SMALLINT UNSIGNED NOT NULL,
  bracket ENUM('winners','losers') NOT NULL,
  match_id SMALLINT UNSIGNED NOT NULL,
  round TINYINT UNSIGNED NOT NULL,
  team1_franchise_id TINYINT UNSIGNED NULL,
  team2_franchise_id TINYINT UNSIGNED NULL,
  -- As Sleeper reports them. In the losers bracket the "winner" is the team
  -- that lost on the field and moved on toward last place.
  winner_franchise_id TINYINT UNSIGNED NULL,
  loser_franchise_id TINYINT UNSIGNED NULL,
  placement TINYINT UNSIGNED NULL,            -- this game decides that place; 1 = title game
  PRIMARY KEY (season, bracket, match_id),
  FOREIGN KEY (season) REFERENCES mfl_seasons(season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_drafts (
  id VARCHAR(32) PRIMARY KEY,                 -- Sleeper draft_id
  season SMALLINT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL,                  -- snake, linear, auction
  status VARCHAR(20) NOT NULL,
  rounds TINYINT UNSIGNED NOT NULL,
  started_at DATETIME NULL,
  FOREIGN KEY (season) REFERENCES mfl_seasons(season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_draft_picks (
  draft_id VARCHAR(32) NOT NULL,
  pick_no SMALLINT UNSIGNED NOT NULL,
  round TINYINT UNSIGNED NOT NULL,
  draft_slot TINYINT UNSIGNED NOT NULL,
  franchise_id TINYINT UNSIGNED NOT NULL,     -- the franchise that made the pick
  player_id VARCHAR(16) NOT NULL,
  player_name VARCHAR(120) NOT NULL,
  position VARCHAR(8) NOT NULL DEFAULT '',
  nfl_team VARCHAR(8) NOT NULL DEFAULT '',
  PRIMARY KEY (draft_id, pick_no),
  KEY idx_franchise (franchise_id),
  KEY idx_player (player_id),
  FOREIGN KEY (draft_id) REFERENCES mfl_drafts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_transactions (
  id VARCHAR(32) PRIMARY KEY,                 -- Sleeper transaction_id
  season SMALLINT UNSIGNED NOT NULL,
  week TINYINT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL,                  -- trade, waiver, free_agent, commissioner
  status VARCHAR(20) NOT NULL,                -- complete, failed
  created_at DATETIME NOT NULL,
  completed_at DATETIME NULL,                 -- when it took effect; a trade's review period sits between these
  franchise_ids_json VARCHAR(255) NOT NULL,
  adds_json TEXT NULL,                        -- { player_id: franchise_id }
  drops_json TEXT NULL,
  picks_json TEXT NULL,
  faab_json TEXT NULL,
  settings_json TEXT NULL,
  KEY idx_season_week (season, week),
  FOREIGN KEY (season) REFERENCES mfl_seasons(season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot of draft picks that have changed hands, replaced on every sync.
CREATE TABLE IF NOT EXISTS mfl_traded_picks (
  pick_season SMALLINT UNSIGNED NOT NULL,
  round TINYINT UNSIGNED NOT NULL,
  original_franchise_id TINYINT UNSIGNED NOT NULL,
  current_franchise_id TINYINT UNSIGNED NOT NULL,
  previous_franchise_id TINYINT UNSIGNED NULL,
  PRIMARY KEY (pick_season, round, original_franchise_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only players the league has touched, so names survive without keeping
-- Sleeper's full player dump around.
CREATE TABLE IF NOT EXISTS mfl_players (
  id VARCHAR(16) PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  position VARCHAR(8) NOT NULL DEFAULT '',
  nfl_team VARCHAR(8) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backs the site's "last updated" line and the CMS sync history.
CREATE TABLE IF NOT EXISTS mfl_sync_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('running','ok','error') NOT NULL DEFAULT 'running',
  trigger_source ENUM('cron','manual') NOT NULL,
  user_id INT UNSIGNED NULL,
  summary VARCHAR(500) NOT NULL DEFAULT '',
  KEY idx_status_finished (status, finished_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One official set of power rankings per week.
-- week: 0 = preseason, 1-17 = that week, 18 = final.
CREATE TABLE IF NOT EXISTS mfl_rankings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  season SMALLINT UNSIGNED NOT NULL,
  week TINYINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL DEFAULT '',     -- blank = "Week 9 Power Rankings"
  intro_md TEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  author_user_id INT UNSIGNED NULL,           -- who may edit it
  author_name VARCHAR(80) NOT NULL,           -- the byline; survives a removed account
  created_at DATETIME NOT NULL,
  published_at DATETIME NULL,
  public_updated_at DATETIME NULL,            -- feeds the site's "last updated" line
  UNIQUE KEY uq_week (season, week),
  KEY idx_status (status),
  FOREIGN KEY (season) REFERENCES mfl_seasons(season),
  FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- team_name and owner_id are frozen at publish, so the archive keeps showing
-- the team as it was that week.
CREATE TABLE IF NOT EXISTS mfl_ranking_entries (
  ranking_id INT UNSIGNED NOT NULL,
  franchise_id TINYINT UNSIGNED NOT NULL,
  rank_no TINYINT UNSIGNED NOT NULL,
  team_name VARCHAR(120) NOT NULL,
  owner_id VARCHAR(32) NULL,
  writeup_md TEXT NULL,
  PRIMARY KEY (ranking_id, franchise_id),
  KEY idx_franchise (franchise_id),
  FOREIGN KEY (ranking_id) REFERENCES mfl_rankings(id) ON DELETE CASCADE,
  FOREIGN KEY (franchise_id) REFERENCES mfl_franchises(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A poll closes at expires_at; there is no separate closed status.
CREATE TABLE IF NOT EXISTS mfl_polls (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(200) NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  expires_at DATETIME NOT NULL,
  author_user_id INT UNSIGNED NULL,           -- who may edit it
  author_name VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  published_at DATETIME NULL,
  public_updated_at DATETIME NULL,            -- feeds the site's "last updated" line
  KEY idx_status_expires (status, expires_at),
  FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfl_poll_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  label VARCHAR(200) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_poll (poll_id, sort_order),
  FOREIGN KEY (poll_id) REFERENCES mfl_polls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One vote per browser. voter_hash is a hash of a random cookie, so nothing
-- here identifies a person, and clearing cookies does allow a second vote.
CREATE TABLE IF NOT EXISTS mfl_poll_votes (
  poll_id INT UNSIGNED NOT NULL,
  voter_hash CHAR(64) NOT NULL,
  answer_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (poll_id, voter_hash),
  KEY idx_answer (answer_id),
  FOREIGN KEY (poll_id) REFERENCES mfl_polls(id) ON DELETE CASCADE,
  FOREIGN KEY (answer_id) REFERENCES mfl_poll_answers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per save of a set of rankings. snapshot_json holds the whole set, in
-- the same shape as the editor's form, so any row can be loaded back into it.
CREATE TABLE IF NOT EXISTS mfl_ranking_revisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ranking_id INT UNSIGNED NOT NULL,
  action VARCHAR(20) NOT NULL,                -- created, baseline, saved, published, unpublished
  user_name VARCHAR(80) NOT NULL,             -- copied, so it survives a removed account
  summary VARCHAR(255) NOT NULL DEFAULT '',
  snapshot_json MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ranking (ranking_id, id),
  FOREIGN KEY (ranking_id) REFERENCES mfl_rankings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What every NFL player scored each week under the league's scoring, rostered
-- or not. Rosters only cover players on a team, so a free agent's weeks live here.
CREATE TABLE IF NOT EXISTS mfl_player_weeks (
  season SMALLINT UNSIGNED NOT NULL,
  week TINYINT UNSIGNED NOT NULL,
  player_id VARCHAR(16) NOT NULL,
  points DECIMAL(7,2) NOT NULL,
  PRIMARY KEY (season, week, player_id),
  KEY idx_player (player_id, season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
