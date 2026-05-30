-- Allow multiple events per calendar date.
-- events: (date, event_index) replaces date as primary key.
-- rsvps:  event_index added, unique key updated to (thaali_id, date, event_index).
-- All existing rows default to event_index = 0.

ALTER TABLE events DROP PRIMARY KEY;
ALTER TABLE events ADD COLUMN event_index TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER date;
ALTER TABLE events ADD PRIMARY KEY (date, event_index);

ALTER TABLE rsvps ADD COLUMN event_index TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER date;
ALTER TABLE rsvps DROP INDEX thaali_id;
ALTER TABLE rsvps ADD UNIQUE KEY thaali_date_event (thaali_id, date, event_index);
