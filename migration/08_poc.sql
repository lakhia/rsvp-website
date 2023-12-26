-- Add POC for 100% thaali
ALTER TABLE family ADD poc VARCHAR(255) NOT NULL DEFAULT "";

UPDATE family SET poc = "Aliakber Y" WHERE poc = "" AND thaali in (37,46,54,58,59,73);
UPDATE family SET poc = "Alafiyaben F" WHERE poc = "" AND thaali in (43,50,101,102,108);
UPDATE family SET poc = "Aliasgar P" WHERE poc = "" AND thaali in (25,61,68,75,80,106,116,222,253);
UPDATE family SET poc = "Alibhai F" WHERE poc = "" AND thaali in (42,63,107,109);
UPDATE family SET poc = "Fatemaben G" WHERE poc = "" AND thaali in (2,5,9,10,19,20,23,27,28,32,38,55,62,70,79,86,89);
UPDATE family SET poc = "Fatemaben Y" WHERE poc = "" AND thaali in (16,74,87,104,111,209,216);
UPDATE family SET poc = "Mariaben D" WHERE poc = "" AND thaali in (69,91,97,110);
UPDATE family SET poc = "Mohammedbhai P" WHERE poc = "" AND thaali in (206);
UPDATE family SET poc = "Mufaddalbhai E" WHERE poc = "" AND thaali in (85,224);
UPDATE family SET poc = "Mufaddalbhai Q" WHERE poc = "" AND thaali in (225);
UPDATE family SET poc = "Murtazabhai I" WHERE poc = "" AND thaali in (56,65,78);
UPDATE family SET poc = "Murtazabhai P" WHERE poc = "" AND thaali in (1,6,11,26,90,96,113,204,217,226,353);
UPDATE family SET poc = "Mustafabhai G" WHERE poc = "" AND thaali in (66,82,88,214);
UPDATE family SET poc = "Mustafabhai H" WHERE poc = "" AND thaali in (14,17,30,81,94,95,99,105,115,213);
UPDATE family SET poc = "Quraishbhai D" WHERE poc = "" AND thaali in (83);
UPDATE family SET poc = "Rajbhai I" WHERE poc = "" AND thaali in (57,71,211);
UPDATE family SET poc = "Rashidaben E" WHERE poc = "" AND thaali in (48,255);
UPDATE family SET poc = "Saminaben P" WHERE poc = "" AND thaali in (3,4,7,8,13,15,24,31,49,67,100,254);
UPDATE family SET poc = "Tahabhai S" WHERE poc = "" AND thaali in (202,205,212,215,218,220,221,223);
UPDATE family SET poc = "Tasneemben B" WHERE poc = "" AND thaali in (72,98);
UPDATE family SET poc = "Tasneemben I" WHERE poc = "" AND thaali in (77,84,93,210);
UPDATE family SET poc = "Yusufbhai E" WHERE poc = "" AND thaali in (12,44,103,114);
UPDATE family SET poc = "Zainabben S" WHERE poc = "" AND thaali in (112);

