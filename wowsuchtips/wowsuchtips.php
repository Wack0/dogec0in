<?php
// (c) 2013-2014 slipstream

include("SmartIRC.php");
include("jsonRPCClient.php");
include("PasswordHash.php");
include("Dogecoin.php");

$dogetip = new jsonRPCClient(""); // tipbot dogecoind
$p = new PasswordHash(8,false);
$sql = explode(":","localhost:user:pass:dbname");
if (!file_exists("../wowsuchbots.db")) {
	$temp = new SQLite3("../wowsuchbots.db");
	$temp->exec("CREATE TABLE IF NOT EXISTS given(nick text unique,time unsigned bigint,wait unsigned bigint)");
	$temp->exec("CREATE TABLE IF NOT EXISTS ident(nick text unique,address text,host text)");
	$temp->exec("CREATE TABLE IF NOT EXISTS newtips(id unsigned bigint,nick text,address text,amount text)");

	// Do we have a db dump?
	if (file_exists(".dbdump")) {
		// yes we do.
		$file = explode("\n",file_get_contents(".dbdump"));
		foreach ($file as $line) $temp->exec("insert into ident (nick,address,host) values ".$line);
		unlink(".dbdump");
	}
}

define("CTRL_B","\002");
define("CTRL_K","\003");
define("CTRL_U","\037");
define("CTRL_I","\035");
define("CTRL_O","\017");
define("CTRL_R","\026");

define("SLIP_QUIT",-1);
define("SLIP_BNC",0);
define("SLIP_ON",1);

