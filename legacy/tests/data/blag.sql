CREATE TABLE bacon_blag (id INTEGER NOT NULL PRIMARY KEY ASC AUTOINCREMENT, content TEXT, title TEXT, created TIMESTAMP, comments_enable TEXT);
CREATE TABLE "bacon_comment" (id INTEGER NOT NULL PRIMARY KEY ASC AUTOINCREMENT, content TEXT, blag_id INTEGER, author TEXT, approved TEXT);
