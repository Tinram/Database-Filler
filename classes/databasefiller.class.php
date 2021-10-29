<?php

declare(strict_types=1);


final class DatabaseFiller
{
    /**
        * Fill a multi-table MySQL database with test data by parsing the SQL schema file.
        *
        * Origin:
        *                  A database with 14 complex tables required test data.
        *                  This was too much configuration work for a tool such as Spawner.
        *                  Instead, why not parse the SQL schema?
        *
        * Purpose:
        *                  1) Database table reproduction from a structure-only SQL schema file, without using any real or sensitive data.
        *                  2) Database schema design and testing: data truncation, character encoding etc.
        *
        * Requirements:
        *                  1) Script expects database schema to exist in MySQL (mysql -u root -p < test.sql)
        *                  2) All table names and column names in the MySQL schema require back-ticks.
        *                  3) Unique keys must be removed from tables when using the configuration array option 'random_data' => false
        *
        * Other:
        *                  Any foreign keys are disabled on data population.
        *                  Random character generation is slow in PHP.
        *                  Coded to PHP 7.2
        *
        * @author          Martin Latter
        * @copyright       Martin Latter 13/12/2014
        * @version         0.58
        * @license         GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
        * @link            https://github.com/Tinram/Database-Filler.git
    */


    ## CONFIGURATION DEFAULTS ##

    /** @var boolean $bDebug, debug output toggle */
    private $bDebug = false; # true: verbose screen output and no database insertion; false: database query insertion

    /** @var integer $iNumRows, number of rows to insert */
    private $iNumRows = 1;

    /** @var string $sSchemaFile, schema file */
    private $sSchemaFile = null;

    /** @var string $sEncoding, database connection encoding */
    private $sEncoding = 'utf8';

    /** @var integer $iLowChar, random character range: low */
    private $iLowChar = 33;

    /** @var integer $iHighChar, random character range: high */
    private $iHighChar = 126;

    /** @var boolean $bRandomData, random data generator toggle */
    private $bRandomData = true; # false = much faster fixed character fill (unsuitable with unique indexes, and SET unique_checks = 0 is not sufficient)

    /** @var integer $iCLIRowCounter, CLI usage: rows of SQL generated before displaying progress percentage */
    private $iCLIRowCounter = 1000;

    /** @var boolean $bPopulatePrimaryKey, toggle to populate primary key field (experimental), e.g. UUID used as PK */
    private $bPopulatePrimaryKey = false;

    /** @var boolean $bIncrementalInts, toggle to make integers incremental (simple integer FK provision) */
    private $bIncrementalInts = false;

    ###############################

    /** @var object $oConnection */
    private $oConnection;

    /** @var boolean $bActiveConnection */
    private $bActiveConnection = false;

    /** @var string $sPrimaryKey */
    private $sPrimaryKey = '';

    /** @var string $sUsername */
    private $sUsername = '';

    /** @var string $sLineBreak */
    private $sLineBreak = '';

    /** @var array<string|null> $aMessages */
    private $aMessages = [];


    /**
        * Constructor: set-up configuration class variables, establish database connection if debug = false.
        *
        * @param   array<mixed> $aConfig, configuration details
    */