class wowsuchdoge {
	var $slipon = SLIP_QUIT;
	var $vipprices = array(1=>20,7=>125,31=>250,182=>1000,365=>1500);
	var $adprices = array(1=>array(150,200),7=>array(500,600),31=>array(1500,1600),182=>array(6000,7000),365=>array(10000,12000));
	function onJoin(&$irc,&$data) {
		if (($this->slipon == SLIP_QUIT) && ($data->nick == "slipstream")) $this->slipon = SLIP_ON;
		elseif (($this->slipon == SLIP_QUIT) && ($data->nick == "slipstre[a]m")) $this->slipon = SLIP_BNC;
		elseif ($data->nick == $irc->_nick) {
			if ($irc->isJoined("#dogec0in","slipstream")) $this->slipon = SLIP_ON;
			elseif ($irc->isJoined("#dogec0in","slipstre[a]m")) $this->slipon = SLIP_BNC;
			return;
		}
	}
	function onNickChange(&$irc,&$data) {
		if (($data->nick == "slipstream") && ($newnick == "slipstre[a]m")) $this->slipon = SLIP_BNC;
		elseif (($data->nick == "slipstre[a]m") && ($newnick == "slipstream")) $this->slipon = SLIP_ON;
	}
	function sqlConnect() {
		global $sql;
		$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
		if ($s->connect_error) return false;
		return $s;
	}
	function sqliteConnect() {
		$s = new SQLite3("../wowsuchbots.db");
		$s->busyTimeout(5000);
		return $s;
	}
	function msgSlip($irc,$msg) {
		if ($this->slipon == SLIP_ON) $irc->message(SMARTIRC_TYPE_QUERY,"slipstream",$msg);
		elseif ($this->slipon == SLIP_BNC) $irc->message(SMARTIRC_TYPE_QUERY,"slipstre[a]m",$msg);
		else $irc->message(SMARTIRC_TYPE_QUERY,"memoserv","send slipstream ".$msg);
	}
	function msgExit(&$irc,&$data) {
		// Dumps the DB and exits. Now i should find it easier to restart this damn bot :-)
		if ($data->nick != "slipstream") return;
		if ($data->ident != "raylee") return;
		if ($data->host != "going.the.extra.mile.to.make.you.smile") return;
		$msg = str_replace("exit ","",$data->message);
		// No need to dump the db anymoar now it's no longer stored completely in RAM
		$irc->_send('QUIT :wowsuchtips exiting: '.$msg, SMARTIRC_CRITICAL);
		usleep(100000);
		$irc->disconnect(true);
		exit();
	}
	function stripControlCharacters($text) {
		$controlCodes = array(
			'/(\x03(?:\d{1,2}(?:,\d{1,2})?)?)/',    // Color code
			'/\x02/',                               // Bold
			'/\x0F/',                               // Escaped
			'/\x1D/',                               // Italic
			'/\x1F/',                               // Underline
			'/\x0F/',								// Original
			'/\x16/'								// Reverse
		);
		return preg_replace($controlCodes,'',$text);
	}
	function hasControlCharacters($text) {
		$controlCodes = array(
			'/(\x03(?:\d{1,2}(?:,\d{1,2})?)?)/',    // Color code
			'/\x02/',                               // Bold
			'/\x0F/',                               // Escaped
			'/\x1D/',                               // Italic
			'/\x1F/',                               // Underline
			'/\x0F/',								// Original
			'/\x16/'								// Reverse
		);
		return preg_filter($controlCodes,'',$text) !== NULL;
	}
	function msgInfo(&$irc,&$data) {
		global $dogetip;
		// is this user identified?
		$temp = $this->sqliteConnect();
		ob_start();
		$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		$buff = ob_get_contents();
		ob_end_clean();
		$temp->close();
		if (strpos($buff,"locked") !== false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"The database is locked. Please try again later.");
			return;
		}
		if ($addy == false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
			return;
		}
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
		if (!$r->num_rows) {
			// something fucked up.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. The administrator has been notified. Try again later.");
			$this->msgSlip($irc,"Couldn't find a userid in the mysql db for identified user with address ".$addy);
			$s->close();
			return;
		}
		$id = $r->fetch_object()->id;
		$s->close();
		try {
			$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		if (!array_key_exists("dogec0in_".$id,$accounts)) {
			// no account.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You do not have a tipping account. To create one, PM me with the text ".CTRL_B."tipcreate");
			return;
		}
		$amount = $accounts['dogec0in_'.$id];
		try {
			$tipaddress = $dogetip->getaddressesbyaccount("dogec0in_".$id);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		$tipaddress = $tipaddress[0];
		$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You have ".$amount." DOGE in your tipping account. To get more, send dogecoins to the address ".CTRL_B.$tipaddress.CTRL_B);
		$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"To tip someone dogecoins, use ".CTRL_B."!tip nick amount".CTRL_B." in a channel wowsuchdoge is in. ".CTRL_B."amount".CTRL_B." is always in Dogecoins.");
		$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"To withdraw dogecoins from your tipping account, PM me with the text ".CTRL_B."withdraw address amount".CTRL_B." - ".CTRL_B."address".CTRL_B." can be ".CTRL_B."waterbowl".CTRL_B." and ".CTRL_B."amount".CTRL_B." can be ".CTRL_B."all");
		return;
	}
	function msgTipcreate(&$irc,&$data) {
		global $dogetip;
		// is this user identified?
		$temp = $this->sqliteConnect();
		ob_start();
		$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		$buff = ob_get_contents();
		ob_end_clean();
		$temp->close();
		if (strpos($buff,"locked") !== false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"The database is locked. Please try again later.");
			return;
		}
		if ($addy == false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
			return;
		}
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
		if (!$r->num_rows) {
			// something fucked up.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. The administrator has been notified. Try again later.");
			$this->msgSlip($irc,"Couldn't find a userid in the mysql db for identified user with address ".$addy);
			$s->close();
			return;
		}
		$id = $r->fetch_object()->id;
		$s->close();
		try {
			$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		if (array_key_exists("dogec0in_".$id,$accounts)) {
			// this user has an account already.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You already have a tipping account. To view information about it, PM me with the text ".CTRL_B."info");
			return;
		}
		// create the tipping account
		try {
			$tipaddress = $dogetip->getaccountaddress("dogec0in_".$id);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Your new tipping account has been created. To deposit to your tipping account, send dogecoins to the address ".CTRL_B.$tipaddress.CTRL_B);
		return;
	}
	function msgWithdraw(&$irc,&$data) {
		global $dogetip;
		$msg = explode(" ",str_replace("withdraw ","",$data->message));
		if (count($msg) < 2) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Usage: withdraw ".CTRL_B."address".CTRL_B." ".CTRL_B."amount".CTRL_B);
			return;
		}
		if (strtolower($msg[0]) == "waterbowl")
			$msg[0] = "DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG";
		if (!Dogecoin::checkAddress($msg[0])) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Usage: withdraw ".CTRL_B."address".CTRL_B." ".CTRL_B."amount".CTRL_B);
			return;
		}
		// is this user identified?
		$temp = $this->sqliteConnect();
		ob_start();
		$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		$buff = ob_get_contents();
		ob_end_clean();
		$temp->close();
		if (strpos($buff,"locked") !== false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"The database is locked. Please try again later.");
			return;
		}
		if ($addy == false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
			return;
		}
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
		if (!$r->num_rows) {
			// something fucked up.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. The administrator has been notified. Try again later.");
			$this->msgSlip($irc,"Couldn't find a userid in the mysql db for identified user with address ".$addy);
			$s->close();
			return;
		}
		$id = $r->fetch_object()->id;
		$s->close();
		try {
			$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		if (!array_key_exists("dogec0in_".$id,$accounts)) {
			// no account.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You do not have a tipping account. To create one, PM me with the text ".CTRL_B."tipcreate");
			return;
		}
		$amount = $accounts['dogec0in_'.$id];
		if (strtolower($msg[1]) == "all")
			$msg[1] = $amount;
		else
			$msg[1] = (float)filter_var($msg[1],FILTER_VALIDATE_FLOAT);
		if ($msg[1] < 1) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Usage: withdraw ".CTRL_B."address".CTRL_B." ".CTRL_B."amount".CTRL_B);
			return;
		}
		if ($amount < $msg[1]) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You only have ".$amount." in your tipping account.");
			return;
		}
		try {
			$dogetip->sendfrom("dogec0in_".$id,$msg[0],$msg[1]);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,CTRL_B.base64_decode("w7jCsA==")."wow much withdrawn".base64_decode("wrDDuA==").CTRL_B.": ".$data->nick." -> ".$msg[0]." __ ".CTRL_B.base64_decode("w5A=").$msg[1]);
		if ($msg[0] == "DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG") $irc->message(SMARTIRC_TYPE_CHANNEL,"#dogec0in",CTRL_B."DONATE".CTRL_B.": ".$data->nick." just donated ".base64_decode("w5A=").$msg[1]." to the waterbowl!");
		return;
	}
	function msgGetvip(&$irc,&$data) {
		global $dogetip;
		$msg = explode(" ",str_replace("getvip ","",$data->message));
		if ($msg[0] == "") {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Usage: getvip ".CTRL_B."number".CTRL_B." d/day/days/w/week/weeks/m/month/months/y/year/years (default is days)");
			return;
		}
		if (count($msg) == 1) {
			$lastchr = substr($msg[0],-1);
			if ((int)filter_var($lastchr,FILTER_VALIDATE_INT) < 1) {
				// this should be "d/w/m/y"
				$msg[0] = substr($msg[0],0,-1);
				$msg[] = $lastchr; // error checking later
			} else $msg[] = "d"; // default to days
		} else $msg[1] = substr($msg[1],0,1);
		$time = (int)filter_var($msg[0],FILTER_VALIDATE_INT);
		if ($time < 0) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You have specified an incorrect time value.");
			return;
		}
		$numdays = 0;
		if ($msg[1] == "y") {
			// years
			if ($time == 1) $numdays = 365;
		} elseif ($msg[1] == "m") {
			// months
			if ($time == 1) $numdays = 31;
			elseif ($time == 6) $numdays = 182;
			elseif ($time == 12) $numdays = 365;
		} elseif ($msg[1] == "w") {
			// weeks
			if ($time == 1) $numdays = 7;
			elseif ($time == 4) $numdays = 31;
			elseif ($time == 26) $numdays = 182;
			elseif ($time == 52) $numdays = 365;
		} elseif ($msg[1] == "d") {
			// days
			if (array_key_exists($time,$this->vipprices)) $numdays = $time;
		}
		if ($numdays == 0) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You have specified an incorrect time value.");
			return;
		}
		$numdoge = $this->vipprices[$numdays];
		// is this user identified?
		$temp = $this->sqliteConnect();
		ob_start();
		$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		$buff = ob_get_contents();
		ob_end_clean();
		$temp->close();
		if (strpos($buff,"locked") !== false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"The database is locked. Please try again later.");
			return;
		}
		if ($addy == false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
			return;
		}
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
		if (!$r->num_rows) {
			// something fucked up.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. The administrator has been notified. Try again later.");
			$this->msgSlip($irc,"Couldn't find a userid in the mysql db for identified user with address ".$addy);
			$s->close();
			return;
		}
		$id = $r->fetch_object()->id;
		try {
			$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		if (!array_key_exists("dogec0in_".$id,$accounts)) {
			// no account.
			$s->close();
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You do not have a tipping account. To create one, PM me with the text ".CTRL_B."tipcreate");
			return;
		}
		$amount = $accounts['dogec0in_'.$id];
		if ($amount < $numdoge) {
		$s->close();
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You only have ".$amount." in your tipping account.");
			return;
		}
		// send DOGE to waterbowl
		try {
			$dogetip->sendfrom("dogec0in_".$id,"DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG",$numdoge);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		// give them vip
		$r = $s->query("select time from voice where uid=".$id);
		if (!$r->num_rows) {
			$timeval = (time() + (86400*$numdays));
			$r = $s->query("insert into voice (uid,time) values (".($id).",".$timeval.")");
			if (strlen($data->ident) == 11)
				$clienttext =  "Please ".CTRL_B."REFRESH NOW".CTRL_B." to fully activate your VIP status.";
			else {
				$clienttext = "You now have access to join the channel ".CTRL_B."#dogec0in-vip".CTRL_B." !";
				// give them voice
				$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","voice #dogec0in ".$data->nick);
				// invite them to vip channel
				$irc->invite($data->nick,"#dogec0in-vip");
			}
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Your VIP status has now been added, it will expire at ".date('r',$timeval).". ".$clienttext);
		} else {
			$timeval = ($r->fetch_object()->time + (86400*$numdays));
			$r = $s->query("update voice set time=".$timeval." where uid=".$id);
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Your VIP status has now been extended, it will now expire at ".date('r',$timeval).".");
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,"#dogec0in",CTRL_B."DONATE".CTRL_B.": ".$data->nick." just donated ".base64_decode("w5A=").$numdoge." to the waterbowl!");
		$s->close();
	}
	function msgGetad(&$irc,&$data) {
		global $dogetip;
		$msg = explode(" ",str_replace("getad ","",$data->message));
		if (($msg[0] == "") || (count($msg) < 3)) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Usage: getad ".CTRL_B."number".CTRL_B."d/day/days/w/week/weeks/m/month/months/y/year/years ".CTRL_B."adtext");
			return;
		}
		$lastchr = substr($msg[0],-1);
		if ((int)filter_var($lastchr,FILTER_VALIDATE_INT) < 1) {
			// this should be "d/w/m/y"
			$msg[0] = substr($msg[0],0,-1);
			$daytext = $lastchr; // error checking later
			array_splice($msg,1,0,$daytext);
		} else {
			$lastchr = substr($msg[1],0,1);
			if ((($lastchr == "d") || ($lastchr == "w") || ($lastchr == "m") || ($lastchr == "y"))
			&& (($msg[1] == $lastchr) || ($msg[1] == "day") || ($msg[1] == "days") || ($msg[1] == "week") || ($msg[1] == "weeks") || ($msg[1] == "month") || ($msg[1] == "months") || ($msg[1] == "year") || ($msg[1] == "years"))) {
				$daytext = $lastchr;
				$msg[1] = $daytext;
			} else {
				$daytext = "d"; // default to days
				array_splice($msg,1,0,$daytext);
			}
		}
		$time = (int)filter_var($msg[0],FILTER_VALIDATE_INT);
		if ($time < 0) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You have specified an incorrect time value.");
			return;
		}
		$numdays = 0;
		if ($msg[1] == "y") {
			// years
			if ($time == 1) $numdays = 365;
		} elseif ($msg[1] == "m") {
			// months
			if ($time == 1) $numdays = 31;
			elseif ($time == 6) $numdays = 182;
			elseif ($time == 12) $numdays = 365;
		} elseif ($msg[1] == "w") {
			// weeks
			if ($time == 1) $numdays = 7;
			elseif ($time == 4) $numdays = 31;
			elseif ($time == 26) $numdays = 182;
			elseif ($time == 52) $numdays = 365;
		} elseif ($msg[1] == "d") {
			// days
			if (array_key_exists($time,$this->vipprices)) $numdays = $time;
		}
		if ($numdays == 0) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You have specified an incorrect time value.");
			return;
		}
		$msg2 = $msg;
		array_splice($msg2,0,2);
		$msg2 = implode(" ",$msg2);
		$numdoge = $this->adprices[$numdays][(int)$this->hasControlCharacters($msg2)];
		// is this user identified?
		$temp = $this->sqliteConnect();
		ob_start();
		$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		$buff = ob_get_contents();
		ob_end_clean();
		$temp->close();
		if (strpos($buff,"locked") !== false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"The database is locked. Please try again later.");
			return;
		}
		if ($addy == false) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
			return;
		}
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
		if (!$r->num_rows) {
			// something fucked up.
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. The administrator has been notified. Try again later.");
			$this->msgSlip($irc,"Couldn't find a userid in the mysql db for identified user with address ".$addy);
			$s->close();
			return;
		}
		$id = $r->fetch_object()->id;
		try {
			$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		if (!array_key_exists("dogec0in_".$id,$accounts)) {
			// no account.
			$s->close();
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You do not have a tipping account. To create one, PM me with the text ".CTRL_B."tipcreate");
			return;
		}
		$amount = $accounts['dogec0in_'.$id];
		if ($amount < $numdoge) {
			$s->close();
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You only have ".$amount." in your tipping account.");
			return;
		}
		// send DOGE to waterbowl
		try {
			$dogetip->sendfrom("dogec0in_".$id,"DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG",$numdoge);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"An internal error occured. Try again later.");
			return;
		}
		// add the ad to the rotation.
		$expirytime = (time() + (86400*$numdays));
		$r = $s->query("insert into ads (text,time) values ('".base64_encode($msg2)."',".$expirytime.")");
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Your ad has been added to the rotation and will expire on ".date('r',$expirytime).". Ask slipstream if you want to display it once in the channel for testing; he'll need the following ID: ".$s->insert_id);
		$irc->message(SMARTIRC_TYPE_CHANNEL,"#dogec0in",CTRL_B."DONATE".CTRL_B.": ".$data->nick." just donated ".base64_decode("w5A=").$numdoge." to the waterbowl!");
		$s->close();
	}
	function chanMsg(&$irc,&$data) {
		global $dogetip;
		if ($data->nick == $irc->_nick) return;
		$data->message = $this->stripControlCharacters($data->message);
		if (($data->message == "!currtips") || (preg_match("/^\!currtips\s/",$data->message))) {
			try {
				$balance = $dogetip->getbalance();
			} catch (Exception $e) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"An internal error occured. Try again later.");
				return;
			}
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."There is currently ".$balance." DOGE in everybody's tipping accounts.");
			return;
		} else if (($data->message == "!vip") || (preg_match("/^\!vip\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"Benefits of VIP: a status symbol in the channel, a higher chance of getting dogecoins (1/9), more emoticons, and access to a private VIP channel. ".$this->vipprices[1]." DOGE = 1 day, ".$this->vipprices[7]." DOGE = 1 week, ".$this->vipprices[31]." DOGE = 1 month, ".$this->vipprices[182]." DOGE = 6 months, ".$this->vipprices[365]." DOGE = 1 year. To get VIP, ".CTRL_B."/msg wowsuchtips getvip number d/w/m/y");
			return;
		} else if (($data->message == "!ads") || (preg_match("/^\!ads\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"Ads show every 10 minutes in the channel. To buy one ".CTRL_B."/msg wowsuchtips getad number d/w/m/y adtext".CTRL_B." , it doesn't have to be dogecoin related. ".$this->adprices[1][0]." DOGE = 1 day (".$this->adprices[1][1]." w/ colour), ".$this->adprices[7][0]." DOGE = 1 week (".$this->adprices[7][1]." w/ colour), ".$this->adprices[31][0]." DOGE = 1 month (".$this->adprices[31][1]." w/ colour), ".$this->adprices[182][0]." DOGE = 6 months (".$this->adprices[182][1]." w/ colour), ".$this->adprices[365][0]." = 1 year (".$this->adprices[365][1]." w/ colour)");
			return;
		} else if (($data->message == "!help") || (preg_match("/^\!help\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"My triggers: !help !ads !vip !tip !currtips");
			return;
		} else if (preg_match("/^\!tip\s/",$data->message)) {
			$msg = explode(" ",$data->message);
			if (count($msg) < 3) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: !tip nick amount");
				return;
			}
			$msg[2] = (float)filter_var($msg[2],FILTER_VALIDATE_FLOAT);
			if ($msg[2] < 1) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: !tip nick amount");
				return;
			}
			if ($msg[2] < 1) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"The minimum tipping amount is 1 DOGE.");
				return;
			}
			// is this user identified?
			$temp = $this->sqliteConnect();
			ob_start();
			$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($msg[1])."' COLLATE NOCASE");
			$buff = ob_get_contents();
			ob_end_clean();
			$temp->close();
			if (strpos($buff,"locked") !== false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"The database is locked. Please try again later.");
				return;
			}
			if ($addy == false) {
				// nope. let's check the usertable.
				$s = $this->sqlConnect();
				if ($s === false) {
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
					return;
				}
				$r = $s->query("select id from users where username='".$s->real_escape_string($msg[1])."'");
				if (!$r->num_rows) {
					// not here either.
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Cannot find that user...");
					$s->close();
					return;
				}
				$id = $r->fetch_object()->id;
			} else {
				// yes, get the user id from the dogecoin address.
				$s = $this->sqlConnect();
				if ($s === false) {
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
					return;
				}
				$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
				if (!$r->num_rows) {
					// something fucked up.
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Cannot find that user...");
					$s->close();
					return;
				}
				$id = $r->fetch_object()->id;
			}
			// get the id of the user making the tip
			$temp = $this->sqliteConnect();
			ob_start();
			$addy = $temp->querySingle("select address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
			$buff = ob_get_contents();
			ob_end_clean();
			$temp->close();
			if (strpos($buff,"locked") !== false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"The database is locked. Please try again later.");
				return;
			}
			if ($addy == false) {
				// not identified.
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You are not identified. If you are using an IRC client, identify to wowsuchdoge first. If you are using the webchat, refresh and try again.");
				$s->close();
				return;
			}
			// get the uid of this user.
			$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
			if (!$r->num_rows) {
				// something fucked up.
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Cannot find that user...");
				$s->close();
				return;
			}
			$tipperid = $r->fetch_object()->id;
			// we shouldn't need mysql again.
			$s->close();
			// is the tipper trying to send to themselves?
			if ($tipperid == $id) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You're trying to tip yourself!");
				return;
			}
			// now let's check to see if this user has an account, and if so, what balance?
			try {
				$accounts = json_decode(json_encode($dogetip->listaccounts()),true);
			} catch (Exception $e) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"An internal error occured. Try again later.");
				return;
			}
			if (!array_key_exists("dogec0in_".$tipperid,$accounts)) {
				// no account.
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You do not have a tipping account. You must create one first: /msg wowsuchdoge tipcreate");
				return;
			} else if ($accounts['dogec0in_'.$tipperid] < $msg[2]) {
				// not enough balance
				try {
					$tipaddress = $dogetip->getaddressesbyaccount("dogec0in_".$tipperid);
				} catch (Exception $e) {
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"An internal error occured. Try again later.");
					return;
				}
				$tipaddress = $tipaddress[0];
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You only have ".$accounts['dogec0in_'.$tipperid]." in your tipping account. Your tipping account address is: ".$tipaddress);
				return;
			}
			// does the user being tipped have an account? if not, create one, and alert them now if possible about it, or later if not possible.
			if (!array_key_exists("dogec0in_".$id,$accounts)) {
				// no account. let's create one.
				try {
					$newaddy = $dogetip->getaccountaddress("dogec0in_".$id);
				} catch (Exception $e) {
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"An internal error occured. Try again later.");
					return;
				}
				// is this user identified?
				$temp = $this->sqliteConnect();
				$idented = (bool)$temp->querySingle("select address from ident where nick='".$temp->escapeString($msg[1])."' COLLATE NOCASE");
				$temp->close();
				if (!$idented) {
					// nope. let's add an entry to the tempdb to make sure we tell them about their new tipper account with the balance in it.
					$irc->message(SMARTIRC_TYPE_QUERY,"wowsuchdoge","query insert into newtips(id,nick,address,amount) values(".(int)$id.",'".$temp->escapeString($data->nick)."','".$temp->escapeString($newaddy)."','".$msg[2]."')");
				} else {
					// yes. let's pm the user right away about it!
					$irc->message(SMARTIRC_TYPE_QUERY,$msg[1],"Hey, ".$data->nick." sent you a ".CTRL_B.base64_decode("w5A=").$msg[2].CTRL_B." tip. (If you don't see it yet, wait for it to confirm.) You can now send others tips with this, or withdraw it (PM me and say ".CTRL_B."info".CTRL_B." ). Your tipping account address is ".CTRL_B.$newaddy.CTRL_B." - do NOT use it as your wallet!");
				}
			} else {
				// this person has an account. are they identified?
				$temp = $this->sqliteConnect();
				$idented = (bool)$temp->querySingle("select address from ident where nick='".$temp->escapeString($msg[1])."' COLLATE NOCASE");
				$temp->close();
				if (!$idented) {
					// nope. let's add an entry to the tempdb to make sure we tell them about their tip.
					$irc->message(SMARTIRC_TYPE_QUERY,"wowsuchdoge","query insert into newtips(id,nick,address,amount) values(".(int)$id.",'".$temp->escapeString($data->nick)."','','".$msg[2]."')");
				} else {
					// yes. let's pm the user right away about it!
					$irc->message(SMARTIRC_TYPE_QUERY,$msg[1],"Hey, ".$data->nick." sent you a ".CTRL_B.base64_decode("w5A=").$msg[2].CTRL_B." tip. (If you don't see it yet, wait for it to confirm.) You can now send others tips with this, or withdraw it (PM me and say ".CTRL_B."info".CTRL_B." ).");
				}
			}
			// and now actually transfer the tip!
			try {
				$dogetip->move("dogec0in_".$tipperid,"dogec0in_".$id,$msg[2]);
			} catch (Exception $e) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"An internal error occured. Try again later.");
				return;
			}
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B.base64_decode("w7jCsA==")."wow such verification".base64_decode("wrDDuA==").CTRL_B.": ".$data->nick." -> ".$msg[1]." ".CTRL_B.base64_decode("w5A=").$msg[2].CTRL_B." [ ".CTRL_B."/msg wowsuchtips info".CTRL_B." ]");
			return;
		}
	}
}

