CREATE TABLE IF NOT EXISTS states (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS counties (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  state_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT counties_state_id_foreign FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS facilities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  county_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT facilities_county_id_foreign FOREIGN KEY (county_id) REFERENCES counties(id) ON DELETE SET NULL
);

ALTER TABLE patients ADD COLUMN IF NOT EXISTS state_id VARCHAR(255) NULL AFTER phone;
ALTER TABLE patients ADD COLUMN IF NOT EXISTS county_id VARCHAR(255) NULL AFTER state_id;
ALTER TABLE patients ADD COLUMN IF NOT EXISTS facility_id VARCHAR(255) NULL AFTER county_id;

INSERT INTO states (name, created_at, updated_at) VALUES
('Central Equatoria', NOW(), NOW()),
('Eastern Equatoria', NOW(), NOW()),
('Western Equatoria', NOW(), NOW()),
('Jonglei', NOW(), NOW()),
('Lakes', NOW(), NOW()),
('Unity', NOW(), NOW()),
('Upper Nile', NOW(), NOW()),
('Warrap', NOW(), NOW()),
('Northern Bahr el Ghazal', NOW(), NOW()),
('Western Bahr el Ghazel', NOW(), NOW()),
('Greater Pibor Administrative Area (GPAA)', NOW(), NOW()),
('Abyei Administrative Area', NOW(), NOW()),
('Ruweng Administrative Area', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO counties (state_id, name, created_at, updated_at) VALUES
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Budi County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Ikotos County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Kapoeta East County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Kapoeta North County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Kapoeta South County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Lafon County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Magwi County', NOW(), NOW()),
((SELECT id FROM states WHERE name = 'Eastern Equatoria'), 'Torit County', NOW(), NOW())
ON DUPLICATE KEY UPDATE state_id = VALUES(state_id), updated_at = VALUES(updated_at);

INSERT INTO facilities (county_id, name, created_at, updated_at) VALUES
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Nimule Hospital', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Pageri PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Abara PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Magwi PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Owinykibul PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Obbo PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Pajok PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Magwi County'), 'Lobone PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Torit County'), 'Torit State Hospital', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Torit County'), 'Nyong PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Torit County'), 'Hiyala PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Torit County'), 'St. Theresa Mission Hospital Isohe', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Kapoeta South County'), 'Kapoeta Civil Hospital', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Kapoeta South County'), 'Naknak PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Kapoeta North County'), 'Riwoto PHCC', NOW(), NOW()),
((SELECT id FROM counties WHERE name = 'Kapoeta East County'), 'Narus PHCC', NOW(), NOW())
ON DUPLICATE KEY UPDATE county_id = VALUES(county_id), updated_at = VALUES(updated_at);