    public function __construct(array $aConfig)
    {
        $this->sLineBreak = (PHP_SAPI !== 'cli') ? '<br>' : "\n";

        if ( ! isset($aConfig['schema_file']))
        {
            $this->aMessages[] = 'No schema file specified in the configuration array.';
            return;
        }

        if (isset($aConfig['debug']))
        {
            $this->bDebug = $aConfig['debug'];
        }

        if (isset($aConfig['num_rows']))
        {
            $this->iNumRows = (int) $aConfig['num_rows'];
        }

        if (isset($aConfig['random_data']))
        {
            $this->bRandomData = $aConfig['random_data'];
        }

        if (isset($aConfig['low_char']))
        {
            $this->iLowChar = (int) $aConfig['low_char'];
        }

        if (isset($aConfig['high_char']))
        {
            $this->iHighChar = (int) $aConfig['high_char'];
        }

        if (isset($aConfig['row_counter_threshold']))
        {
            $this->iCLIRowCounter = (int) $aConfig['row_counter_threshold'];
        }

        if (isset($aConfig['populate_primary_key']))
        {
            $this->bPopulatePrimaryKey = $aConfig['populate_primary_key'];
        }

        if (isset($aConfig['incremental_ints']))
        {
            $this->bIncrementalInts = $aConfig['incremental_ints'];
        }

        if ( ! $this->bDebug)
        {
            if ( ! isset($aConfig['host']) || ! isset($aConfig['database']) || ! isset($aConfig['username']) || ! isset($aConfig['password']))
            {
                $this->aMessages[] = 'Database connection details have not been fully specified in the configuration array.';
                return;
            }

            if (isset($aConfig['encoding']))
            {
                $this->sEncoding = $aConfig['encoding'];
            }

            $this->oConnection = new mysqli($aConfig['host'], $aConfig['username'], $aConfig['password'], $aConfig['database']);

            if ($this->oConnection->connect_errno === 0)
            {
                $this->oConnection->set_charset($this->sEncoding);
                $this->bActiveConnection = true;

                $this->sUsername = $aConfig['username'];
            }
            else
            {
                $this->aMessages[] = 'Database connection failed: ' . $this->oConnection->connect_error . ' (error number: ' . $this->oConnection->connect_errno . ')';
                return;
            }
        }

        $this->parseSQLFile($aConfig['schema_file']);
    }


    /**
        * Close database connection if active.
    */

    public function __destruct()
    {
        if ($this->bActiveConnection)
        {
            $this->oConnection->close();
        }
    }


    /**
        * Parse SQL file to extract table schema.
        *
        * @param   string $sFileName, schema filename
        *
        * @return  void
    */

    private function parseSQLFile(string $sFileName): void
    {
        $aTableHolder = [];
        $aMatch = [];

        if ( ! file_exists($sFileName))
        {
            $this->aMessages[] = 'The schema file \'' . htmlentities(strip_tags($sFileName)) . '\' does not exist in this directory.';
            return;
        }

        # parse SQL schema
        $sFile = file_get_contents($sFileName);

        # find number of instances of 'CREATE TABLE'
        preg_match_all('/CREATE TABLE/', $sFile, $aMatch);

        # create array of table info
        for ($i = 0, $iOffset = 0, $n = count($aMatch[0]); $i < $n; $i++)
        {
            if ($iOffset === 0)
            {
                $iStart = stripos($sFile, 'CREATE TABLE');
                $iEnd = stripos($sFile, 'ENGINE=');
            }
            else
            {
                $iStart = stripos($sFile, 'CREATE TABLE', $iEnd);
                $iEnd = stripos($sFile, 'ENGINE=', $iStart);
            }

            $sTable = substr($sFile, $iStart, ($iEnd - $iStart));

            $iOffset = $iEnd;

            # remove COMMENT 'text', including most common symbols; preserve schema items after COMMENT
            $sTable = preg_replace('/comment [\'|"][\w\s,;:`<>=£&%@~#\\\.\/\{\}\[\]\^\$\(\)\|\!\*\?\-\+]*[\'|"]/i', '', $sTable);

            # strip SQL comments
            $sTable = preg_replace('!/\*.*?\*/!s', '', $sTable); # credit: chaos, stackoverflow
            $sTable = preg_replace('/[\s]*(--|#).*[\n|\r\n]/', "\n", $sTable);

            # replace EOL and any surrounding spaces for split
            $sTable = preg_replace('/[\s]*,[\s]*[\n|\r\n]/', '*', $sTable);

            $aTableHolder[] = $sTable;
        }

        # send each table string for processing
        foreach ($aTableHolder as $sTable)
        {
            $this->processSQLTable($sTable);
        }
    }


    /**
        * Process each table schema.
        *
        * @param   string $sTable, table schema string
        *
        * @return  void
    */

