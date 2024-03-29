<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_db}
 *
 * @package tgiframework
 * @subpackage database
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @todo is there backquoting in PDO? If so, update queries
 * @todo add arbitrary query support (and log)
 * @todo add delete function
 */
// {{{ tgif_db_pdo
// docs {{{
/**
 * Class adds some useful functions (a la {@link http://codex.wordpress.org/Function_Reference/wpdb_Class WordPress wpdb}) to PDO
 *
 * @package tgiframework
 * @subpackage database
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_db_pdo extends pdo
{
    // {{{ - $insertId
    /**
     * The last ID inserted
     * @var integer
     */
    public $insertId = -1;
    // }}}
    // {{{ __construct(…)
    /**
     * Calls PDO constructor using a single paramter array
     *
     * @param array $pdo_args The arguments for the {@link PDO} constructor
     */
    function __construct($pdo_args)
    {
        switch( count($pdo_args) ) {
        case 1:
            parent::__construct($pdo_args[0]);
            break;
        case 2:
            parent::__construct($pdo_args[0],$pdo_args[1]);
            break;
        case 3:
            parent::__construct($pdo_args[0],$pdo_args[1],$pdo_args[2]);
            break;
        case 4:
            parent::__construct($pdo_args[0],$pdo_args[1],$pdo_args[2],$pdo_args[3]);
            break;
        }
    }
    // }}}
    // CREATE, UPDATE, DELETE
    // {{{ - query($sql)
    function query($sql)
    {
        $sth = $this->prepare($sql);
        $result = $sth->execute();
        return $result;
    }
    // }}}
    // {{{ - insert($table,$data)
    /**
     * Insert a row into a table
     *
     * Unlike Wordpress DB, format is not supported
     *
     * @param string $table the name of the table to nsert data into
     * @param array $data date to insert by key/value (no SQL escaping)
     * @return boolean success or failure
     */
    function insert($table, $data)
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::insert()',get_class($this)), array( 'data'=>$data ));
        // format query {{{
        $keys = array_keys($data);
        $values = array();
        for ($i=0,$max=count($keys); $i<$max; ++$i) {
            $key = $keys[$i];
            //$keys[$i] = $key;
            $values[$i] = ':'.$key;
            $data[':'.$key] = $data[$key];
            unset($data[$key]);
        }
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(',',$keys),
            implode(',',$values)
        );
        // }}}
        //$sth = $this->_prepareQuery($query,$data);
        $sth = $this->prepare($query);
        $result = $sth->execute($data);
        //$result = $sth->execute();
        $this->insertId = $this->lastInsertId();
        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return ($result) ? true : false;
    }
    // }}}
    // {{{ - update($table,$data,$where)
    /**
     * Update a row into a table
     *
     * Unlike Wordpress DB, format is not supported
     *
     * @param string $table the name of the table to nsert data into
     * @param array $data date to insert by key/value (no SQL escaping)
     * @param array $where WHERE clause. Multiple clauses joined by "AND"
     * @return boolean success or failure
     */
    function update($table, $data, $where)
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::update()',get_class($this)), array( 'data'=>array_merge($data,$where) ));
        // format query {{{
        $sets = array();
        $wheres = array();
        foreach ($data as $key=>$value) {
            $sets[] = sprintf('%1$s=:%1$s', $key);
            $data[':'.$key] = $value;
            unset($data[$key]);
        }
        foreach ($where as $key=>$value) {
            $wheres[] = sprintf('%1$s=:%1$s', $key);
            $data[':'.$key] = $value;
        }
        $query = sprintf('UPDATE %s SET %s WHERE %s',
            $table,
            implode(',',$sets),
            implode(' AND ',$wheres)
        );
        // }}}
        //$sth = $this->_prepareQuery($query,$data);
        $sth = $this->prepare($query);
        $result = $sth->execute($data);
        //$result = $sth->execute();
        $this->insertId = $this->lastInsertId();
        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return ($result) ? true : false;
    }
    // }}}
    // {{{ - delete($table,$where)
    /**
     * Delete a row (or rows) from a table
     *
     * @param string $table the name of the table to nsert data into
     * @param array $where WHERE clause. Multiple clauses joined by "AND"
     * @return integer the number of rows deleted (MySQL calls ROW_COUNT() automatically)
     */
    function delete($table, $where)
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::delete()',get_class($this)), array( 'data'=>$where ));
        // format query {{{
        $wheres = array();
        $data = array();
        foreach ($where as $key=>$value) {
            $wheres[] = sprintf('%1$s=:%1$s', $key);
            $data[':'.$key] = $value;
        }
        $query = sprintf('DELETE FROM %s WHERE %s',
            $table,
            implode(' AND ',$wheres)
        );
        // }}}
        //$sth = $this->_prepareQuery($query,$data);
        //$result = $sth->execute();
        $sth = $this->prepare($query);
        $result = $sth->execute($data);
        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query, 'row_count'=>$result ) );
        return $result;
    }
    // }}}
    // {{{ - insertOrUpdate($table,$data,$where[,$autoIncrement])
    /**
     * Inserts a row, if it exists update a row into a table
     *
     * @param string $table the name of the table to nsert data into
     * @param array $data date to insert by key/value (no SQL escaping)
     * @param array $where WHERE clause. Multiple clauses joined by "AND"
     * @param string $autoIncrement If set, this is the ID of the auto
     * increment value in order to make the last_insert_id meaningful
     * http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
     * (note if the insert or update is different, then it will still
     * be invalid).
     * @return boolean success or failure
     */
    function insertOrUpdate($table, $data, $where, $autoIncrement='')
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::insertOrUpdate()',get_class($this)), array( 'data'=>array_merge($data,$where) ));
        // format query {{{
        $keys = array_keys($data);
        $values = array();
        $sets = array();
        $wheres = array();
        for ($i=0,$max=count($keys); $i<$max; ++$i) {
            $key            = $keys[$i];
            $value          = $data[$key];

            $data[':'.$key] = $data[$key];
            $values[$i]     = ':'.$key;
            $sets[]         = sprintf('%1$s=VALUES(%1$s)', $key);

            unset($data[$key]);
        }
        foreach ($where as $key=>$value) {
            $data[':'.$key] = $value;
            $keys[$i]       = $key;
            $values[$i]     = ':'.$key;
            ++$i;
            //$wheres[]       = sprintf('%s=%s', $key, $this->quote($value));
        }
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s%s',
            $table,
            implode(',',$keys),
            implode(',',$values),
            ($autoIncrement) ? sprintf('%1$s=LAST_INSERT_ID(%1$s),', $autoIncrement) : '',
            implode(',',$sets)
        );
        // special case: $sets can be empty and end up creating invalid SQL syntax due to hanging comma
        if ( empty($sets) ) {
            $query = substr($query,0,-1);
        }
        // }}}
        //$sth = $this->_prepareQuery($query,$data);
        $sth = $this->prepare($query);
        $result = $sth->execute($data);
        $this->insertId = ($autoIncrement) ? $this->lastInsertId($autoIncrement) : $this->lastInsertId();
        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return ($result) ? true : false;
    }
    // }}}
    // READ
    // {{{ - getResults($query[,$bindings,$output_type])
    /**
     * Select an entire row from a database.
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @param string $output_type OBJECT, ARRAY_A, or ARRAY_N. Unlike the
     * WordPress db, this will default to ARRAY_A
     * @return mixed the rows from the database. If no result found then null
     * @todo support CLASS, BOUND, INTO, LAZY fetch types?
     */
    function getResults($query, $bindings=array(), $output_type='ARRAY_A')
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::getResults()',get_class($this)), array( 'bindings'=>$bindings ));
        $sth = $this->_prepareQuery($query,$bindings);
        $sth->execute();

        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return $sth->fetchAll($this->_guessStyle($output_type));
    }
    // }}}
    // {{{ - getRow($query[,$bindings,$output_type,$row_offset])
    /**
     * Select an entire row from a database.
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @param string $output_type OBJECT, ARRAY_A, or ARRAY_N. Unlike the
     * WordPress db, this will default to ARRAY_A
     * @param integer $row_offset The desired row
     * @return mixed the row from the database. If no result found then null
     */
    function getRow($query, $bindings=array(), $output_type='ARRAY_A', $row_offset=0)
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::getRow()',get_class($this)), array( 'bindings'=>$bindings ));
        $sth = $this->_prepareQuery($query,$bindings);

        $sth->execute();

        // scan to right row
        for ($i=1; $i<$row_offset; ++$i) {
            $success = $sth->nextRowset();
            if (!$success) { return null; }
        }

        $result = $sth->fetch($this->_guessStyle($output_type));
        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return $result;
    }
    // }}}
    // {{{ - getVar($query[,$bindings,$column_offset,$row_offset])
    /**
     * Select a single variable from a database.
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @param integer $column_offset The desired column
     * @param integer $row_offset The desired row
     * @return mixed the varible from the database. If no result found then null or false (PDO::fetchColumn() returns false on failure)
     */
    function getVar($query, $bindings=array(), $column_offset=0, $row_offset=0)
    {
        $_TAG->diagnostics->startTimer('db', sprintf('%s::getVar()',get_class($this)), array( 'bindings'=>$bindings ));
        $sth = $this->_prepareQuery($query,$bindings);

        $sth->execute();

        // scan to right row
        for ($i=1; $i<$row_offset; ++$i) {
            $success = $sth->nextRowset();
            if (!$success) { return null; }
        }

        $_TAG->diagnostics->stopTimer('db', array( 'query' => $query ) );
        return $sth->fetchColumn($column_offset);
    }
    // }}}
    // PRIVATE METHODS
    // {{{ - _guessStyle($output_type)
    /**
     * Return the PDO fetch style.
     *
     * @param string $output_type OBJECT, ARRAY_A, or ARRAY_N. Unlike the
     * WordPress db, this will default to ARRAY_A
     * @return integer returns the PDO::FETCH_* constant
     */
    private function _guessStyle($output_type)
    {
        switch ($output_type) {
            case 'ARRAY_A': return PDO::FETCH_ASSOC;
            case 'ARRAY_N': return PDO::FETCH_NUM;
            case 'OBJECT' : return PDO::FETCH_OBJ;
        }
        return PDO::FETCH_BOTH;
    }
    // }}}
    // {{{ - _prepareQuery($query[,$bindings])
    /**
     * Prepares a query
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @return PDOStatement a statement handle of the prepared query
     * @todo PARAM_BOOL (boolean)
     * @todo PARAM_LOB (large object)
     */
    private function _prepareQuery($query, $bindings=array())
    {
        //var_dump($query);
        $return_obj = $this->prepare($query);
        foreach ($bindings as $key=>$value) {
            // make sure there is a : at the beginning of the bindparam
            $key = ( substr($key,0,1) == ':' ) ? $key : ':'.$key;
            // type checking
            if (is_null($value)) {
		        $return_obj->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
		        $return_obj->bindValue($key, $value, PDO::PARAM_INT);
            } else {
		        $return_obj->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        return $return_obj;
    }
    // }}}
}
// }}}
?>
