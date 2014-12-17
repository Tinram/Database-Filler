<?php

/**
* example to set-up and call DatabaseFiller class
* Martin Latter, 14/12/14
*/


require('databasefiller.class.php');

header('Content-Type: text/html; charset=utf-8');



/**
* configuration settings to pass to class
*/

$aConfiguration = array(

	# output type toggle
	'debug' => FALSE, # TRUE for verbose screen output and no DB insertion, FALSE for DB insertion

	# number of rows to insert
	'num_rows' => 10,
		// on Windows, with unoptimised my.ini settings, data throughput may present a 'MySQL server has gone away' error at approximately 1500 rows

	# database details
	'database' => 'dbfilltest',
	'username' => 'un',
	'password' => 'pw',

	# schema file
	'schema_file' => 'test.sql',

	# database connection encoding
	'encoding' => 'utf8',  # latin1 / utf8 etc

	# random data toggle - set to false for a much faster fixed character fill - but - no unique indexes permitted
	'random_data' => TRUE,

	# random character range: ASCII integer values
	'low_char' => 33,
	'high_char' => 127
);



$oDF = new DatabaseFiller($aConfiguration);

echo $oDF->displayMessages();

?>