<?php
	require("config.php");
	function decode($str, $base=64, $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-') {
		$len = strlen($str);
		$val = 0;
		$arr = array_flip(str_split($chars));
		for($i = 0; $i < $len; ++$i) {
			$val += $arr[$str[$i]] * pow($base, $len-$i-1);
		}
		return $val;
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
		$stmt = $conn->prepare("SELECT url, short, times, uses FROM shortener WHERE id = ?");
		$stmt->bind_param("s", decode($id));
		$stmt->execute();
		$stmt->bind_result($url, $short, $times, $uses);
		
		$urlData = array();
		while ($stmt->fetch()) {
			$urlData['url'] = $url; //0
			$urlData['short'] = $short; 
			$urlData['times'] = $times; 
			$urlData['uses'] = $uses; 
		}
		//close connection
		$stmt->close();
		$conn->close();
		
		if (empty($url)){
			$errLoad = 'The url that you are looking for don\'t exists.';
		}else{
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>URL Shortener info page</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Koukakaijin URL Shortener info page">
    <meta name="author" content="Koukakaijin">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
	<link rel="stylesheet" href="assets/add.css">
</head>

<body>

<div class="container">
	<?php echo "<p class='text-danger'>$errLoad</p>";?>
    <h1>Information about an URL</h1> <br /><br />
	<?php if ($errLoad==""){echo "<p class='text-info'>Original url: <b>$url</b></p>";}?>
    <?php if ($errLoad==""){echo "<p class='text-info'>Shortened link: <b>$short</b></p>";}?>
    <?php if ($errLoad==""){echo "<p class='text-info'>Number of times used: <b>$uses</b></p>";}?>
    <?php if ($errLoad==""){echo "<p class='text-info'>Number of times shortened: <b>$times</b></p>";}?>
</div>

</body>
</html>