-- Split adult RSVP counts by gender: mardo (men) and bairao (women).
-- adults is kept in the schema (and API payloads) for compatibility with
-- existing consumers; going forward it is derived as mardo + bairao.

ALTER TABLE rsvps ADD COLUMN mardo  int default 0 not null AFTER adults;
ALTER TABLE rsvps ADD COLUMN bairao int default 0 not null AFTER mardo;

-- Historic data predates the gender split — attribute all existing adults to mardo.
UPDATE rsvps SET mardo = adults WHERE adults > 0;