    private function processSQLTable(string $sTable): void
    {
        static $iCount = 1;

        $fD1 = microtime(true);

        $aDBFieldAttr = [];
        $aRXResults = [];
        $aFields = [];
        $aValues = [];

        # parse primary key name
        $iPKStart = stripos($sTable, 'PRIMARY KEY');
        $iPKEnd = strpos($sTable, ')', $iPKStart);
        $sPKCont = substr($sTable, $iPKStart, $iPKEnd);
        preg_match('/`([\w\-]+)`/', $sPKCont, $aRXResults);
        $this->sPrimaryKey = $aRXResults[1]; # class var rather than passing a function parameter for each line

        $aLines = explode('*', $sTable);

        # get table name
        preg_match('/`([\w\-]+)`/', $aLines[0], $aRXResults);
        $sTableName = $aRXResults[1];

        if ($this->bPopulatePrimaryKey === true)
        {
            $aLines[0] = str_replace($aRXResults[0], '', $aLines[0]);
        }

        # extract field attributes
        foreach ($aLines as $sLine)
        {
            $aTemp = $this->findField($sLine);

            if ( ! is_null($aTemp))
            {
                $aDBFieldAttr[] = $aTemp;
            }
        }
        ##

        # create SQL query field names
        foreach ($aDBFieldAttr as $aRow)
        {
            $aFields[] = '`' . $aRow['fieldName'] . '`';
        }
        ##

        if (PHP_SAPI === 'cli' && $this->iNumRows > $this->iCLIRowCounter)
        {
            echo 'generating SQL for table \'' . $sTableName . '\' ...' . $this->sLineBreak;
        }

        # create SQL query value sets
        for ($i = 0; $i < $this->iNumRows; $i++)
        {
            $aTemp = []; # reset

            # generate random data for fields, dependent on datatype
            foreach ($aDBFieldAttr as $aRow)
            {
                if ($aRow['type'] === 'string')
                {
                    $iLen = (int) $aRow['length'];

                    if ($iLen === 0)
                    {
                        $iLen = 255;
                    }

                    $s = '';

                    if ($this->bRandomData)
                    {
                        for ($j = 0; $j < $iLen; $j++)
                        {
                            $c = chr(mt_rand($this->iLowChar, $this->iHighChar));

                            if ($c === '<' || $c === '>') # < and > are corrupting symbols
                            {
                                $c = 'Z';
                            }

                            $s .= $c;
                        }
                    }
                    else
                    {
                        for ($j = 0; $j < $iLen; $j++)
                        {
                            $s .= 'X';
                        }
                    }

                    $aTemp[] = '"' . addslashes($s) . '"';
                }
                else if (substr($aRow['type'], 0, 3) === 'int')
                {
                    $MAXINT = mt_getrandmax(); # limited by PHP

                    switch ($aRow['type'])
                    {
                        case 'int_32' :

                            if ($aRow['unsigned'] === true)
                            {
                                $iMin = 0;
                                $iMax = $MAXINT;
                            }
                            else
                            {
                                # skew to get predominantly negative values
                                $iMin = -9999999;
                                $iMax = 1000000;
                            }

                        break;

                        case 'int_24' :

                            if ($aRow['unsigned'] === true)
                            {
                                $iMin = 0;
                                $iMax = 16777215;
                            }
                            else
                            {
                                $iMin = -8388608;
                                $iMax = 8388607;
                            }

                        break;

                        case 'int_16' :

                            if ($aRow['unsigned'] === true)
                            {
                                $iMin = 0;
                                $iMax = 65535;
                            }
                            else
                            {
                                $iMin = -32768;
                                $iMax = 32767;
                            }

                        break;

                        case 'int_8' :

                            if ($aRow['unsigned'] === true)
                            {
                                $iMin = 0;
                                $iMax = 255;
                            }
                            else
                            {
                                $iMin = -127;
                                $iMax = 127;
                            }

                        break;

                        case 'int_64' :

                            # int_64 dealt with separately for 32-bit limits
                            $iMin = 0;
                            $iMax = 18446744073708551616; # reduced slightly to avoid 'out of range' error for 'random_data' => false

                        break;
                    }

                    if ($this->bIncrementalInts)
                    {
                        if ($aRow['type'] === 'int_32' || $aRow['type'] === 'int_64')
                        {
                            $iNum = $i + 1;
                        }
                        else
                        {
                            $iNum = $iMax;
                        }
                    }
                    else if ($this->bRandomData)
                    {
                        if ($aRow['type'] !== 'int_64')
                        {
                            $iNum = mt_rand($iMin, $iMax);
                        }
                        else
                        {
                            # BIGINT string kludge for 32-bit systems
                            $s = '';
                            for ($j = 0; $j < 19; $j++) # 1 char less than max to avoid overflow
                            {
                                $s .= mt_rand(0, 9);
                            }
                            $iNum = $s;
                        }
                    }
                    else
                    {
                        $iNum = $iMax;
                    }

                    $aTemp[] = $iNum;
                }
                else if ($aRow['type'] === 'decimal' || substr($aRow['type'], 0, 5) === 'float')
                {
                    # compromise dealing with decimals and floats

                    if ($aRow['type'] === 'decimal')
                    {
                        $iLen = ((int) $aRow['length']) - 4;
                    }
                    else
                    {
                        $iLen = (int) $aRow['length'];
                    }

                    $s = '';

                    for ($j = 0; $j < $iLen; $j++)
                    {
                        $s .= 9;
                    }

                    $iMax = (int) $s;

                    if ($this->bRandomData)
                    {
                        $iNum = mt_rand(0, $iMax);
                        $iUnits = mt_rand(0, 99);
                    }
                    else
                    {
                        $iNum = $s;
                        $iUnits = '50';
                    }

                    if ($aRow['type'] === 'decimal')
                    {
                        $aTemp[] = '"' . $iNum . '.' . $iUnits . '"';
                    }
                    else if ($aRow['type'] === 'float_single')
                    {
                        $aTemp[] = lcg_value() * ($iMax * 0.01);
                    }
                    else if ($aRow['type'] === 'float_double')
                    {
                        $aTemp[] = lcg_value() * ($iMax * 0.01);
                    }
                }
                else if ($aRow['type'] === 'date')
                {
                    $aTemp[] = '"' . date('Y-m-d') . '"';
                }
                else if ($aRow['type'] === 'datetime')
                {
                    $aTemp[] = '"' . date('Y-m-d H:i:s') . '"';
                }
                else if ($aRow['type'] === 'time')
                {
                    $aTemp[] = '"' . date('H:i:s') . '"';
                }
                else if ($aRow['type'] === 'enumerate')
                {
                    $aTemp[] = '"' . $aRow['enumfields'][array_rand($aRow['enumfields'], 1)] . '"';
                }
            }

            $aValues[] = '(' . join(',', $aTemp) . ')';

            # SQL generation progress indicator for CLI
            if (PHP_SAPI === 'cli')
            {
                if ($this->iNumRows > $this->iCLIRowCounter)
                {
                    if ($i % $this->iCLIRowCounter === 0)
                    {
                        printf("%02d%%" . $this->sLineBreak . "\x1b[A", ($i / $this->iNumRows) * 100);
                    }
                }
            }
        }
        ##

        $fD2 = microtime(true);
        $this->aMessages[] = ((PHP_SAPI === 'cli') ? $this->sLineBreak : '') . __METHOD__ . '() table ' . $iCount . ' :: ' . sprintf('%01.6f sec', $fD2 - $fD1);

        if ($this->bDebug)
        {
            $this->aMessages[] = var_dump($aFields);
            $this->aMessages[] = var_dump($aValues);
        }

        # create SQL query string
        $sInsert = 'INSERT INTO ' . $sTableName . ' ';
        $sInsert .= '(' . join(',', $aFields) . ') ';
        $sInsert .= 'VALUES ' . join(',', $aValues);

        if ($this->bDebug)
        {
            $this->aMessages[] = $sInsert . $this->sLineBreak;
        }
        ##

        # send SQL to database
        if ( ! $this->bDebug)
        {
            $this->oConnection->query('SET foreign_key_checks = 0');

            if ($this->sUsername === 'root' && $this->iNumRows > 1500) # adjust value as necessary, 1500 was originally for Win XAMPP
            {
                # the following variable can be set when running as root / super (affecting all connections)
                # other useful variables for inserts need to be directly edited in my.cnf / my.ini
                $this->oConnection->query('SET GLOBAL max_allowed_packet = 268435456');
            }

            $fT1 = microtime(true);
            $rResult = $this->oConnection->query($sInsert);
            $fT2 = microtime(true);

            if ($rResult)
            {
                $this->aMessages[] = 'added ' . $this->iNumRows . ' rows of ' . ($this->bRandomData ? 'random' : 'fixed') . ' data to table \'' . $sTableName . '\'';
            }
            else
            {
                $this->aMessages[] = 'MySQL reports ERRORS attempting to add ' . $this->iNumRows . ' rows of ' . ($this->bRandomData ? 'random' : 'fixed') . ' data to table \'' . $sTableName . '\'';
                $rResult = $this->oConnection->query('SHOW WARNINGS');
                $aErrors = $rResult->fetch_row();
                $rResult->close();
                $this->aMessages[] = join(' | ', $aErrors);
            }

            $this->aMessages[] = 'SQL insertion: ' . sprintf('%01.6f sec', $fT2 - $fT1) . $this->sLineBreak;
        }
        ##

        $iCount++;
    }


