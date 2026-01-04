-- Update size values in family table
UPDATE family SET size = 'LG' WHERE size = 'L';
UPDATE family SET size = 'MD' WHERE size = 'M';
UPDATE family SET size = 'SM' WHERE size = 'S';

-- Update size values in rsvps table
UPDATE rsvps SET size = 'LG' WHERE size = 'L';
UPDATE rsvps SET size = 'MD' WHERE size = 'M';
UPDATE rsvps SET size = 'SM' WHERE size = 'S';

-- Modify column to use ENUM with new values
ALTER TABLE family MODIFY COLUMN size ENUM('XL', 'LG', 'MD', 'SM', 'XS') NOT NULL;
ALTER TABLE rsvps MODIFY COLUMN size ENUM('XL', 'LG', 'MD', 'SM', 'XS') NOT NULL;
