-- Make "M" the default for any family where size is not specified
UPDATE family SET size = 'M' WHERE size IS NULL;
UPDATE family SET size = 'M' WHERE size = '';

-- Use 2 characters for size
ALTER TABLE family MODIFY COLUMN size VARCHAR(2) NOT NULL;
-- Added size for RSVP
ALTER TABLE rsvps ADD size VARCHAR(2) NOT NULL;
