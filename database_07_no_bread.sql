INSERT INTO menus VALUES (28, "Paratha", 1);
INSERT INTO menus VALUES (29, "Roti", 1);
INSERT INTO menus VALUES (30, "Garlic Bread", 1);
INSERT INTO menus VALUES (31, "Mini Naan", 1);
INSERT INTO menus VALUES (32, "Home-style Paratha", 1);
INSERT INTO menus VALUES (33, "French Bread", 1);

UPDATE menus SET menu = "Afghani Naan", rice = 1 WHERE menu = "Naan";
UPDATE menus SET rice = 1 WHERE menu = "Pav";

INSERT INTO ingredients VALUES (8, "paratha", "pieces");
INSERT INTO ingredients VALUES (12, "bread", "whole");
INSERT INTO ingredients VALUES (40, "serving", "serving");

INSERT INTO cooking VALUES (3, 40, 1);
INSERT INTO cooking VALUES (28, 8, 4);
INSERT INTO cooking VALUES (30, 12, 0.5);
INSERT INTO cooking VALUES (31, 10, 4);
INSERT INTO cooking VALUES (32, 10, 4);
INSERT INTO cooking VALUES (33, 12, 0.333);

UPDATE family SET area = replace(area , 'Group ','');
