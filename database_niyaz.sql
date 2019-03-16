-- Each event can be either faiz or niyaz
ALTER TABLE events ADD niyaz boolean DEFAULT False NOT NULL;

-- Each RSVP may have
ALTER TABLE rsvps ADD adults int default 0 not null;
ALTER TABLE rsvps ADD kids int default 0 not null;

-- Thaali "available" renamed to apply to niyaaz also
UPDATE rsvps SET avail = False WHERE avail IS NULL;
UPDATE rsvps SET filled = False WHERE filled IS NULL;
ALTER TABLE rsvps CHANGE COLUMN avail here boolean DEFAULT False NOT NULL;
ALTER TABLE rsvps CHANGE COLUMN filled filled boolean DEFAULT False NOT NULL;
