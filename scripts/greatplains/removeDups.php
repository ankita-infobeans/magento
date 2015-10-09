<?php



$do = new doIt();
$do->run();


class doIt
{
	private $dbUser = "icc";
	private $dbPass = "phuWepOov5";
	private $dbDb = "iccshop";
	private $dbHost = "10.30.2.172";
	private $conn = null;

	function run()

	{
		$this->setupDb();

		$q = "
		SELECT * FROM `ecodes_downloadable`
		INNER JOIN (
		SELECT `id`,serial`
		FROM `ecodes_downloadable`
		GROUP BY `serial`
		HAVING count( `serial` ) >1) dup ON ecodes_downloadable.serial = dup.serial";


		$q = "

		SELECT *
		FROM `ecodes_downloadable`
		GROUP BY `serial`
		HAVING count( `serial` ) >1 AND `serial` != ''";
		
		
		$q = "
		
		SELECT *
		FROM `ecodes_downloadable`
		GROUP BY `order_item_id`
		HAVING count( `order_item_id` ) >1 AND `serial` != ''";
		
		

		echo $q;

		$total = 0;
		$result = mysql_query($q,$this->conn);
		if(!$result)
		{
			echo mysql_error();
			die;
		}
		
		while ($row = mysql_fetch_assoc($result)) {

			//print_r($row);
			//$total++;
			//continue;
			$q2 = "SELECT * FROM `ecodes_downloadable` WHERE `order_item_id` = '".$row['order_item_id']."'";

			//echo $q2."\n";
			$res2 = mysql_query($q2);
			if(!$res2)
			{
				echo mysql_error();
				die;
			}
			$deleted = false;
			$a = array();
			while($row2 = mysql_fetch_assoc($res2))
			{
				$a[] = $row2;

				if($row2['updated_at'] == "0000-00-00 00:00:00")
				{
					//echo "\n";
					//echo "---------------------------------------------------\n";
					//echo "---------------------------------------------------\n";
					//print_r($row2);
					//echo "---------------------------------------------------\n";
					//echo "---------------------------------------------------\n";
					//echo "\n\n";
					$q3 = "DELETE FROM `ecodes_downloadable` WHERE `id` = '".$row2['id']."'";
					echo $q3."\n";
					mysql_query($q3);
					$deleted = true;
					$total++;
				}

			}
			if(!$deleted)
			{
				echo "NO empty now need to see whats going on\n";

				$thisone = array_pop($a);
				
				//print_r($thisone);
				$q3 = "DELETE FROM `ecodes_downloadable` WHERE `id` = '".$thisone['id']."'";
				echo $q3."\n\n\n\n";
				mysql_query($q3);
				$deleted = true;
				$total++;
				
			}
			//die;

		}
		
		echo "total deletes are $total\n";


	}
	function setupDb()
	{


		$this->conn = mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);

		if (!$this->conn) {
			echo "Could not connect to server\n";
			trigger_error(mysql_error(), E_USER_ERROR);
			die;
		} else {
			echo "Connection established\n";
		}


		if (!mysql_select_db($this->dbDb)) {
			echo "Unable to select mydbname: " . mysql_error();
			exit;
		}

	}
}