$bot = new wowsuchdoge();
$irc = new Net_SmartIRC();
$irc->setDebug(SMARTIRC_DEBUG_NONE);
//$irc->setUseSockets(true);
$irc->setChannelSyncing(true);
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^exit\s/', $bot, "msgExit");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^info$/', $bot, "msgInfo");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^tipcreate$/', $bot, "msgTipcreate");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^withdraw\s/', $bot, "msgWithdraw");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^getvip\s/', $bot, "msgGetvip");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^getad\s/', $bot, "msgGetad");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/!/', $bot, "chanMsg");
$irc->registerActionHandler(SMARTIRC_TYPE_ACTION, '/!/', $bot, "chanMsg");
$irc->registerActionHandler(SMARTIRC_TYPE_JOIN, '/\#dogec0in$/', $bot, "onJoin");
$irc->registerActionHandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $bot, "onNickChange");
while(1) {
$irc->connect("localhost",7000,true);
$irc->login("wowsuchtips","much tipping",8,"manydoge",'');
$irc->send("oper username password"); // oline
$irc->send("part #services :");
$irc->message(SMARTIRC_TYPE_QUERY,"nickserv","identify password");
$irc->join(array("#dogec0in","#dogec0in-vip","#dogec0in-gamble","#dogec0in-tip","#dogec0in-trade"));
$irc->send("samode #dogec0in +Y wowsuchtips");
$irc->send("samode #dogec0in-vip +Y wowsuchtips");
$irc->send("samode #dogec0in-tip +Y wowsuchtips");
$irc->send("samode #dogec0in-gamble +Y wowsuchtips");
$irc->send("samode #dogec0in-trade +Y wowsuchtips");
$irc->setModulepath(".");
$irc->loadModule("PingFix");
$irc->listen();
$irc->disconnect();
}
