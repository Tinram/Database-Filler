<?php

class DatabaseFiller {

	/**
	*
	* Fill a multi-table MySQL database with junk data through the parsing of the MySQL schema file.
	*
	*
	* origin:
	*                  I needed to test the population of a database with 14 complex tables. Tools such as Spawner are good on small tables -
	*                  - but specifying the datatypes on so many fields before using Spawner was too time-consuming.  Instead, why not parse the SQL schema?
	*
	* purposes:
	*                  1) Assist in the testing, editing, and data population of complex database schema, before moving the database to a production environment.
	*                  2) Test database connection encoding and character encoding, and data insert speeds on different character encodings.
	*                  3) Check table field population with specified datatype, data truncation, visual cues etc.
	*
	* requirements:
	*                  1) Script expects database schema to exist in MySQL (mysql -u root -p < test.sql).
	*                  2) ** All table names and column names in the MySQL schema require back-ticks. **
	*
	* other:
	*                  Any foreign keys are disabled on data population.
	*                  Some comments may need stripping from schema for correct column name parsing.
	*                  Random character generation is slow in PHP, and further depends on field length and the number of rows being generated.
	*                  Class could be altered to parse SHOW CREATE TABLE from MySQL directly.
	*
	* @author          Martin Latter <copysense.co.uk>
	* @copyright       Martin Latter 13/12/2014
	* @version         0.30
	* @license         GNU GPL v3.0
	* @link            https://github.com/Tinram/Database-Filler.git
	*
	*/


	private

		# CONFIGURATION DEFAULTS

		# output toggle
		$bDebug = FALSE, # TRUE: verbose screen output and no DB insertion; FALSE: database query insertion

		# number of rows to insert
		$iNumRows = 1,

		# schema file
		$sSchemaFile = NULL,

		# DB connection encoding
		$sEncoding = 'utf8',

		# random character range
		$iLowChar = 33,
		$iHighChar = 127,

		# random data generator toggle
		$bRandomData = TRUE, # FALSE = a much faster fixed character fill (unsuitable with unique indexes, and SET unique_checks = 0 is not sufficient)

		##########################

		$oConnection = FALSE,
		$bActiveConnection = FALSE,

		$aMessages = array();


	public function __construct(array $aConfig) {

		/**
		* set-up configuration class variables, establish DB connection if no debug configuration option set
		*/

		if ( ! isset($aConfig['schema_file'])) {
			die('No schema file specified in the configuration array.');
		}

		if (isset($aConfig['debug'])) {
			$this->bDebug = $aConfig['debug'];
		}

		if (isset($aConfig['num_rows'])) {
			$this->iNumRows = (int) $aConfig['num_rows'];
		}

		if (isset($aConfig['random_data'])) {
			$this->bRandomData = $aConfig['random_data'];
		}

		if (isset($aConfig['low_char'])) {
			$this->iLowChar = (int) $aConfig['low_char'];
		}

		if (isset($aConfig['high_char'])) {
			$this->iHighChar = (int) $aConfig['high_char'];
		}

		if ( ! $this->bDebug) {

			if ( ! isset($aConfig['database']) || ! isset($aConfig['username']) || ! isset($aConfig['password'])) {

				$this->aMessages[] = 'Database connection details have not been fully specified in the configuration array.';
				return;
			}

			if (isset($aConfig['encoding'])) {
				$this->sEncoding = $aConfig['encoding'];
			}

			$this->oConnection = new mysqli('localhost', $aConfig['username'], $aConfig['password'], $aConfig['database']);

			if ( ! $this->oConnection->connect_errno) {

				$this->oConnection->set_charset($this->sEncoding);
				$this->bActiveConnection = TRUE;
			}
			else {

				$this->aMessages[] = 'Database connection failed: ' . $this->oConnection->connect_error . ' (error number: ' . $this->oConnection->connect_errno . ')';
				return;
			}
		}

		$this->parseSQLFile($aConfig['schema_file']);

	} # end __construct()


	public function __destruct() {

		/**
		* close DB connection if active
		*/

		if ($this->bActiveConnection) {
			$this->oConnection->close();
		}

	} # end __destruct()


