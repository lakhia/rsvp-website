DROP USER IF EXISTS 'sffaiz'@'localhost';
CREATE USER 'sffaiz'@'localhost' IDENTIFIED BY 'sffaiz-pass';
GRANT ALL ON *.* TO sffaiz@localhost;
GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP
  ON sffaiz.*
  TO 'sffaiz'@'localhost';

DROP DATABASE IF EXISTS sffaiz;
CREATE DATABASE sffaiz;
USE sffaiz;

--
-- TABLE schema
--

CREATE TABLE family
  (
    thaali int NOT NULL PRIMARY KEY,
    lastName varchar(255) NOT NULL,
    firstName varchar(255) NOT NULL,
    size char,
    email varchar(255) NOT NULL,
    phone varchar(255),
    area varchar(255)
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
    UNIQUE KEY `thaali_id` (`thaali_id`, `date`)
  );

--
-- Dummy data
-- Create dummy families
-- These can be replaced with real families
--
insert into family
  ( thaali, lastName, firstName, email, phone, area )
  values
  ( 1, 'Anonymous', 'Mumin bhai', 'randomemail@gmail.com', '000-000-0000' , 'Area1');
insert into family
  ( thaali, lastName, firstName, email, phone, area  )
  values
  ( 2, 'Anonymous', 'Mumina behen', 'randomemail@gmail.com', '000-000-0000', 'Area1' );

insert into events
  ( date, details)
  values
  ( "2016-12-16", "Daal Gosht Chawal, Vegetable Tarkari" );
insert into events
  ( date, details)
  values
  ( "2016-12-17", "Chicken Tarkari, Dahi, Khitchri" );
insert into events
  ( date, details)
  values
  ( "2016-12-18", "Gosht Korma, Chawal, Fruit" );
insert into events
  ( date, details)
  values
  ( "2016-12-19", "Keema Patra, Daal Chawal" );
insert into events
  ( date, details)
  values
  ( "2016-12-20", "Chicken Tikka Masala, Khurdi, Khitchri" );
insert into events
  ( date, details)
  values
  ( "2016-12-21", "Khitchro" );
insert into events
  ( date, details)
  values
  ( "2016-12-22", "Kari Chawal, kheer" );
insert into events
  ( date, details)
  values
  ( "2016-12-23", "Sabzi tarkari, dal, chawal" );
insert into events
  ( date, details)
  values
  ( "2016-12-24", "Nihari, khitchri" );