    /**
        * Extract field data from schema line.
        *
        * @param   string $sLine, line
        *
        * @return  array<mixed>|null ['fieldName' => $v, 'type' => $v, 'length' => $v]
    */

    private function findField(string $sLine): ?array
    {
        static $aTypes =
        [
            'BIGINT' => 'int_64',
            'TINYINT' => 'int_8',
            'SMALLINT' => 'int_16',
            'MEDIUMINT' => 'int_24',
            'INT' => 'int_32', # catch other ints before int_32

            'DECIMAL' => 'decimal',
            'FLOAT' => 'float_single',
            'DOUBLE' => 'float_double',

            'CHAR' => 'string',
            'VARCHAR' => 'string',

            'TEXT' => 'string',
            'TINYTEXT' => 'string',
            'MEDIUMTEXT' => 'string',
            'LONGTEXT' => 'string',

            'ENUM' => 'enumerate',

            'DATETIME' => 'datetime',
            'DATE' => 'date',
            'TIME' => 'time'
        ];

        if ($this->bPopulatePrimaryKey === false)
        {
            if (stripos($sLine, 'CREATE TABLE') !== false)
            {
                return null;
            }
        }

        if
        (
            stripos($sLine, 'KEY') !== false ||
            stripos($sLine, 'UNIQUE') !== false ||
            stripos($sLine, 'FULLTEXT') !== false ||
            stripos($sLine, 'SPATIAL') !== false ||
            stripos($sLine, 'TIMESTAMP') !== false
        )
        {
            return null;
        }

        $aOut = ['type' => '', 'length' => 0]; # set defaults to address notices
        $aRXResults = [];

        foreach ($aTypes as $sType => $v)
        {
            $iPos = stripos($sLine, $sType);

            if ($iPos !== false)
            {
                $sSub = substr($sLine, $iPos);

                preg_match('/([0-9]+)/', $sSub, $aRXResults);
                $aOut['type'] = $v;

                if ($aOut['type'] !== 'datetime' && $aOut['type'] !== 'date')
                {
                    if (count($aRXResults) !== 0)
                    {
                        $aOut['length'] = $aRXResults[1];
                    }
                    else
                    {
                        $aOut['length'] = 0;
                    }
                }

                # ENUMeration
                if ($aOut['type'] === 'enumerate')
                {
                    $iStart = strpos($sLine, '(');
                    $iEnd = strpos($sLine, ')');

                    $sEnumParams = substr($sLine,  $iStart, ($iEnd + 1) - $iStart);
                    $sEnumParams = str_replace( ['\'', '"', '(', ')'], '', $sEnumParams);
                    $sEnumParams = str_replace(', ', ',', $sEnumParams);

                    $aOut['enumfields'] = explode(',', $sEnumParams);
                }

                break;
            }
        }

        preg_match('/`([\w\-]+)`/', $sLine, $aRXResults);

        $aOut['unsigned'] = (stripos($sLine, 'unsigned') !== false) ? true : false;

        if (($aRXResults[1] !== '') && ($aRXResults[1] !== $this->sPrimaryKey))
        {
            $aOut['fieldName'] = $aRXResults[1];
            return $aOut;
        }
        else
        {
            return null;
        }
    }


    /**
        * Getter for class array of messages.
        *
        * @return  string
    */

    public function displayMessages(): string
    {
        return $this->sLineBreak . join($this->sLineBreak, $this->aMessages) . $this->sLineBreak;
    }
}
