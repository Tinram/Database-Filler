
# Database Filler

#### Populate MySQL database tables with test data by parsing the SQL schema file.

## Purpose

### Primary

+ Database table cloning without using any real or sensitive data:
    + Clone of production database tables required for query analysis, yet sensitive data cannot be imported. However, you possess a structure-only schema file.

### Secondary

+ Schema design and development:
    + Check table field population with specified datatypes, potential data truncation, etc.
    + Test connection encoding and character encoding, and data insertion speeds.

<br>

[1]: https://tinram.github.io/images/databasefiller-data.png
![Database-Filler database][1]

<br>

## Background

Originally, I needed to populate a database containing 14 complex tables. Tools such as Spawner are useful for populating small tables, but in this case, specifying the datatypes for 300+ fields to initiate Spawner would have been insanity.

Instead, why not parse the SQL schema?


[2]: https://tinram.github.io/images/databasefiller-execute.png
![Database-Filler execute][2]


## Database Requirements

1. The script expects the database schema to already exist in MySQL (`mysql -u root -p < test.sql`).
2. **All table names** and **column names** in the MySQL schema **require back-ticks.**
3. **Unique keys must be removed** from tables when using the option **'random_data' => FALSE**


## Other

+ The majority of MySQL datatypes are supported.
+ Any foreign keys are disabled on data population.
+ Random character generation is slow in PHP, and such slowness further depends on field length, number of fields, and the number of rows being generated.
+ Multiple INSERTs are added in a single query, which is quite fast. Number of INSERTs per second will depend on MySQL configuration settings (the defaults are not optimised), datatype / length inserted, system load, operating system, hardware etc.


## Set-up

Ensure the database already exists in MySQL  
e.g. for the test schema:

```bash
    mysql -u root -p < test.sql
```

Adjust the array connection details and parameters in the file *databasefiller_example.php*

Then execute this file with PHP on the command-line:

```bash
    php databasefiller_example.php
```

*or* run the file through a web server e.g.

        http://localhost/Database-Filler/databasefiller_example.php


## License

Database Filler is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
