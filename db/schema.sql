-- monitora details
DROP TABLE IF EXISTS communities CASCADE;
CREATE TABLE communities (
  community_id SERIAL PRIMARY KEY,
  community_string VARCHAR(32)
);

-- start of week
DROP TABLE IF EXISTS weeks CASCADE;
CREATE TABLE weeks (
  week_id SERIAL PRIMARY KEY,
  week DATE
);

-- questions with series, e.g. age ranges
DROP TABLE IF EXISTS series CASCADE;
CREATE TABLE series (
  series_id SERIAL PRIMARY KEY,
  series_string VARCHAR(64)
);

-- individual items in series, e.g. ages 18-24
DROP TABLE IF EXISTS series_items CASCADE;
CREATE TABLE series_items (
  item_id SERIAL PRIMARY KEY,
  series_id INTEGER,
  item_string VARCHAR(128),
  item_number INTEGER,
  FOREIGN KEY (series_id) REFERENCES series (series_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ID to group question together
DROP TABLE IF EXISTS meta_questions CASCADE;
CREATE TABLE meta_questions (
  meta_question_id SERIAL PRIMARY KEY
);

-- individual question
DROP TABLE IF EXISTS questions CASCADE;
CREATE TABLE questions (
  question_id SERIAL PRIMARY KEY,
  meta_question_id INTEGER,
  order_num INTEGER,
  item_id INTEGER DEFAULT NULL,
  question_string VARCHAR(64),
  repeats INTEGER DEFAULT 1,
  FOREIGN KEY (item_id) REFERENCES series_items (item_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (meta_question_id) REFERENCES meta_questions (meta_question_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- response to survey
DROP TABLE IF EXISTS responses CASCADE;
CREATE TABLE responses (
  response_id SERIAL PRIMARY KEY,
  community_id INTEGER,
  week_id INTEGER,
  time_stamp TIMESTAMP DEFAULT NOW(),
  FOREIGN KEY (community_id) REFERENCES communities (community_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (week_id) REFERENCES weeks (week_id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE (community_id, week_id)
);

-- answer to individual question in response to survey
DROP TABLE IF EXISTS answers CASCADE;
CREATE TABLE answers (
  response_id INTEGER,
  question_id INTEGER,
  repeat INTEGER,
  numeric_value NUMERIC,
  string_value TEXT,
  FOREIGN KEY (response_id) REFERENCES responses (response_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions (question_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- add communities
INSERT 
  INTO communities 
       (community_string)
VALUES ('Máncora'),
       ('Los Organos'),
       ('El Ñuro'),
       ('Cabo Blanco'),
       ('Talara'),
       ('Puerto Nuevo'),
       ('Yacila'),
       ('La Islilla'),
       ('La Tortuga'),
       ('Sechura'),
       ('Parachique'),
       ('Puerto Rico');