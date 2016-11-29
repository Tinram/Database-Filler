<?php

/**
	* Example to set-up and call databasefiller.class.php
	* Martin Latter, 14/12/14
*/


date_default_timezone_set('Europe/London');

ini_set('memory_limit', '256M'); # for inserting a large number of rows ($aConfiguration['num_rows'])


require('databasefiller.class.php');

header('Content-Type: text/html; charset=utf-8');



/**
	* Configuration settings to pass to class.
*/

$aConfiguration = array(

	# output type toggle
	'debug' => FALSE, # TRUE for verbose screen output and no DB insertion, FALSE for DB insertion

	# number of rows to insert
	'num_rows' => 10,
		// optimise mysqld variables in my.cnf/my.ini files when inserting a large number (i.e. 50000) of rows

	# database details
	'database' => 'dbfilltest',
	'username' => 'USERNAME',
	'password' => 'PASSWORD',

	# schema file
	'schema_file' => 'test.sql',

	# database connection encoding
	'encoding' => 'utf8', # latin1 / utf8 etc

	# random data toggle - set to false for a much faster fixed character fill - but - no unique indexes permitted
	'random_data' => TRUE,

	# random character range: ASCII integer values
	'low_char' => 33,
	'high_char' => 127
);



$oDF = new DatabaseFiller($aConfiguration);

echo $oDF->displayMessages();

?>