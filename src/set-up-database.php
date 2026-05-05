<?php

    if (session_status() == PHP_SESSION_NONE){
        session_start();
    }// end if

    if(isset($_SESSION["security-level"])){
        $lSecurityLevel = $_SESSION["security-level"];
    }else{
        $lSecurityLevel = 0;
    }

    //initialize custom error handler
    require_once 'classes/CustomErrorHandler.php';
    if (!isset($CustomErrorHandler)){
        $CustomErrorHandler = new CustomErrorHandler($lSecurityLevel);
    }// end if

    require_once 'classes/MySQLHandler.php';
    $MySQLHandler = new MySQLHandler($lSecurityLevel);
    
	function format($pMessage, $pLevel) {
		$styles = [
			"I" => "database-informative-message",
			"S" => "database-success-message",
			"F" => "database-failure-message",
			"W" => "database-warning-message"
		];
	
		// Use the level-specific style if it exists, otherwise default to "database-informative-message"
		$lStyle = $styles[$pLevel] ?? "database-informative-message";
	
		return "<div class=\"".$lStyle."\">" . htmlspecialchars($pMessage) . "</div>";
	}

	$lErrorDetected = false;
?>

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
	<html lang="en" xml:lang="en">
		<head>
			<title>Set Up Database</title>
			<link rel="shortcut icon" href="./images/favicon.ico" type="image/x-icon" />
    		<link rel="stylesheet" type="text/css" href="./styles/global-styles.css" />
    	</head>
    	<body>
    		<div>&nbsp;</div>
    		<div class="page-title">Setting up the database...</div><br /><br />
    		<div class="label" style="text-align: center;">If you see no error messages, it should be done.</div>
    		<div>&nbsp;</div>
    		<div class="label" style="text-align: center;"><a href="index.php">Continue back to the frontpage.</a></div>
    		<br />
    		<script>
    			try{
    				window.sessionStorage.clear();
    				window.localStorage.clear();
    			}catch(e){
    				alert("Error clearing HTML 5 Local and Session Storage" + e.toString());
    			};
    		</script>
    		<div class="database-success-message">HTML 5 Local and Session Storage cleared unless error popped-up already.</div>
