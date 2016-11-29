
# Database Filler

####  Fill a multi-table MySQL database with test data by parsing the SQL schema file.


## Origin

I needed to test the population of a database with 14 complex tables. Tools such as Spawner are useful for small tables - but specifying the datatypes for so many fields (300+) before initiating Spawner would be too time-consuming.

Instead, why not parse the SQL schema?


## Purpose

1. Assist in the testing, editing, and data population of complex database schema, before moving the database to a production environment.
2. Test database connection encoding and character encoding, and data insertion speeds on different character encodings.
3. Check table field population with specified datatype, data truncation, visual cues etc.


## Database Requirements

1. The script expects the database schema to exist in MySQL (`mysql -u root -p < test.sql`).
2. **All table names** and **column names** in the MySQL schema **require back-ticks.**
3. **Unique keys must be removed** from tables when using the option **'random_data' => FALSE**


## Other

- Any foreign keys are disabled on data population.
- The majority of MySQL datatypes are supported.
- Random character generation is slow in PHP, and further depends on field length, number of fields, and the number of rows being generated.
- The multiple INSERTs are added in a single query, which is quite fast. Number of INSERTs per second will depend on MySQL configuration settings (default is not optimised), datatype/length inserted, operating system, hardware, etc.


## Execution

`http://localhost/Database-Filler/databasefiller_example.php`

(Or execute on the command-line with: `php -f databasefiller_example.php` (remove HTML tags from  *databasefiller.class.php*) )


## License

Database Filler is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
