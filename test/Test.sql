BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Test" (
	"Id"	INTEGER,
	"Method"	TEXT NOT NULL,
	"Uri"	TEXT NOT NULL,
	"JsonGetParameters"	TEXT NOT NULL,
	"JsonPostParameters"	TEXT,
	"JsonConnectedUser"	TEXT,
	"ExpectedResponseCode"	TEXT NOT NULL,
	"Query"	TEXT,
	"QueryExpectedResponse"	TEXT,
	PRIMARY KEY("Id")
);
INSERT INTO "Test" VALUES (2,'DELETE','/articles/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (3,'GET','/articles/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (4,'POST','/articles/@id:[0-9]+','{"id":1}','{"title":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (5,'GET','/publish/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (6,'POST','/publish/article/@id:[0-9]+','{"id":1}','{"isSpotlightActive":false}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (7,'GET','/dbbrowser/@table:[A-Za-z0-9_]+','{"table":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (8,'GET','/dbbrowser/@table:[A-Za-z0-9_]+/create','{"table":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (9,'POST','/dbbrowser/@table:[A-Za-z0-9_]+/create','{"table":"zz"}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (10,'GET','/dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+','{"table":"zz", "id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (11,'POST','/dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+','{"table":"zz", "id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (12,'DELETE','/dbbrowser/@table:[A-Za-z0-9_]+/delete/@id:[0-9]+','{"table":"zz", "id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (13,'GET','/emails/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (14,'GET','/events/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (15,'GET','/groups/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (16,'POST','/groups/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (17,'DELETE','/groups/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (18,'GET','/data/media/@year:[0-9]+/@month:[0-9]+/@filename','{"id":1,  "year":1, "month":1, "filename":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (19,'GET','/navBar/show/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (20,'GET','/persons/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (21,'POST','/persons/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (22,'DELETE','/persons/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (23,'GET','/registration/groups/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (24,'GET','/surveys/add/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (25,'GET','/surveys/results/@id:[0-9]+','{"id":1}',NULL,NULL,'401',NULL,NULL);
INSERT INTO "Test" VALUES (26,'GET','/user/forgotPassword/@encodedEmail','{"encodedEmail":"webmaster@myclub.foo"}',NULL,NULL,'500',NULL,NULL);
INSERT INTO "Test" VALUES (27,'GET','/user/setPassword/@token:[a-f0-9]+','{"token":"0123456789abcdef0123456789abcdef"}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (28,'POST','/user/setPassword/@token:[a-f0-9]+','{"token":"0123456789abcdef0123456789abcdef"}','{"password":"admin1234"}',NULL,'400',NULL,NULL);
INSERT INTO "Test" VALUES (29,'GET','/contact/event/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (31,'DELETE','/api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename','{"year":1, "month":1, "filename":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (32,'GET','/event/chat/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (33,'GET','/eventTypes/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (34,'POST','/eventTypes/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (35,'DELETE','/eventTypes/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (36,'GET','/presentation/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (37,'GET','/api/author/@articleId:[0-9]+','{"articleId":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (38,'GET','/api/surveys/reply/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (39,'GET','/api/carousel/@articleId:[0-9]+','{"articleId":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (43,'GET','/events/@id:[0-9]+/@token:[a-f0-9]+','{"id":1, "token":"0123456789abcdef0123456789abcdef"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (44,'GET','/events/@id:[0-9]+/unregister','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (45,'DELETE','/api/carousel/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (46,'DELETE','/api/attributes/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (47,'GET','/api/attributes-by-event-type/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (48,'DELETE','/api/event/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (49,'POST','/api/event/duplicate/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (50,'GET','/api/event/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (51,'GET','/api/event-needs/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (52,'DELETE','/api/needs/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (53,'DELETE','/api/needs/type/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (54,'GET','/api/needs-by-need-type/@id:[0-9]+','{"id":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (56,'DELETE','/api/navBar/deleteItem/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (57,'GET','/api/navBar/getItem/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (58,'GET','/api/personsInGroup/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (59,'POST','/api/registration/add/@personId:[0-9]+/@groupId:[0-9]+','{"personId":1, "groupId":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (60,'POST','/api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+','{"personId":1, "groupId":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (61,'GET','/events/@id:[0-9]+/register','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (62,'GET','/events/@id:[0-9]+/register','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (63,'POST','/designs/save','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (64,'POST','/emails','','{"dayOfWeek":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (65,'POST','/events/guest','','{"email":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (66,'POST','/groups/create','','{"name":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (67,'POST','/import','','{"csvFile":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (68,'POST','/presentation/edit','','{"content":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (69,'POST','/surveys/create','','{"question":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (70,'POST','/user/account','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (71,'POST','/user/availabilities','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (72,'POST','/user/groups','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (73,'POST','/user/preferences','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (74,'POST','/user/sign/in','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (75,'POST','/contact','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (76,'POST','/api/designs/vote','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (77,'POST','/arwards','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (78,'POST','/api/media/upload','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (79,'POST','/api/surveys/reply','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (80,'POST','/api/carousel/save','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (81,'POST','/api/attributes/create','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (82,'POST','/api/attributes/update','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (83,'POST','/api/needs/type/save','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (84,'POST','/api/message/add','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (85,'POST','/import/headers','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (86,'POST','/api/navBar/saveItem','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (87,'POST','/api/event/save','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (88,'POST','/api/event/updateSupply','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (89,'POST','/api/event/sendEmails','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (90,'POST','/api/needs/save','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (91,'POST','/api/message/update','','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (92,'POST','/api/navBar/updatePositions','','{"id":1}',NULL,'403',NULL,NULL);
COMMIT;
