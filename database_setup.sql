CREATE USER 'sffaiz'@'localhost' IDENTIFIED BY 'sffaiz-pass';
GRANT ALL ON *.* TO sffaiz@localhost;

create database sffaiz;
use sffaiz;

---------------
-- TABLE schema
---------------

CREATE TABLE family
  (
    thaali int NOT NULL PRIMARY KEY,
    lastName varchar(255) NOT NULL,
    firstName varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    phone varchar(255)
    -- bigThaali BOOLEAN,
    -- numAdults int,
    -- numChildren int,
  );

CREATE TABLE events
  (
    date DATE NOT NULL PRIMARY KEY,
    details varchar(255),       -- event name or menu
    enabled BOOLEAN DEFAULT true
  );

CREATE TABLE rsvps
  ( date DATE NOT NULL,
    thaali_id int NOT NULL,
    rsvp BOOLEAN NOT NULL DEFAULT false,
    -- lessRice BOOLEAN,
    -- numAdults int,
    -- numChildren int
    UNIQUE KEY `thaali_id` (`thaali_id`, `date`)
  );

---------------
-- Dummy data
---------------

insert into rsvps
  ( date, thaali_id, rsvp )
  values
  ( "2014-12-01", 36, 1 );

insert into rsvps
  ( date, thaali_id, rsvp )
  values
  ( "2014-12-02", 36, 0 )
  ON DUPLICATE KEY UPDATE
  rsvp=0;

insert into family
  ( thaali, lastName, firstName, email, phone )
  values
  ( 36, 'Lakhia', 'Ali Akber', 'lakhia@gmail.com', '510-565-7861' );
insert into family
  ( thaali, lastName, firstName, email, phone )
  values
  ( 5, 'Pedhiwala', 'Mohammed', 'mpedhiwala@gmail.com', '510-494-1520' );
insert into family
  ( thaali, lastName, firstName, email, phone )
  values
  ( 6, 'Bootwala', 'Mustafa', 'mabootwala@gmail.com', '650-676-8849' );
insert into family
  ( thaali, lastName, firstName, email, phone )
  values
  ( 7, 'Patanwala', 'Aliasgar', 'apatanwala@gmail.com', '650-276-8037' );
insert into family
  ( thaali, lastName, firstName, email, phone )
  values
  ( 8, 'Partapurwala', 'Murtaza', 'murtazap@gmail.com', '510-579-4909' );

insert into week
  ( date, details)
  values
  ( "2014-12-16", "Daal Gosht Chawal, Vegetable Tarkari" );
insert into week
  ( date, details)
  values
  ( "2014-12-17", "Chicken Tarkari, Dahi, Khitchri" );
insert into week
  ( date, details)
  values
  ( "2014-12-18", "Gosht Korma, Chawal, Fruit" );
insert into week
  ( date, details)
  values
  ( "2014-12-19", "Keema Patra, Daal Chawal" );
insert into week
  ( date, details)
  values
  ( "2014-12-20", "Chicken Tikka Masala, Khurdi, Khitchri" );
insert into week
  ( date, details)
  values
  ( "2014-12-21", "Khitchro" );
insert into week
  ( date, details)
  values
  ( "2014-12-22", "Kari Chawal, kheer" );
insert into week
  ( date, details)
  values
  ( "2014-12-23", "Sabzi tarkari, dal, chawal" );
insert into week
  ( date, details)
  values
  ( "2014-12-24", "Nihari, khitchri" );

