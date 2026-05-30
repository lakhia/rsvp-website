-- Allow multiple events per calendar date.
-- events: (date, event_index) replaces date as primary key.
-- rsvps:  event_index added, unique key updated to (thaali, date, event_index).
-- All existing rows default to event_index = 0.

ALTER TABLE events DROP PRIMARY KEY;
ALTER TABLE events ADD COLUMN event_index TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER date;
ALTER TABLE events ADD PRIMARY KEY (date, event_index);

ALTER TABLE rsvps ADD COLUMN event_index TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER date;
ALTER TABLE rsvps DROP INDEX thaali_id;
ALTER TABLE rsvps ADD UNIQUE KEY thaali_date_event (thaali_id, date, event_index);

-- Rename thaali_id to thaali to match family.thaali PK.
ALTER TABLE rsvps RENAME COLUMN thaali_id TO thaali;

-- Misc schema improvements
ALTER TABLE menus MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE ingredients MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE menus ADD UNIQUE (menu);
CREATE INDEX idx_ingredients_name ON ingredients (name);

-- rsvps: index on (date, event_index) for date-scoped queries in print, shop,
--   export, generate_labels, and clean — which filter by date without thaali
--   and cannot use the existing thaali_date_event unique key.
CREATE INDEX idx_rsvps_date_event ON rsvps (date, event_index);

-- cooking: FK constraints so MySQL enforces referential integrity.
--   ON DELETE CASCADE lets a menu or ingredient deletion clean up cooking rows
--   automatically (measure.php already does this manually; the FK is additive).
ALTER TABLE cooking
  ADD CONSTRAINT fk_cooking_menu       FOREIGN KEY (menu_id)   REFERENCES menus(id)       ON DELETE CASCADE,
  ADD CONSTRAINT fk_cooking_ingredient FOREIGN KEY (ingred_id) REFERENCES ingredients(id) ON DELETE CASCADE;

-- Rename lessRice to norice for consistency.
ALTER TABLE rsvps RENAME COLUMN lessRice TO norice;
