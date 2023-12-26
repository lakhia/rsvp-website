-- Add ITS number for 100% thaali
ALTER TABLE family ADD its VARCHAR(255) NOT NULL DEFAULT "";

ALTER TABLE family ADD CONSTRAINT its_id_unique UNIQUE (its, thaali);

