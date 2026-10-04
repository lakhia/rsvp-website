--
-- Column renames and id improvements
--
-- rsvps.thaali_id → rsvps.thaali, to match family.thaali
-- rsvps.lessRice → rsvps.norice
-- menus.id and ingredients.id become AUTO_INCREMENT

ALTER TABLE rsvps RENAME COLUMN thaali_id TO thaali;
ALTER TABLE rsvps RENAME INDEX thaali_id TO thaali;

ALTER TABLE rsvps RENAME COLUMN lessRice TO norice;

ALTER TABLE menus MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE ingredients MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE menus ADD UNIQUE (menu);
CREATE INDEX idx_ingredients_name ON ingredients (name);

-- Date-scoped queries in print, shop, export, generate_labels, and clean
-- filter by date without thaali, so they cannot use the (thaali, date) unique key.
CREATE INDEX idx_rsvps_date ON rsvps (date);

-- cooking: FK constraints so MySQL enforces referential integrity.
-- ON DELETE CASCADE lets a menu or ingredient deletion clean up cooking rows
-- automatically (measure.php already does this manually; the FK is additive).
ALTER TABLE cooking
  ADD CONSTRAINT fk_cooking_menu       FOREIGN KEY (menu_id)   REFERENCES menus(id)       ON DELETE CASCADE,
  ADD CONSTRAINT fk_cooking_ingredient FOREIGN KEY (ingred_id) REFERENCES ingredients(id) ON DELETE CASCADE;
