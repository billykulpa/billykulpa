-- billykulpa.com — schema
-- MySQL / MariaDB, utf8mb4

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per editable page. h1, meta_title, meta_description are CMS-editable.
CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,      -- '' = home, 'about', 'work', 'notes'
  label VARCHAR(190) NOT NULL,            -- shown in admin list
  h1 VARCHAR(255) NOT NULL DEFAULT '',
  lede TEXT NULL,
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
('work/supporting-local-music', 'Work / Local Music',
 'Supporting Local Music',
 'Supporting Local Music — Billy Kulpa',
 'Concert posters, cassette packaging, and other work from the Rockford, Illinois music scene.'),
('notes', 'Notes',
 'Notes',
 'Notes — Billy Kulpa',
 'Writing on design systems, creative leadership, and building things by hand.')
ON DUPLICATE KEY UPDATE label = VALUES(label);
