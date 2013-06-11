<?php

/*
 * Simple database connection script
 *
 * PDO class.
 */
//define("PDO_USER", "root");
//define("PDO_PASS", "root");

class IgnitePDO {

	  private $db;

	  public function __construct($dsn) {
	    try {
	      $this->db = new PDO($dsn, PDO_USER, PDO_PASS, array( PDO::ATTR_PERSISTENT => true ));
	    } catch (PDOException $e) {
	      die('Connection failed: ' . $e->getMessage());
	    }
	  }
	

	  function __destruct() {
	    $this->db = NULL;
	  }
	

	  private function handleException($e) {
	    echo "Database error: " . $e->getMessage();
	    exit;
	  }


	public function getEntries($limit) {
	    $result = array();
	    try {
	      $sql = "SELECT * FROM entries WHERE moderate=0";
	      //if ($conditions) $sql .= "AND ".$conditions;
	      if ($limit && $limit > 0) $sql .= " LIMIT ".intval($limit);
	      $stmt = $this->db->prepare($sql);
	      $stmt->execute();	
	      while ($row = $stmt->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_NEXT)) {
		      $result[] = $row;
		    }
		    $stmt = null;
	      return json_encode($result);
	    } catch (PDOException $e) {
	      $this->handleException($e);
	    }
    }
  
  
   public function getEntry($id) {
	    $result = array();
	    try {
	      $sql = "SELECT * FROM entries";
	      //if ($conditions) $sql .= "AND ".$conditions;
	      if ($id > 0){
		      $sql .= " WHERE id=".intval($id);
	      } else {
		      throw PDOException('Need an ID');
	      }
	      $stmt = $this->db->prepare($sql);
	      $stmt->execute();	
	      while ($row = $stmt->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_NEXT)) {
		      $result[] = $row;
		    }
		    $stmt = null;
	      return json_encode($result);
	    } catch (PDOException $e) {
	      $this->handleException($e);
	    }
    }
    
	//TODO: add code to prevent sql injection.
	public function executeSQL2($sql){
		$return_data = array();
		try {
			//PDO::setAttribute("PDO::MYSQL_ATTR_USE_BUFFERED_QUERY", true);
			 $stmt = $this->dbh->prepare($sql);
			 $stmt->execute();
			 //$return_data = $stmt->fetch(PDO::FETCH_OBJ);
			 //while ($row = $sth->fetch (PDO::FETCH_BOTH)){}
			 //while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
			 while ($row = $stmt->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_NEXT)) {
		      $return_data[] = $row;
		    }
		    $stmt = null;
			 
			
	    } catch (PDOException $e) {
	        $return_data = "Error!: " . $e->getMessage() . "<br/>";
	        die();
	    }

	    return json_encode($return_data);
    }

}
?>