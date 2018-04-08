CREATE TABLE menus
  (
    id int NOT NULL PRIMARY KEY,
    menu varchar(255) NOT NULL,
    rice BOOLEAN NOT NULL DEFAULT false
  );

CREATE TABLE ingredients
  (
    id int NOT NULL primary key,
    name varchar(255) NOT NULL,
    unit varchar(10) NOT NULL
  );

CREATE TABLE cooking
  (
    menu_id int NOT NULL,
    ingred_id int NOT NULL,
    multiplier float NOT NULL,
    PRIMARY KEY(menu_id, ingred_id)
  );

insert into menus values(1,  "Akhni Pulao", 1);
insert into menus values(2,  "Butter Chicken", 0);
insert into menus values(3,  "Chawal", 1);
insert into menus values(4,  "Chicken Biryani", 1);
insert into menus values(5,  "Chicken Manchurian", 0);
insert into menus values(6,  "Chicken Tarkari", 0);
insert into menus values(7,  "Chicken Tikka Masala", 0);
insert into menus values(8,  "Daal Chawal", 1);
insert into menus values(9,  "Daal Gosht", 0);
insert into menus values(10, "Naan", 0);
insert into menus values(11, "Gosht Tarkari", 0);
insert into menus values(12, "Kadhi", 0);
insert into menus values(13, "Kadi", 0);
insert into menus values(14, "Keema Tarkari", 0);
insert into menus values(15, "Khitchdi", 1);
insert into menus values(16, "Khurdi", 0);
insert into menus values(17, "Masoor Daal", 0);
insert into menus values(18, "Masoor Pulao", 1);
insert into menus values(19, "Matho", 0);
insert into menus values(20, "Nihari", 0);
insert into menus values(21, "Palidu", 0);
insert into menus values(22, "Veg Fried Rice", 1);
--    insert into menus values(10, "Daal", 0);

insert into ingredients values(1, "rice", "cups");
insert into ingredients values(2, "boneless chicken", "lbs");
insert into ingredients values(3, "keema", "lbs");
insert into ingredients values(4, "gosht", "lbs");
insert into ingredients values(10, "naan", "pieces");

--    insert into ingredients values(3, "mong daal", "cups");

# Menu ID, Ingre ID, Multiplier
insert into cooking values (1, 1, 0.25);
insert into cooking values (3, 1, 0.3);
insert into cooking values (4, 1, 0.2);
insert into cooking values (4, 2, 0.15);
insert into cooking values (10, 10, 2);
insert into cooking values (14, 3, 0.6);
