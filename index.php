<?php
/*
This class transforms a query of Firebird in a new table of MySQL
*/
class Fire2My{
	/*
	@server is the server ip + path of the firebird database file;
	example in Windows:	
	$f2m->server = '127.0.0.1:C:\Firebird\test.fdb';
	*/
	public $server;
	
	/*
	@user is the username to connect in the database, the default is 'SYSDBA' 
	@password is the password to connect in the database, the default is 'masterkey' 
	*/
	public $user='SYSDBA';
	public $password='masterkey';
	
	/*
	@database is the name of the database that will be create by the function create_database
	*/
	public $database;

	/*
	@table is the name of the table that will be created to insert the data of the sql
	*/
	public $table;

	private $sql;
	
	/*
	@query is the active query resultset in Firebird using the @sql
	*/
	private $query;

	/*
	@connected is only a atribute to control if the database is connected or not
	*/	
	private $connected = FALSE;
	
	/*
	@dbh is the connection of PHP with Firebird	
	*/	
	private $dbh;
	
	/*
	@cols receives the list of columns of the query
	*/	
	private $cols;
	
	/*
	@select will activate the query and populate the cols
	
	@sql is the select query that will create the table and registers
	example:
	$f2m->sql = 'SELECT FIRST 10 SKIP 30 a.ID, a.NAME, b.BARCODE 
	FROM PRODUCTS as a Full Join CODES as b ON(a.BARCODEID = b.ID)';
	*/	
	public function select($sql){
		$this->sql = $sql;
		
		$this->query = ibase_query ($this->dbh, $sql); 
		
		$coln = ibase_num_fields($this->query);
		if($coln==0)
			return;
		
		for ($i = 0; $i < $coln; $i++) {
			$col_info = ibase_field_info($this->query, $i);
			// echo "name: " . $col_info['name'] . "\n";
			// echo "alias: " . $col_info['alias'] . "\n";
			// echo "relation: " . $col_info['relation'] . "\n";
			// echo "length: " . $col_info['length'] . "\n";
			// echo "type: " . $col_info['type'] . "\n";
			$this->cols[]= $col_info;	
		}			
	}
	
	/*
	@create_database is the simple query to create a database in MySQL
	
	@new_database_name is the name of the new database to be created in MySQL
	*/	
	public function create_database($new_database_name){
		echo "CREATE DATABASE IF NOT EXISTS `".$new_database_name."` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
			<br><br>USE `".$new_database_name."`;<br><br>";
	}
	
	/*
	@create_table will create a new table in MySQL with the columns specified in sql
	
	@new_table_name is the name of the new table to be created in MySQL
	
	@create_pk if true will create the column ID to be primary key. If not, only the selection will be created
	*/	
	public function create_table($new_table_name=null,$create_pk=false){
		if($this->cols == null)
			echo "ERROR: First use the function select with the query that will be catch the data";
		
		if($new_table_name)
			$this->table = $new_table_name;

		echo "<br>CREATE TABLE `".$this->table."`(<br>";
		
		if($create_pk){
			echo '`ID`';
			echo ' BIGINT ';
			echo 'NOT NULL AUTO_INCREMENT,<BR>';			
		}

		for($i=0;$i<sizeof($this->cols);$i++){
			$col = $this->cols[$i];
			$r[]='`'.strtoupper($col['alias'])."`".
			     ' '.$col['type'].' '.
			     '('.$col['length'].') NULL';
		}
		echo implode(',<br>',$r);
		if($create_pk)
			echo '<br>,PRIMARY KEY (`ID`)';
		echo '<br>)  ENGINE = MyISAM;<br><BR>';
	}
	
	/*
	@insert will transfer the data of the Firebird to the MySQL
	
	@new_table_name is the name of the table in MySQL that will receives the data
	*/	
	public function insert($new_table_name=null){
		if($this->query == null)
			echo "ERROR: First use the function select with the query that will be catch the data";
		
		if($new_table_name)
			$this->table = $new_table_name;
		
		echo "INSERT INTO ".$this->table." (";
		for($i=0;$i<sizeof($this->cols)-1;$i++){
			$col = $this->cols[$i];
			echo '`'.strtoupper($col['alias'])."`, ";
		}
		$col = $this->cols[$i];
		echo '`'.strtoupper($col['alias'])."`) VALUES<br>";
		
		while ($row = ibase_fetch_row ($this->query)) {
			// $FILIAL = $row->COD_FILIAL;
			$dado = "(";
			for($i=0;$i<sizeof($row);$i++){
				$dado .= "'".str_replace("'","`",strtoupper($row[$i]))."',";
			}
			$dado = rtrim($dado,',');
			$dado .= "),<br>";
			echo $dado;
		}
			
	}

	/*
	The constructor search the function that has the number of arguments in his name then call it
	*/
    function __construct() 
    { 
        $a = func_get_args(); 
        $i = func_num_args(); 
        if (method_exists($this,$f='__construct'.$i)) { 
            call_user_func_array(array($this,$f),$a); 
        }else
			echo "ERROR: You need a server, a name and a password to create a connection";
    }
	private function __construct0(){
	}
	private function __construct1($server){
		$this->server = $server;
	}
	private function __construct3($server,$user,$password){
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->connect();
	}
	
	/*
	connect PHP on Firebird
	*/
	public function connect($server=null,$user=null,$password=null){
		if($server)
			$this->server = $server;
		if($user)
			$this->user = $user;
		if($password)
			$this->password = $password;			
		
		if($this->connected)
			$this->disconnect();
		if (!($this->dbh=ibase_connect($this->server, $this->user, $this->password)))
			die('CONNECTION ERROR: ' .  ibase_errmsg());
		else
			$this->connected = True;
	}
	
	/*
	Disconnect the Firebirt
	IMPORTANT: Use this function whenever you finish the work
	*/	
	public function disconnect(){
		ibase_close($this->dbh);
		$this->connected = False;
	}	
}

//O banco tava sem mostrar acento, arrumei com isso
header('Content-Type: text/html; charset=ISO8859_1');

$o = new Fire2My('127.0.0.1:C:\Firebird\teste.fdb','SYSDBA', '1234');
$o->select('SELECT a.id as IDPRODUTO, a.descricao, b.CODIGOBARRA, b.PADRAOBARRA 
FROM PRODUTOESERVICO as a 
Full Join PRODUTO as b ON(a.produtoid = b.id)');
$o->create_database('PESQUISA_PRECO');
$o->create_table('PRODUTO',1);
$o->insert();
$o->disconnect();

