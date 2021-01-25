# bring in server specific settings
include db.mk

SCHEMA = schema.sql
FUNCTIONS = functions.sql
POPULATE = populate.php
FIELDS = fields.json

clean : 
	cat $(SCHEMA) $(FUNCTIONS) | psql -h $(DBHOST) -p $(DBPORT) -U $(DBUSER) $(DBNAME)
	cat $(FIELDS) | php -f $(POPULATE) 