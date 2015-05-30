<?php
	//ddbb config file
	require("config");
	$myDomain = 'kou.no-ip.biz/';
	function encode($id)
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
		$shortenedId = '';
		while($id>0) {
			$remainder = $id % 64;
			$id = ($id-$remainder) / 64;     
			$shortenedId = $alphabet{$remainder} . $shortenedId;
		};
		return $shortenedId;
	}
	function decode($str, $base=64, $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-') {
		$len = strlen($str);
		$val = 0;
		$arr = array_flip(str_split($chars));
		for($i = 0; $i < $len; ++$i) {
			$val += $arr[$str[$i]] * pow($base, $len-$i-1);
		}
		return $val;
	}
    if(!empty($_POST)){ 
        // Ensure that the user fills out fields 
        if(empty($_POST['url'])){
		  $errUrl = 'Please provide the url that needs to be shortened.';
		}
        if(empty($_POST['human'])){ 
			$errHuman = 'Please answer the anti-spam question.';
		}else if($_POST['human']!=$_POST['human1']){ 
			$errHuman = 'Your anti-spam answer is incorrect.';
		}
		if (!$errUrl && !$errHuman) {
				
			$conn = new mysqli($db_host, $db_username, $db_password, $db_dbname);
			$url = $_POST['url'];
			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			// prepare and bind
			$stmt = $conn->prepare("SELECT id, short, times FROM shortener WHERE url = ?");
			$stmt->bind_param("s", $url);
			$stmt->execute();
			$stmt->bind_result($id, $short, $times);
			
			$urlData = array();
			// fetch values
			//$row = $stmt->fetch(); 
			while ($stmt->fetch()) {
				$urlData['id'] = $id; //0
				$urlData['short'] = $short;
				$urlData['times'] = $times;
			}
			//close connection
			$stmt->close();
			$conn->close();
			
			if (empty($urlData['id'])){
				
				$conn = new mysqli($db_host, $db_username, $db_password, $db_dbname);
				// Check connection
				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				try {

					$conn->autocommit(FALSE); // i.e., start transaction
					$random = (string)rand(10,99999);
					// assume that the TABLE groups has an auto_increment id field
					$stmt = $conn->prepare("INSERT INTO shortener (url, short) VALUES (?,?)");
					$stmt->bind_param("ss", $url, $random);
					$result = $stmt->execute();
					if ( !$result ) {
						$result->free();
						throw new Exception($conn->error);
					}

					$url_id = $conn->insert_id; // last auto_inc id from *this* connection
					$short = encode($url_id);
					$urlData['short'] = $short;
					$query = "UPDATE shortener SET short='$short' WHERE id='$url_id'";
					$result = $conn->query($query);
					if ( !$result ) {
						$result->free();
						throw new Exception($conn->error);
					}
					// our SQL queries have been successful. commit them
					// and go back to non-transaction mode.
					$conn->commit();
					$conn->autocommit(TRUE); // i.e., end transaction
					//close connection
					$stmt->close();
					$conn->close();
				}
				catch ( Exception $e ) {
					// before rolling back the transaction, you'd want
					// to make sure that the exception was db-related
					$conn->rollback(); 
					$conn->autocommit(TRUE); // i.e., end transaction   
					//close connection
					$stmt->close();
					$conn->close();
				}
							
			}else{				
				//Increase times
				$conn = new mysqli($db_host, $db_username, $db_password, $db_dbname);
				// Check connection
				if ($conn->connect_error) {
					die("Connection failed: " . $conn->connect_error);
				}
				// prepare and bind
				$stmt = $conn->prepare("UPDATE shortener SET times = times+1 WHERE id = ?");
				$stmt->bind_param("s", $id);
				$stmt->execute();
				$stmt->bind_result($url);
				$stmt->close();
				$conn->close();
			}
		}
	}	
		
	if(!empty($_GET)){
		$id = (string)$_GET['id'];
		if ($id != ""){
			$datos = true;
		}else{
			$datos = false;
		}
	}else{
		$datos = false;
	}
	if (!$datos){
		//nada
	}else{
		$id = preg_replace("/[^a-zA-Z0-9-_]/", "", $id);
		
		$conn = new mysqli($db_host, $db_username, $db_password, $db_dbname);
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		// prepare and bind
		$stmt = $conn->prepare("SELECT url FROM shortener WHERE id = ?");
		$stmt->bind_param("s", decode($id));
		$stmt->execute();
		$stmt->bind_result($url);
		
		$urlData = array();
		while ($stmt->fetch()) {
			$urlData['url'] = $url; //0
		}
		//close connection
		$stmt->close();
		$conn->close();
		
		if (empty($urlData['url'])){
			$errLoad = 'The url that you are looking for don\'t exists.';
		}else{
			$conn = new mysqli($db_host, $db_username, $db_password, $db_dbname);
			// Check connection
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			// prepare and bind
			$stmt = $conn->prepare("UPDATE shortener SET uses = uses+1 WHERE id = ?");
			$stmt->bind_param("s", decode($id));
			$stmt->execute();
			$stmt->bind_result($url);
			$stmt->close();
			$conn->close();
			//better update to regex
			if (strpos($urlData[url], 'http://') !== false || strpos($urlData[url], 'https://') !== false){
				header("Location:  $urlData[url]");
				die();
			}else{
				header("Location:  http://$urlData[url]");
				die();
			}
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>URL Shortener</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Koukakaijin URL Shortener">
    <meta name="author" content="Koukakaijin">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>

<body>

<div class="container">
	<?php echo "<p class='text-danger'>$errLoad</p>";?>
    <h1>Short an URL</h1> <br /><br />
    <form role="form" action="index.php" method="post"> 
		<div class="form-group">
			<div class ="row">
				<div class="col-xs-12 col-sm-6 col-lg-4">	
					<label>Provide an URL:</label>
					<input class="form-control" type="text" name="url" value="<?php echo $_POST['url']?>" /> 
					<?php echo "<p class='text-danger'>$errUrl</p>";?>
				</div>
			
				<?php echo empty($urlData['short']) ? '' : '<div class="col-xs-12 col-sm-6 col-lg-4"><label>This is your url</label>'; ?>
				<?php echo empty($urlData['short']) ? '' : '<input class="form-control" value="'.$myDomain.$urlData['short'].'" type="text" /></div>';?>
				
			</div>
		</div>
		<div class="form-group">
			<?php
				$human2=rand(1,6);
				$human3=rand(1,6);
				$human1=$human2+$human3;
			?>
			<label><?php echo "$human2"." + "."$human3"." = ?"?></label>
			<div class ="row">
				<div class="col-xs-12 col-sm-6 col-lg-4">	
					<input class="form-control" type="text" name="human" value="" /> 
					<?php echo "<p class='text-danger'>$errHuman</p>";?>
					<input class="hidden" type="text" name="human1" value="<?php echo "$human1" ?>" /> 
				</div>
			</div>
		</div>
		<input type="submit" class="btn btn-info" value="Short" /> 
    </form>
</div>

</body>
</html>