<?php

    try{
    	echo format("Attempting to connect to MySQL server on host " . MySQLHandler::$mMySQLDatabaseHost . " with user name " . MySQLHandler::$mMySQLDatabaseUsername,"I");
    	$MySQLHandler->openDatabaseConnection();
    	echo format("Connected to MySQL server at " . MySQLHandler::$mMySQLDatabaseHost . " as " . MySQLHandler::$mMySQLDatabaseUsername,"I");

    	try{
    		echo format("Preparing to drop database " . MySQLHandler::$mMySQLDatabaseName,"I");
    		$lQueryString = "DROP DATABASE IF EXISTS " . MySQLHandler::$mMySQLDatabaseName;
    		$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    		if (!$lQueryResult) {
    			$lErrorDetected = true;
    			echo format("Was not able to drop database " . MySQLHandler::$mMySQLDatabaseName,"F");
    		}else{
    			echo format("Executed query 'DROP DATABASE IF EXISTS' for database " . MySQLHandler::$mMySQLDatabaseName . " with result ".$lQueryResult,"S");
    		}// end if
    	}catch(Exception $e){
    		// We do not want error dropping database to derail entire database setup.
    		echo format("Error was reported while attempting to drop database " . MySQLHandler::$mMySQLDatabaseName,"F");
    		echo format("MySQL sometimes throws errors attempting to drop databases. Here is error in case the error is serious.","I");
    		echo $CustomErrorHandler->FormatError($e, $lQueryString);
    	}//end try

    	echo format("Preparing to create database " . MySQLHandler::$mMySQLDatabaseName,"I");
    	$lQueryString = "CREATE DATABASE " . MySQLHandler::$mMySQLDatabaseName;
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
    		echo format("Was not able to create database " . MySQLHandler::$mMySQLDatabaseName,"F");
    	}else{
    		echo format("Executed query 'CREATE DATABASE' for database " . MySQLHandler::$mMySQLDatabaseName . " with result ".$lQueryResult,"S");
    	}// end if

    	echo format("Switching to use database " . MySQLHandler::$mMySQLDatabaseName,"I");
    	$lQueryString = "USE " . MySQLHandler::$mMySQLDatabaseName;
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
    		echo format("Was not able to use database " . MySQLHandler::$mMySQLDatabaseName,"F");
    	}else{
    		echo format("Executed query 'USE DATABASE' " . MySQLHandler::$mMySQLDatabaseName . " with result ".$lQueryResult,"I");
    	}// end if

		$lQueryString = "
			CREATE TABLE IF NOT EXISTS security_level (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				level INT NOT NULL DEFAULT 0
			);
		";
		
		$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
		
		if (!$lQueryResult) {
			$lErrorDetected = true;
			echo format("Failed to create 'security_level' table.", "F");
		} else {
			echo format("Successfully created 'security_level' table.", "S");
		
			// Ensure AUTO_INCREMENT starts at 1 (default behavior)
			$lAutoIncrementQuery = "ALTER TABLE security_level AUTO_INCREMENT = 1;";
			$lAutoIncrementResult = $MySQLHandler->executeQuery($lAutoIncrementQuery);
		
			if (!$lAutoIncrementResult) {
				$lErrorDetected = true;
				echo format("Failed to set AUTO_INCREMENT to 1.", "F");
			} else {
				echo format("AUTO_INCREMENT set to start at 1.", "S");
			}
		}
		
		// Optionally insert the initial value
		$lInsertQuery = "INSERT INTO security_level (level) VALUES (0)";
		$lInsertResult = $MySQLHandler->executeQuery($lInsertQuery);
		
		if (!$lInsertResult) {
			$lErrorDetected = true;
			echo format("Failed to insert initial security level.", "F");
		} else {
			echo format("Initial security level set to 0.", "S");
		}

    	$lQueryString = 'CREATE TABLE user_poll_results( '.
    			'cid INT NOT NULL AUTO_INCREMENT, '.
    			'tool_name TEXT, '.
    			'username TEXT, '.
    			'date DATETIME, '.
    			'PRIMARY KEY(cid))';
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'user_poll_results' table.", "F");
    	}else{
			echo format("Successfully created 'user_poll_results' table.", "S");
    	}// end if

    	$lQueryString = 'CREATE TABLE blogs_table( '.
    			 'cid INT NOT NULL AUTO_INCREMENT, '.
    	         'blogger_name TEXT, '.
    	         'comment TEXT, '.
    			 'date DATETIME, '.
    			 'PRIMARY KEY(cid))';
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'blogs_table' table.", "F");
    	}else{
			echo format("Successfully created 'blogs_table' table.", "S");
    	}// end if

		$lQueryString = 'CREATE TABLE accounts (
				cid INT NOT NULL AUTO_INCREMENT,
				username VARCHAR(255) NOT NULL UNIQUE,
				password VARCHAR(255) NOT NULL,
				mysignature TEXT,
				is_admin BOOLEAN DEFAULT FALSE,
				firstname VARCHAR(255),
				lastname VARCHAR(255),
				client_id CHAR(32) NOT NULL UNIQUE,
				client_secret VARCHAR(64) NOT NULL UNIQUE,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY(cid)
			)';
		$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'accounts' table.", "F");
    	}else{
			echo format("Successfully created 'accounts' table.", "S");
    	}// end if

    	$lQueryString = 'CREATE TABLE hitlog( '.
    			 'cid INT NOT NULL AUTO_INCREMENT, '.
    	         'hostname TEXT, '.
    	         'ip TEXT, '.
    			 'browser TEXT, '.
    			 'referer TEXT, '.
    			 'date DATETIME, '.
    			 'PRIMARY KEY(cid))';
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'hitlog' table.", "F");
    	}else{
			echo format("Successfully created 'hitlog' table.", "S");
    	}// end if

		$lQueryString = 'INSERT INTO accounts (username, password, mysignature, is_admin, firstname, lastname, client_id, client_secret) VALUES
			("admin", "adminpass", "g0t r00t?", true, "System", "Administrator", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("adrian", "somepassword", "Zombie Films Rock!", true, "Adrian", "Crenshaw", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("patches", "tortoise", "meow", false, "Patches", "Pester", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("jeremy", "password", "d1373 1337 speak", false, "Jeremy", "Druin", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("bryce", "password", "I Love SANS", false, "Bryce", "Galbraith", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("samurai", "samurai", "Carving fools", false, "Samurai", "WTF", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("dodgerjim", "password", "Rome is burning", false, "Jim", "Rome", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("bobby", "password", "Hank is my dad", false, "Bobby", "Hill", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("simba", "password", "I am a super-cat", false, "Simba", "Lion", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("dreveil", "password", "Preparation H", false, "Dr.", "Evil", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("scotty", "password", "Scotty do", false, "Scotty", "Evil", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("cal", "password", "C-A-T-S Cats Cats Cats", false, "John", "Calipari", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("john", "password", "Do the Duggie!", false, "John", "Wall", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("kevin", "42", "Doug Adams rocks", false, "Kevin", "Johnson", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("dave", "set", "Bet on S.E.T. FTW", false, "Dave", "Kennedy", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("rocky", "stripes", "treats?", false, "Rocky", "Paws", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("tim", "lanmaster53", "Because reconnaissance is hard to spell", false, "Tim", "Tomes", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("ABaker", "SoSecret", "Muffin tops only", true, "Aaron", "Baker", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("PPan", "NotTelling", "Where is Tinker?", false, "Peter", "Pan", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("CHook", "JollyRoger", "Gator-hater", false, "Captain", "Hook", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("james", "i<3devs", "Occupation: Researcher", false, "James", "Jardine", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("ed", "pentest", "Commandline KungFu anyone?", false, "Ed", "Skoudis", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("joe", "holly", "Off by one error", false, "Joe", "Holly", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("peter", "initech123", "I dont like my job", false, "Peter", "Gibbons", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("milton", "stapler", "Wheres my stapler?", false, "Milton", "Waddams", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("bill", "tpsreports", "Did you get the memo?", true, "Bill", "Lumbergh", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("samir", "nojoke123", "No one can pronounce my last name", false, "Samir", "Nagheenanajar", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("michael_b", "michael123", "We fixed the glitch", false, "Michael", "Bolton", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("michael_s", "worldsbestboss", "Worlds Best Boss", true, "Michael", "Scott", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("jim", "bearsbeatsbattlestar", "Pranking Dwight", false, "Jim", "Halpert", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("dwight", "assistant_to_the_regional_manager", "Bears. Beets. Battlestar Galactica.", false, "Dwight", "Schrute", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("pam", "pammy123", "Dunder Mifflin", false, "Pam", "Beesly", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("ryan", "temp123", "I am the temp", false, "Ryan", "Howard", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("derek", "bluestrike", "Im pretty sure theres more to life than being really, really ridiculously good looking", false, "Derek", "Zoolander", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("hansel", "hansel123", "Hansel. Hes so hot right now.", false, "Hansel", "", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("mugatu", "mugatu123", "I invented the piano key necktie!", true, "Jacobim", "Mugatu", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("matilda", "journalist123", "Investigative reporter", false, "Matilda", "Jeffries", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("stackhawk", "Kaakaww", "Swooping in for security", false, "Stack", "Hawk", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("zoolander", "zoolander123", "I am really, really, really, ridiculously good looking", false, "Derek", "Zoolander", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '"),
			("maury", "maury123", "Youre the guy who cant turn left", false, "Maury", "Ballstein", "' . bin2hex(random_bytes(16)) . '", "' . bin2hex(random_bytes(32)) . '");';
		$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
		if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to insert data into 'accounts' table.", "F");
    	}else{
			echo format("Successfully inserted data into 'accounts' table.", "S");
    	}// end if

		$lQueryString = "INSERT INTO `blogs_table` (`cid`, `blogger_name`, `comment`, `date`) VALUES
			(1, 'adrian', 'Welcome to my crappy blog software. :)', '2009-03-01 22:26:12'),
			(2, 'adrian', 'Looks like I got a lot more work to do. Fun, Fun, Fun!!!', '2009-03-01 22:26:54'),
			(3, 'anonymous', 'An anonymous blog? Huh?', '2009-03-01 22:27:11'),
			(4, 'ed', 'I love me some Netcat!!!', '2009-03-01 22:27:48'),
			(5, 'john', 'Listen to Pauldotcom!', '2009-03-01 22:29:04'),
			(6, 'jeremy', 'Mutillidae is fun', '2009-03-01 22:29:49'),
			(7, 'john', 'Chocolate is GOOD!!!', '2009-03-01 22:30:06'),
			(8, 'admin', 'Fear me, for I am ROOT!', '2009-03-01 22:31:13'),
			(9, 'dave', 'Social Engineering is woot-tastic', '2009-03-01 22:31:13'),
			(10, 'kevin', 'Read more Douglas Adams', '2009-03-01 22:31:13'),
			(11, 'jim', 'Bears eat beets', '2009-03-01 22:31:13'),
			(12, 'michael_s', 'I declare BANKRUPTCY!', '2024-10-07 09:00:00'),
			(13, 'jim', 'Just pulled off the ultimate prank on Dwight.', '2024-10-07 09:05:00'),
			(14, 'pam', 'Art school has been really fulfilling.', '2024-10-07 09:10:00'),
			(15, 'dwight', 'Bears. Beets. Battlestar Galactica.', '2024-10-07 09:15:00'),
			(16, 'ryan', 'Starting my new tech venture.', '2024-10-07 09:20:00'),
			(17, 'peter', 'Today, I didnt really do much work. Feels great.', '2024-10-07 09:25:00'),
			(18, 'milton', 'They took my stapler again...', '2024-10-07 09:30:00'),
			(19, 'bill', 'Did you get the memo?', '2024-10-07 09:35:00'),
			(20, 'samir', 'No one can still pronounce my last name...', '2024-10-07 09:40:00'),
			(21, 'michael_b', 'Its not that Michael Bolton!', '2024-10-07 09:45:00'),
(22, 'mugatu', 'The Derelicte campaign is going great!', '2024-10-07 09:50:00'),
			(23, 'derek', 'Being ridiculously good looking has its perks.', '2024-10-07 09:55:00')";
		$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to insert data into 'blogs_table' table.", "F");
    	}else{
			echo format("Successfully inserted data into 'blogs_table' table.", "S");
    	}// end if

    	$lQueryString = 'CREATE TABLE credit_cards( '.
    			 'ccid INT NOT NULL AUTO_INCREMENT, '.
    	         'ccnumber TEXT, '.
    	         'ccv TEXT, '.
    			 'expiration DATE, '.
    			 'PRIMARY KEY(ccid))';
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'credit_cards' table.", "F");
    	}else{
			echo format("Successfully created 'credit_cards' table.", "S");
    	}// end if

		$lQueryString = "INSERT INTO `credit_cards` (`ccid`, `ccnumber`, `ccv`, `expiration`) VALUES
			(1, AES_ENCRYPT('4111111111111111', 'encryption_key_here'), AES_ENCRYPT('123', 'encryption_key_here'), '2024-10-01 10:01:12'), -- Visa Test Card
			(2, AES_ENCRYPT('5555555555554444', 'encryption_key_here'), AES_ENCRYPT('321', 'encryption_key_here'), '2025-04-01 07:00:12'), -- Mastercard Test Card
			(3, AES_ENCRYPT('378282246310005', 'encryption_key_here'), AES_ENCRYPT('231', 'encryption_key_here'), '2026-03-01 11:55:12'), -- American Express Test Card
			(4, AES_ENCRYPT('6011111111111117', 'encryption_key_here'), AES_ENCRYPT('456', 'encryption_key_here'), '2027-06-01 04:33:12'), -- Discover Test Card
			(5, AES_ENCRYPT('4222222222222', 'encryption_key_here'), AES_ENCRYPT('789', 'encryption_key_here'), '2028-11-01 13:31:13'), -- Visa Short Test Card
			(6, AES_ENCRYPT('4000002760003184', 'encryption_key_here'), AES_ENCRYPT('123', 'encryption_key_here'), '2025-08-01 12:00:00'), -- Visa Debit Test Card
			(7, AES_ENCRYPT('2223000048400011', 'encryption_key_here'), AES_ENCRYPT('234', 'encryption_key_here'), '2026-09-01 09:30:45'), -- Mastercard Debit Test Card
			(8, AES_ENCRYPT('6011000990139424', 'encryption_key_here'), AES_ENCRYPT('345', 'encryption_key_here'), '2027-02-01 15:45:30'), -- Discover Debit Test Card
			(9, AES_ENCRYPT('4000000000000002', 'encryption_key_here'), AES_ENCRYPT('456', 'encryption_key_here'), '2025-05-01 08:15:00'), -- Visa Credit Test Card
			(10, AES_ENCRYPT('3566002020360505', 'encryption_key_here'), AES_ENCRYPT('567', 'encryption_key_here'), '2024-12-01 18:20:10'), -- JCB Test Card
			(11, AES_ENCRYPT('5038370200000000', 'encryption_key_here'), AES_ENCRYPT('678', 'encryption_key_here'), '2026-07-01 11:00:00') -- Maestro Test Card";
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to insert data into 'credit_cards' table.", "F");
    	}else{
			echo format("Successfully inserted data into 'credit_cards' table.", "S");
    	}// end if

    	$lQueryString =
    			'CREATE TABLE pen_test_tools('.
    			'tool_id INT NOT NULL AUTO_INCREMENT, '.
    	        'tool_name TEXT, '.
    	        'phase_to_use TEXT, '.
    			'tool_type TEXT, '.
    			'comment TEXT, '.
    			'PRIMARY KEY(tool_id))';
    	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);
    	if (!$lQueryResult) {
    		$lErrorDetected = true;
			echo format("Failed to create 'pen_test_tools' table.", "F");
    	}else{
			echo format("Successfully created 'pen_test_tools' table.", "S");
    	}// end if

		$lQueryString = "
		INSERT INTO `pen_test_tools` (`tool_id`, `tool_name`, `phase_to_use`, `tool_type`, `comment`) VALUES
		(1, 'Burp Suite Professional', 'Discovery', 'Interception Proxy', 'Advanced manual testing with automated scanning capabilities.'),
		(2, 'OWASP ZAP', 'Discovery', 'Interception Proxy', 'Free, open-source alternative to Burp Suite with active and passive scanning.'),
		(3, 'Nmap', 'Reconnaissance', 'Network Scanner', 'Highly versatile tool for network discovery and security auditing.'),
		(4, 'Nuclei', 'Discovery', 'Scanner', 'Automated scanner powered by custom templates for detecting vulnerabilities.'),
		(5, 'SQLMap', 'Exploitation', 'SQL Injection Tool', 'Automates the process of detecting and exploiting SQL injection flaws.'),
		(6, 'Metasploit Framework', 'Exploitation', 'Exploitation Framework', 'Comprehensive platform for developing, testing, and using exploits.'),
		(7, 'Recon-ng', 'Reconnaissance', 'Framework', 'Web reconnaissance framework with modular structure.
    	        (253, 'CUZgNTpJmJ4', 'Mutillidae: Lab 3 Walkthrough'),
    	        (254, '3yCX0MWV820', 'Mutillidae: Lab 4 Walkthrough'),
    	        (255, 'lU_fu-B5QtI', 'Mutillidae: Lab 5 Walkthrough'),
    	        (256, '6FXeO3Wx5wc', 'Mutillidae: Lab 6 Walkthrough'),
    	        (257, 'V_CsaO6RkvM', 'Mutillidae: Lab 7 Walkthrough'),
    	        (258, 'fVv39I0oXHE', 'Mutillidae: Lab 8 Walkthrough'),
    	        (259, 'KqoL60jtBWU', 'Mutillidae: Lab 9 Walkthrough'),
    	        (260, '8', 'Mutillidae: Lab 10 Walkthrough'),
    	        (261, '9', 'Mutillidae: Lab 11 Walkthrough'),
    	        (262, '10', 'Mutillidae: Lab 12 Walkthrough'),
    	        (263, '11', 'Mutillidae: Lab 13 Walkthrough'),
    	        (264, '12', 'Mutillidae: Lab 14 Walkthrough'),
    	        (265, '13', 'Mutillidae: Lab 15 Walkthrough'),
    	        (266, '14', 'Mutillidae: Lab 16 Walkthrough'),
    	        (267, 'UjEblUrWvb4', 'Mutillidae: Lab 17 Walkthrough'),
    	        (268, 'J2kq8EzhnCE', 'Mutillidae: Lab 18 Walkthrough'),
    	        (269, 'QSsLONLS5bk', 'Mutillidae: Lab 19 Walkthrough'),
    	        (270, 'PyHiBzfl0hI', 'Mutillidae: Lab 20 Walkthrough'),
    	        (271, 'NDITrGT9IqM', 'Mutillidae: Lab 21 Walkthrough'),
    	        (272, 'u8z7S1HlCHM', 'Mutillidae: Lab 22 Walkthrough'),
    	        (273, 'MfSKEtbMAjw', 'Mutillidae: Lab 23 Walkthrough'),
    	        (274, 'ydRZoXfZL9k', 'Mutillidae: Lab 24 Walkthrough'),
    	        (275, 'L0FvIGx0Co0', 'Mutillidae: Lab 25 Walkthrough'),
    	        (276, 'TzhKue6jmN0', 'Mutillidae: Lab 26 Walkthrough'),
    	        (277, 'lvVMnPQX8tE', 'Mutillidae: Lab 27 Walkthrough'),
    	        (278, 'UJunZ3Vadsc', 'Mutillidae: Lab 28 Walkthrough'),
    	        (279, 'BT6LrYLKE4A', 'Mutillidae: Lab 29 Walkthrough'),
    	        (280, 'rt-GoLEs6L4', 'Mutillidae: Lab 30 Walkthrough'),
    	        (281, 'OvDnbPbborM', 'Mutillidae: Lab 31 Walkthrough'),
                (282, 'y9vhp0llgNc', 'Mutillidae: Lab 32 Walkthrough'),
                (283, 'BlwFGkj79vk', 'Mutillidae: Lab 33 Walkthrough'),
                (284, 'ERhowYml8Ms', 'Mutillidae: Lab 34 Walkthrough'),
                (285, '6y5jl0y8Ukc', 'Mutillidae: Lab 35 Walkthrough'),
                (286, 'y1EDT6UTvqA', 'Mutillidae: Lab 36 Walkthrough'),
                (287, 'qIT-Hc_RJZI', 'Mutillidae: Lab 37 Walkthrough'),
                (288, 'NtkXw02MsQ4', 'Mutillidae: Lab 38 Walkthrough'),
                (289, '37', 'Mutillidae: Lab 39 Walkthrough'),
                (290, '38', 'Mutillidae: Lab 40 Walkthrough'),
                (291, '39', 'Mutillidae: Lab 41 Walkthrough'),
                (292, 'fyVmA7nlSVo', 'Mutillidae: Lab 42 Walkthrough'),
                (293, 'kr4KD9RJ-hA', 'Mutillidae: Lab 43 Walkthrough'),
                (294, 'ETK7l27eZTs', 'Mutillidae: Lab 44 Walkthrough'),
                (295, 'THnZOa93SOs', 'Mutillidae: Lab 45 Walkthrough'),
                (296, 'LkH_qRqcJzo', 'Mutillidae: Lab 46 Walkthrough'),
                (297, 'ICXErYUJ3sU', 'Mutillidae: Lab 47 Walkthrough'),
                (298, '46', 'Mutillidae: Lab 48 Walkthrough'),
                (299, 'Ddnid67vqq4', 'Mutillidae: Lab 49 Walkthrough'),
                (300, 'px40hbprIOM', 'Mutillidae: Lab 50 Walkthrough'),
                (301, 'DGYVXsrZrug', 'Mutillidae: Lab 51 Walkthrough'),
                (302, 'JJOqPu_oyi8', 'Mutillidae: Lab 52 Walkthrough'),
                (303, 'Gxm1_bkYcZ4', 'Mutillidae: Lab 53 Walkthrough'),
                (304, 'NF8dxs_CQA0', 'Mutillidae: Lab 54 Walkthrough'),
                (305, 'x7ibzMx4c3c', 'Mutillidae: Lab 55 Walkthrough'),
                (306, '54', 'Mutillidae: Lab 56 Walkthrough'),
                (307, '55', 'Mutillidae: Lab 57 Walkthrough'),
                (308, '56', 'Mutillidae: Lab 58 Walkthrough'),
                (309, '57', 'Mutillidae: Lab 59 Walkthrough'),
    	        (310, 'sVgXHH9GSyk', 'Mutillidae: Lab 60 Walkthrough'),
    	        (311, '6BIdjAYCyKc', 'Mutillidae: Lab 61 Walkthrough'),
    	        (312, 'z0USLZLCPPE', 'Mutillidae: Lab 62 Walkthrough'),
    	    	(313, '2fQfma45UMc', 'Mutillidae: Lab 63 Walkthrough'),
                (314, 'Y4TWdPZp2eA', 'Mutillidae: Lab 51 Walkthrough - Alternate Method')";

    $lQueryResult = $MySQLHandler->executeQuery($lQueryString);
	if (!$lQueryResult) {
		$lErrorDetected = true;
		echo format("Failed to insert data into 'youTubeVideos' table.", "F");
	}else{
		echo format("Successfully inserted data into 'youTubeVideos' table.", "S");
	}// end if

	/* ***********************************************************************************
	 * Create accounts.xml password.txt file from MySQL accounts table
	 * ************************************************************************************/
	$lAccountXMLFilePath="data/accounts.xml";
	$lPasswordFilePath="passwords/accounts.txt";

	echo format("Trying to build XML version of accounts table to update accounts XML ".$lAccountXMLFilePath,"I");
	echo format("Do not worry. A default version of the file is included if this does not work.","I");

	echo format("Trying to build text version of accounts table to update password text file ".$lPasswordFilePath,"I");
	echo format("Do not worry. A default version of the file is included if this does not work.","I");

	$lAccountsXML = "";
	$lAccountsText = "";
	$lQueryString = "SELECT * FROM accounts;";
	$lQueryResult = $MySQLHandler->executeQuery($lQueryString);

	if (isset($lQueryResult->num_rows) && $lQueryResult->num_rows > 0) {
		$lResultsFound = true;
		$lRecordsFound = $lQueryResult->num_rows;
	}//end if

	if ($lResultsFound){

		echo format("Executed query 'SELECT * FROM accounts'. Found ".$lRecordsFound." records.","S");

		$lAccountsXML = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
		$lAccountsXML .= "<Accounts>".PHP_EOL;
		$lCounter = 1;
		$cTAB = CHR(9);
		
		$lAccountsText = "CID,Username,Password,Signature,Type,FirstName,LastName,ClientID,ClientSecret".PHP_EOL; // Add CSV header
		
		while($row = $lQueryResult->fetch_object()){
			$lAccountType = $row->is_admin == 'TRUE' ? "Admin" : "User";
			
			// XML Generation
			$lAccountsXML .= $cTAB.'<Account ID="'.$lCounter.'">'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<CID>'.htmlspecialchars($row->cid).'</CID>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<UserName>'.htmlspecialchars($row->username).'</UserName>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<Password>'.htmlspecialchars($row->password).'</Password>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<Signature>'.htmlspecialchars($row->mysignature).'</Signature>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<Type>'.htmlspecialchars($lAccountType).'</Type>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<FirstName>'.htmlspecialchars($row->firstname).'</FirstName>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<LastName>'.htmlspecialchars($row->lastname).'</LastName>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<ClientID>'.htmlspecialchars($row->client_id).'</ClientID>'.PHP_EOL;
			$lAccountsXML .= $cTAB.$cTAB.'<ClientSecret>'.htmlspecialchars($row->client_secret).'</ClientSecret>'.PHP_EOL;
			$lAccountsXML .= $cTAB.'</Account>'.PHP_EOL;
		
			// CSV Generation
			$lAccountsText .= $row->cid.","
						   . $row->username.","
						   . $row->password.","
						   . $row->mysignature.","
						   . $lAccountType.","
						   . $row->firstname.","
						   . $row->lastname.","
						   . $row->client_id.","
						   . $row->client_secret.PHP_EOL;
		
			$lCounter += 1;
		}
		
		$lAccountsXML .= "</Accounts>".PHP_EOL;
		
		try {
			// Ensure the directories exist and are writable
			if (!is_dir(pathinfo($lAccountXMLFilePath, PATHINFO_DIRNAME))) {
				echo format("Oh no. Trying to create an XML version of the accounts file did not work out. The directory " . $lAccountXMLFilePath . " does not exist.","F");
			}
			if (!is_dir(pathinfo($lPasswordFilePath, PATHINFO_DIRNAME))) {
				echo format("Oh no. Trying to create a text version of the accounts file did not work out. The directory " . $lPasswordFilePath . " does not exist.","F");
			}
			if (!is_writable(pathinfo($lAccountXMLFilePath, PATHINFO_DIRNAME))) {
				echo format("Oh no. Trying to create an XML version of the accounts file did not work out. The directory " . $lAccountXMLFilePath . " is not writable.","F");
			}
			if (!is_writable(pathinfo($lPasswordFilePath, PATHINFO_DIRNAME))) {
				echo format("Oh no. Trying to create a text version of the accounts file did not work out. The directory " . $lPasswordFilePath . " is not writable.","F");
			}

			// XML File Writing
			try {
				if (is_writable(pathinfo($lAccountXMLFilePath, PATHINFO_DIRNAME))) {
					file_put_contents($lAccountXMLFilePath, $lAccountsXML);
					echo format("Wrote accounts to " . $lAccountXMLFilePath, "S");
				}else{
					echo format("Could not write accounts XML to " . $lAccountXMLFilePath . " - Directory not writable", "W");
					echo format("Using default version of accounts.xml", "W");
				}
			} catch (Exception $e) {
				echo format("Could not write accounts XML to " . $lAccountXMLFilePath . " - " . $e->getMessage(), "W");
				echo format("Using default version of accounts.xml", "W");
			}
		
			// Text File Writing
			try {
				if (is_writable(pathinfo($lPasswordFilePath, PATHINFO_DIRNAME))) {
					file_put_contents($lPasswordFilePath, $lAccountsText);
					echo format("Wrote accounts to " . $lPasswordFilePath, "S");
				}else{
					echo format("Could not write accounts text to " . $lPasswordFilePath . " - Directory not writable", "W");
					echo format("Using default version of accounts.txt", "W");
				}
			} catch (Exception $e) {
				echo format("Could not write accounts text to " . $lPasswordFilePath . " - " . $e->getMessage(), "W");
				echo format("Using default version of accounts.txt", "W");
			}
		
		} catch (Exception $e) {
			$lErrorDetected = true;
			echo $CustomErrorHandler->FormatError($e, $lQueryString);
		}
		
	} else {
		$lErrorDetected = true;
		echo format("Warning: No records found when trying to build XML and text version of accounts table ".$lQueryResult,"W");
	}// end if ($lResultsFound)

	$MySQLHandler->closeDatabaseConnection();

} catch (Exception $e) {
	$lErrorDetected = true;
	echo $CustomErrorHandler->FormatError($e, $lQueryString);
}// end try

if (!$lErrorDetected) {
    // Check for HTTP referer (only if available)
    $lHTTPReferer = $_SERVER["HTTP_REFERER"] ?? "";

    // Determine if the referer matches certain conditions
    $lReferredFromOfflinePage = strpos($lHTTPReferer, "database-offline.php") !== false;

    // Redirect to home page if offline page triggered the reset
    if ($lReferredFromOfflinePage) {
        $lRedirectLocation = "index.php?page=home.php&popUpNotificationCode=SUD1";
    } else {
        $lRedirectLocation = "index.php?page=home.php";
    }

    // JavaScript-based redirect to ensure the session is cleared before the redirect
    echo "<script>
        if (confirm('Database reset successful. Click OK to continue to the home page. Click Cancel to stay on this page.')) {
            window.location.href = '$lRedirectLocation';
        }
    </script>";
}

$CustomErrorHandler = null;
?>
	</body>
</html>