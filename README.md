Database Filler
===========

####  Fill a multi-table MySQL database with junk data through the parsing of the MySQL schema file.

### Origin

I needed to test the population of a database with 14 complex tables. Tools such as Spawner are good on small tables - but specifying the datatypes on so many fields before initiating Spawner was too time-consuming.  Instead, why not parse the SQL schema?

### Purposes
1. Aid the construction and testing of large database schema.
2. Test database connection encoding and character encoding, and data insert speeds on different character encodings.
3. Check table field population with specified datatype, data truncation, visual cues etc.

### Requirements
1. Script expects database schema to exist in MySQL (*mysql -u root -p < test.sql*).
2. **All table names and column names in the MySQL schema require back-ticks**

### Other
- Any foreign keys are disabled on data population.
- Some SQL comments may need stripping from schema for correct column name parsing.
- Random character generation is slow in PHP, and further depends on field length and the number of rows being generated.