	private function parseSQLFile($sFileName) {

		/**
		* parse SQL file to extract table schema
		*
		* @param    string $sFileName, schema filename
		*/

		$aTableHolder = array();
		$aMatch = array();

		# parse SQL schema
		$sFile = file_get_contents($sFileName);

		# find number of instances of 'CREATE TABLE'
		preg_match_all('/CREATE TABLE/', $sFile, $aMatch);

		# create array of table info
		for ($i = 0, $iOffset = 0, $n = sizeof($aMatch[0]); $i < $n; $i++) {

			if ( ! $iOffset) {

				$iStart = stripos($sFile, 'CREATE TABLE');
				$iEnd = stripos($sFile, 'ENGINE=');
			}
			else {

				$iStart = stripos($sFile, 'CREATE TABLE', $iEnd);
				$iEnd = stripos($sFile, 'ENGINE=', $iStart);
			}

			$sTable = substr($sFile, $iStart, ($iEnd - $iStart));

			$iOffset = $iEnd;

			$aTableHolder[] = $sTable;
		}

		# send each table string for processing
		foreach ($aTableHolder as $sTable) {
			$this->processSQLTable($sTable);
		}

	} # end parseSQLFile()


	private function processSQLTable($sTable) {

		/**
		* process each table schema
		*
		* @param    string $sTable, table schema string
		*/

		static $iCount = 1;

		$fD1 = microtime(TRUE);

		$aDBFieldAttr = array();
		$aRXResults = array();
		$aFields = array();
		$aValues = array();

		$aLines = explode(',', $sTable);

		# get table name
		preg_match('/`([a-zA-Z0-9\-_]+)`/', $aLines[0], $aRXResults);
		$sTableName = $aRXResults[1];

		# extract field attributes
		foreach ($aLines as $sLine) {

			$aTemp = $this->findField($sLine);

			if ( ! is_null($aTemp)) {
				$aDBFieldAttr[] = $aTemp;
			}
		}
		##

		# create SQL query field names
		foreach ($aDBFieldAttr as $aRow) {
			$aFields[] = '`' . $aRow['fieldName'] . '`';
		}
		##

		# create SQL query value sets
		for ($i = 0; $i < $this->iNumRows; $i++) {

			$aTemp = array(); # reset

			# generate random data for fields, dependent on datatype
			foreach ($aDBFieldAttr as $aRow) {

				if ($aRow['type'] === 'string') {

					$iLen = (int) $aRow['length'];

					if ( ! $iLen) {
						$iLen = 255;
					}

					$s = '';

					if ($this->bRandomData) {

						for ($j = 0; $j < $iLen; $j++) {

							$c = chr(mt_rand($this->iLowChar, $this->iHighChar));

							if ($c === '<' || $c === '>') { # < and > are corrupting symbols
								$c = 'Z';
							}

							$s .= $c;
						}
					}
					else {

						for ($j = 0; $j < $iLen; $j++) {
							$s .= 'X';
						}
					}

					$aTemp[] = '"' . addslashes($s) . '"';
				}
				else if ($aRow['type'] === 'int') {
				
					# will need further adjustment for some values of smallint, mediumint

					if ($this->bRandomData) {
						$iLen = (int) $aRow['length'];
					}
					else {
						$iLen = (int) $aRow['length'] - 1; # -1 to avoid overflow on INTs on fixed data
					}

					$s = '';

					for ($j = 0; $j < $iLen; $j++) {
						$s .= 9;
					}

					$iMax = (int) $s;

					if ($this->bRandomData) {
						$iNum = mt_rand(0, $iMax);
					}
					else {
						$iNum = $iMax;
					}

					$aTemp[] = $iNum;
				}
				else if ($aRow['type'] === 'decimal' || $aRow['type'] === 'float') {

					# compromise dealing with decimals and floats

					if ($aRow['type'] === 'decimal') {
						$iLen = ((int) $aRow['length']) - 3;
					}
					else {
						$iLen = (int) $aRow['length'];
					}

					$s = '';

					for ($j = 0; $j < $iLen; $j++) {
						$s .= 9;
					}

					$iMax = (int) $s;

					if ($this->bRandomData) {

						$iNum = mt_rand(0, $iMax);
						$iUnits = mt_rand(0, 99);
					}
					else {

						$iNum = $s;
						$iUnits = '50';
					}

					if ($aRow['type'] === 'decimal') {
						$aTemp[] = '"' . $iNum . '.' . $iUnits . '"';
					}
					else if ($aRow['type'] === 'float') {
						$aTemp[] = lcg_value() * $iMax;
					}
				}
				else if ($aRow['type'] === 'date') {

					$aTemp[] = '"' . date('Y-m-d') . '"'; 
				}
				else if ($aRow['type'] === 'datetime') {

					$aTemp[] = '"' . date('Y-m-d H:i:s') . '"'; 
				}
				else if ($aRow['type'] === 'time') {

					$aTemp[] = '"' . date('H:i:s') . '"'; 
				}
			}

			$aValues[] = '(' . join(',', $aTemp) . ')';
		}
		##

		$fD2 = microtime(TRUE);
		$this->aMessages[] = __METHOD__ . '() iteration <b>' . $iCount . '</b> :: ' . sprintf('%01.6f sec', $fD2 - $fD1);

		if ($this->bDebug) {

			$this->aMessages[] = var_dump($aFields);
			$this->aMessages[] = var_dump($aValues);
		}

		# create SQL query string
		$sInsert = 'INSERT INTO ' . $sTableName . ' ';
		$sInsert .= '(' . join(',', $aFields) . ') ';
		$sInsert .= 'VALUES ' . join(',', $aValues);

		if ($this->bDebug) {
			$this->aMessages[] = $sInsert . '<br>';
		}
		##

		# send SQL to database
		if ( ! $this->bDebug) {

			$this->oConnection->query('SET foreign_key_checks = 0');

			if ($this->iNumRows > 1500) { # MySQL server's my.cnf file will need optimising for inserting more than ~1500 rows at once 
				$this->oConnection->query('SET max_allowed_packet = 128M');
			}

			$fT1 = microtime(TRUE);
			$this->oConnection->query($sInsert);
			$fT2 = microtime(TRUE);

			$this->aMessages[] = 'attempted to add ' . $this->iNumRows . ' rows of random data to table <b>' . $sTableName . '</b>'; 
			$this->aMessages[] = 'SQL insertion: ' . sprintf('%01.6f sec', $fT2 - $fT1);
		}
		##

		$iCount++;

	} # end processSQLTable()


