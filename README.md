
# Database Filler

#### Fill MySQL database tables with test data by parsing the SQL schema file.


## Purpose

+ Quickly populate database tables with row data for testing SQL retrieval.
+ Assist in testing complex database schema, before moving the database to a production environment.
+ Check table field population with specified datatypes, potential data truncation, visual cues etc.
+ Test database connection encoding and character encoding, and data insertion speeds.


## Background

Originally, I needed to populate a database with 14 complex tables. Tools such as Spawner are useful for small tables, but in this case, specifying the datatypes for 300+ fields to initiate Spawner would have been insanity.

Instead, why not parse the SQL schema?


## Database Requirements

1. The script expects the database schema to exist in MySQL (`mysql -u root -p < test.sql`).
2. **All table names** and **column names** in the MySQL schema **require back-ticks.**
3. **Unique keys must be removed** from tables when using the option **'random_data' => FALSE**


## Other

+ The majority of MySQL datatypes are supported.
+ Any foreign keys are disabled on data population.
+ Random character generation is slow in PHP, and such slowness further depends on field length, number of fields, and the number of rows being generated.
+ Multiple INSERTs are added in a single query, which is quite fast. Number of INSERTs per second will depend on MySQL configuration settings (default is not optimised), datatype / length inserted, operating system, hardware, etc.


## Set-up

Adjust the array connection details and parameters in *databasefiller_example.php* file.

Then execute the file with PHP on the command-line:

        php databasefiller_example.php

or run the file through a web server e.g.

        http://localhost/Database-Filler/databasefiller_example.php


## License

Database Filler is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
