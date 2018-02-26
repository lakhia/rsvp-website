
// For changes made to print page
// adding area column to table
// and updating area for specific rows

alter table family add  area varchar(255);

update family set area = "Sacramento"       where thaali between 300 and 314;
update family set area = "Lathrop"          where thaali in (10,11,22, 29, 45, 47 );
update family set area = "Mountain House"   where thaali in (18, 52, 53, 85);
update family set area = "Masjid"           where area is null;
update family set area = "Pleasanton"       where thaali in (12, 26, 42, 48, 56, 57, 60, 62, 64, 71, 72, 77, 90, 253,357, 359 );
update family set area = "San Bruno"        where thaali in (353, 351);
update family set area = "Novato"           where thaali in (352, 356);
update family set area = "San Francisco"    where thaali in (87, 69, 203, 205 ,213, 215);

alter table rsvps add column avail bool default 0;
alter table rsvps add column filled bool default 0;
alter table family add column resp varchar(3);

// Filling team members get responsibilty F
update family set resp = "F" where thaali in ( 1, 8, 7, 36, 28, 3, 92, 23, 37, 5, 13, 73, 43, 65, 19, 70, 20, 33, 9, 4, 21);
