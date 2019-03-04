-- Each event can be either faiz or niyaz
alter table events add niyaz boolean default false;

-- Each RSVP may have
alter table rsvps add adults int default 0;
alter table rsvps add kids int default 0;

-- Thaali "available" renamed to apply to niyaaz also
alter table rsvps CHANGE COLUMN avail here boolean;