	private function findField($sLine) {

		/**
		* extract field data from schema line
		*
		* @param    string $sTable, table schema string
		* @return   array ( 'fieldName' => $v, 'type' => $v, 'length' => $v )
		*/

		static $aTypes = array(

			'INT' => 'int',
			'TINYINT' => 'int',
			'SMALLINT' => 'int',
			'MEDIUMINT' => 'int',
			'BIGINT' => 'int',

			'DECIMAL' => 'decimal',
			'FLOAT' => 'float',
			'DOUBLE' => 'float',

			'CHAR' => 'string',
			'VARCHAR' => 'string',

			'TEXT' => 'string',
			'TINYTEXT' => 'string',
			'MEDIUMTEXT' => 'string',
			'LONGTEXT' => 'string',

			'DATETIME' => 'datetime',
			'DATE' => 'date',
			'TIME' => 'time'
		);


		if (stripos($sLine, 'CREATE TABLE') !== FALSE || stripos($sLine, 'KEY') !== FALSE || stripos($sLine, 'TIMESTAMP') !== FALSE) {
			return NULL;
		}

		$aOut = array('type' => '', 'length' => 0); # set defaults to address notices
		$aRXResults = array();

		foreach ($aTypes as $sType => $v) {

			$iPos = stripos($sLine, $sType);

			if ($iPos !== FALSE) {

				$sSub = substr($sLine, $iPos);
				preg_match('/([0-9]+)/', $sSub, $aRXResults);
				$aOut['type'] = $v;

				if ($aOut['type'] !== 'datetime' && $aOut['type'] !== 'date') {
					$aOut['length'] = @$aRXResults[1]; # block for comments in SQL schema
				}

				break;
			}
		}

		preg_match('/`([a-zA-Z0-9\-_]+)`/', $sLine, $aRXResults);

		if ( ! empty($aRXResults[1]) && $aRXResults[1] !== 'id') {

			$aOut['fieldName'] = $aRXResults[1];
			return $aOut;
		}

	} # end findField()


	public function displayMessages() {

		/**
		* getter for class array of messages
		*
		* @return   string
		*/

		return join('<br>', $this->aMessages);

	} # end displayMessages()

} # end {}

?>