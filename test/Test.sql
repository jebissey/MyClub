BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Test" (
	"Id"	INTEGER,
	"Step"	INTEGER,
	"Method"	TEXT NOT NULL,
	"Uri"	TEXT NOT NULL,
	"JsonGetParameters"	TEXT,
	"JsonPostParameters"	TEXT,
	"JsonConnectedUser"	TEXT,
	"ExpectedResponseCode"	TEXT NOT NULL,
	"Query"	TEXT,
	"QueryExpectedResponse"	TEXT,
	PRIMARY KEY("Id")
);
INSERT INTO "Test" VALUES (2,NULL,'DELETE','/articles/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (3,NULL,'GET','/articles/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (4,NULL,'POST','/articles/@id:[0-9]+','{"id":1}','{"title":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (5,NULL,'GET','/publish/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (6,NULL,'POST','/publish/article/@id:[0-9]+','{"id":1}','{"isSpotlightActive":false}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (7,NULL,'GET','/dbbrowser/@table:[A-Za-z0-9_]+','{"table":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (8,NULL,'GET','/dbbrowser/@table:[A-Za-z0-9_]+/create','{"table":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (9,NULL,'POST','/dbbrowser/@table:[A-Za-z0-9_]+/create','{"table":"zz"}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (10,NULL,'GET','/dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+','{"table":"zz", "id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (11,NULL,'POST','/dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+','{"table":"zz", "id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (12,NULL,'DELETE','/dbbrowser/@table:[A-Za-z0-9_]+/delete/@id:[0-9]+','{"table":"zz", "id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (13,NULL,'GET','/emails/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (14,NULL,'GET','/events/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (15,NULL,'GET','/groups/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (16,NULL,'POST','/groups/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (17,NULL,'DELETE','/groups/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (18,NULL,'GET','/data/media/@year:[0-9]+/@month:[0-9]+/@filename','{"id":1,  "year":1, "month":1, "filename":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (19,NULL,'GET','/navBar/show/article/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (20,NULL,'GET','/persons/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (21,NULL,'POST','/persons/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (22,NULL,'DELETE','/persons/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (23,NULL,'GET','/registration/groups/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (24,NULL,'GET','/surveys/add/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (25,NULL,'GET','/surveys/results/@id:[0-9]+','{"id":1}',NULL,NULL,'401',NULL,NULL);
INSERT INTO "Test" VALUES (26,NULL,'GET','/user/forgotPassword/@encodedEmail','{"encodedEmail":"webmaster@myclub.foo"}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (27,NULL,'GET','/user/setPassword/@token:[a-f0-9]+','{"token":"0123456789abcdef0123456789abcdef"}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (28,NULL,'POST','/user/setPassword/@token:[a-f0-9]+','{"token":"0123456789abcdef0123456789abcdef"}','{"password":"admin1234"}',NULL,'400',NULL,NULL);
INSERT INTO "Test" VALUES (29,NULL,'GET','/contact/event/@id:[0-9]+','{"id":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (31,NULL,'DELETE','/api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename','{"year":1, "month":1, "filename":"zz"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (32,NULL,'GET','/event/chat/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (33,NULL,'GET','/eventTypes/edit/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (34,NULL,'POST','/eventTypes/edit/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (35,NULL,'DELETE','/eventTypes/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (36,NULL,'GET','/presentation/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (37,NULL,'GET','/api/author/@articleId:[0-9]+','{"articleId":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (38,NULL,'GET','/api/surveys/reply/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (39,NULL,'GET','/api/carousel/@articleId:[0-9]+','{"articleId":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (43,NULL,'GET','/events/@id:[0-9]+/@token:[a-f0-9]+','{"id":1, "token":"0123456789abcdef0123456789abcdef"}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (44,NULL,'GET','/events/@id:[0-9]+/unregister','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (45,NULL,'DELETE','/api/carousel/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'401',NULL,NULL);
INSERT INTO "Test" VALUES (46,NULL,'DELETE','/api/attributes/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (47,NULL,'GET','/api/attributes-by-event-type/@id:[0-9]+','{"id":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (48,NULL,'DELETE','/api/event/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (49,NULL,'POST','/api/event/duplicate/@id:[0-9]+','{"id":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (50,NULL,'GET','/api/event/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (51,NULL,'GET','/api/event-needs/@id:[0-9]+','{"id":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (52,NULL,'DELETE','/api/needs/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (53,NULL,'DELETE','/api/needs/type/delete/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (54,NULL,'GET','/api/needs-by-need-type/@id:[0-9]+','{"id":1}',NULL,NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (56,NULL,'DELETE','/api/navBar/deleteItem/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (57,NULL,'GET','/api/navBar/getItem/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (58,NULL,'GET','/api/personsInGroup/@id:[0-9]+','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (59,NULL,'POST','/api/registration/add/@personId:[0-9]+/@groupId:[0-9]+','{"personId":1, "groupId":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (60,NULL,'POST','/api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+','{"personId":1, "groupId":1}','{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (61,NULL,'GET','/events/@id:[0-9]+/register','{"id":1}',NULL,NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (63,NULL,'POST','/designs/save',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (64,NULL,'POST','/emails',NULL,'{"dayOfWeek":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (65,NULL,'POST','/events/guest',NULL,'{"email":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (66,NULL,'POST','/groups/create',NULL,'{"name":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (67,NULL,'POST','/import',NULL,'{"csvFile":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (68,NULL,'POST','/presentation/edit',NULL,'{"content":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (69,NULL,'POST','/surveys/create',NULL,'{"question":"zz"}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (70,NULL,'POST','/user/account',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (71,NULL,'POST','/user/availabilities',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (72,NULL,'POST','/user/groups',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (73,NULL,'POST','/user/preferences',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (74,NULL,'POST','/user/sign/in',NULL,'{"id":1}',NULL,'400',NULL,NULL);
INSERT INTO "Test" VALUES (75,NULL,'POST','/contact',NULL,'{"name":"zz","email":"user@myclub.foo", "message":"zz"}',NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (76,NULL,'POST','/api/designs/vote',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (77,NULL,'POST','/arwards',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (78,NULL,'POST','/api/media/upload',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (79,NULL,'POST','/api/surveys/reply',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (80,NULL,'POST','/api/carousel/save',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (81,NULL,'POST','/api/attributes/create',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (82,NULL,'POST','/api/attributes/update',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (83,NULL,'POST','/api/needs/type/save',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (84,NULL,'POST','/api/message/add',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (85,NULL,'POST','/import/headers',NULL,'{"id":1}',NULL,'200',NULL,NULL);
INSERT INTO "Test" VALUES (86,NULL,'POST','/api/navBar/saveItem',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (87,NULL,'POST','/api/event/save',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (88,NULL,'POST','/api/event/updateSupply',NULL,'{"id":1}',NULL,'401',NULL,NULL);
INSERT INTO "Test" VALUES (89,NULL,'POST','/api/event/sendEmails',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (90,NULL,'POST','/api/needs/save',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (91,NULL,'POST','/api/message/update',NULL,'{"id":1}',NULL,'403',NULL,NULL);
INSERT INTO "Test" VALUES (92,NULL,'POST','/api/navBar/updatePositions',NULL,'{"id":1}',NULL,'403',NULL,NULL);
COMMIT;
