<?php
// (c) 2013-2014 slipstream

include("SmartIRC.php");
include("jsonRPCClient.php");
include("PasswordHash.php");
include("Dogecoin.php");

$doge = new jsonRPCClient(""); // waterbowl dogecoind
$dogetip = new jsonRPCClient(""); // tipbot dogecoind
$p = new PasswordHash(8,false);
$sql = explode(":","host:user:pass:dbname");
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
	var $lastgiven = "";
	var $lastsaid = "";
	var $saiddry = false;
	var $lastadmsg = "";
	var $lastsaidv = "";
	var $balance;
	var $given;

	function __construct() {
		$this->balance = exec("../dogecoind getbalance");
		$this->given = exec("../dogecoind getreceivedbyaddress DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");
		$this->given -= $this->balance;
		$this->given = $this->given;
	}
	
	function deIdent($nick) {
		$temp = $this->sqliteConnect();
		$temp->exec("DELETE FROM ident WHERE nick='".$temp->escapeString($nick)."'");
		$temp->exec("DELETE FROM given WHERE nick='".$temp->escapeString($nick)."'");
		$temp->close();
	}
	function onPart(&$irc,&$data) {
		if ($data->channel != "#dogec0in") return;
		$this->deIdent($data->nick);
	}
	function onKick(&$irc,&$data) {
		if ($data->nick == $irc->_nick) $irc->join(array($data->channel));
		else {
			if ($data->channel != "#dogec0in") return;
			$this->deIdent($data->nick);
		}
	}
	function onQuit(&$irc,&$data) {
		if (($data->nick == "slipstream") || ($data->nick == "slipstre[a]m")) $this->slipon = SLIP_QUIT;
		else
			$this->deIdent($data->nick);
	}
	function onJoin(&$irc,&$data) {
		if (($this->slipon == SLIP_QUIT) && ($data->nick == "slipstream")) $this->slipon = SLIP_ON;
		elseif (($this->slipon == SLIP_QUIT) && ($data->nick == "slipstre[a]m")) $this->slipon = SLIP_BNC;
		elseif ($data->nick == $irc->_nick) {
			if ($irc->isJoined("#dogec0in","slipstream")) $this->slipon = SLIP_ON;
			elseif ($irc->isJoined("#dogec0in","slipstre[a]m")) $this->slipon = SLIP_BNC;
			return;
		}
		// are we identified already?
		
		if (strlen($data->ident) != 11)
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Welcome to #dogec0in! To identify to me and start drinking from the waterbowl, type ".CTRL_B."/msg ".$irc->_nick." identify [username:]password".CTRL_B."!");
	}
	function onJoinVip(&$irc,&$data) {
		$temp = $this->sqliteConnect();
		if ($data->nick == $irc->_nick) return;
		if (($data->nick == "CuteB0t") || ($data->nick == "wowsuchtips") || ($data->nick == "ponzibot") || ($data->nick == "ExperimentalBot")) return;
		if (($data->nick == "slipstream") && ($data->ident == "raylee") && ($data->host == "going.the.extra.mile.to.make.you.smile")) return;
		$ret = (bool)$temp->querySingle("SELECT count(*) from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		if (!$ret) $irc->send("REMOVE ".$data->nick." #dogec0in-vip You are not permitted to join this channel.");
		if (!$this->isVip($data->nick)) $irc->send("REMOVE ".$data->nick." #dogec0in-vip You are not permitted to join this channel.");
		$temp->close();
	}
	function onNickChange(&$irc,&$data) {
		$temp = $this->sqliteConnect();
		$newnick = $data->rawmessageex[2];
		//echo $newnick; // for debugging, it turned out it works fine :D
		$temp->exec("UPDATE ident SET nick='".$temp->escapeString($newnick)."' where nick='".$temp->escapeString($data->nick)."'");
		$temp->exec("UPDATE given SET nick='".$temp->escapeString($newnick)."' where nick='".$temp->escapeString($data->nick)."'");
		if (($data->nick == "slipstream") && ($newnick == "slipstre[a]m")) $this->slipon = SLIP_BNC;
		elseif (($data->nick == "slipstre[a]m") && ($newnick == "slipstream")) $this->slipon = SLIP_ON;
		$temp->close();
	}
	function sqlConnect() {
		global $sql;
		$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
		if ($s->connect_error) return false;
		return $s;
	}
	function sqliteConnect() {
		$s = new SQLite3("../wowsuchbots.db");
		$s->busyTimeout(1000);
		return $s;
	}
	function isVip($nick,$s = false) {
		if ($s == false) {
			$closesql = true;
			$s = $this->sqlConnect();
			if ($s === false) return false; // db connect failed.
		}
		$useid = (int)filter_var($nick,FILTER_VALIDATE_INT);
		if ($useid < 1) {
			// not an int.
			$temp = $this->sqliteConnect();
			$r = $s->query("select id from users where address='".$s->real_escape_string($temp->querySingle("SELECT address from ident where nick='".$nick."' COLLATE NOCASE"))."'");
			$temp->close();
			if (!$r->num_rows) {
				if (isset($closesql)) $s->close();
				return false; // invalid user
			}
			$useid = $r->fetch_object()->id;
		}
		$v = $s->query("select id from voice where uid=".$useid." and time > ".time());
		if (!$v->num_rows) {
			if (isset($closesql)) $s->close();
			return false; // not vip
		}
		if (isset($closesql)) $s->close();
		return true;
	}
	function msgSlip($irc,$msg) {
		if ($this->slipon == SLIP_ON) $irc->message(SMARTIRC_TYPE_QUERY,"slipstream",$msg);
		elseif ($this->slipon == SLIP_BNC) $irc->message(SMARTIRC_TYPE_QUERY,"slipstre[a]m",$msg);
		else $irc->message(SMARTIRC_TYPE_QUERY,"memoserv","send slipstream ".$msg);
	}
	function msgIdent(&$irc,&$data) {
		global $p;
		$temp = $this->sqliteConnect();
		$ret = (bool)$temp->querySingle("SELECT count(*) from ident where nick='".$temp->escapeString($data->nick)."' or host='".$temp->escapeString($data->host)."'");
		if ($ret) {
			$ret = $temp->querySingle("SELECT nick from ident where host='".$temp->escapeString($data->host)."'");
			if (!$irc->isJoined("#dogec0in",$ret)) {
				$temp->query("DELETE from ident where host='".$temp->escapeString($data->host)."'");
			} else {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You are already identified.");
				return;
			}
		}
		$canUseApiKey = false;
		$msg = explode(" ",str_replace("identify ","",$data->message));
		$s = $this->sqlConnect();
		if ($s === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. Part of dogec0in is down, try again later.");
			return;
		}
		$msg = explode(":",$msg[0]);
		if (count($msg) > 2) {
			$user = array_shift($msg);
			$msg = implode(":",$msg);
			$msg = array($user,$msg);
		}
		elseif (count($msg) == 1) {
			$canUseApiKey = true;
			$msg = array($data->nick,$msg[0]);
		}
		$r = $s->query("select id,password,webkey,address from users where verified=1 and username='".$s->real_escape_string($msg[0])."'");
		if ($r === false) {
			$this->msgSlip($irc,CTRL_B."ERROR".CTRL_B.": db error. [ at function : msgIdent() - trying to identify : ".$data->nick." ]");
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There has been an error. The administrator has been alerted. Try again later.");
			$s->close();
			$temp->close();
			return;
		}
		if ($r->num_rows == 0) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Username or password incorrect.");
			$s->close();
			$temp->close();
			return;
		}
		$res = $r->fetch_object();
		if (($canUseApiKey) && ($res->webkey == $msg[1])) {
			// identified
			$uid = (int)$res->id;
			$r = $s->query("select reason from bans where uid=".$uid);
			if ($r->num_rows) {
				$banreason = $r->fetch_object()->reason;
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You are banned. ".$banreason);
				$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","kickban #dogec0in ".$data->nick." Banned user trying to identify.");
				$s->close();
				$temp->close();
				return;
			}
			$temp->exec("INSERT INTO ident (nick,address,host) values ('".$temp->escapeString($data->nick)."','".$temp->escapeString($res->address)."','".$temp->escapeString($data->host)."')");
			// check for voice
			$v = $s->query("select id from voice where uid=".$uid." and time > ".time());
			if ($v->num_rows) {
				// this user has voice
				$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","voice #dogec0in ".$data->nick);
			}
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Much identified! Such gifts, many channel messages!");
			// does this user have a pending tipbot message?
			$pending = $temp->querySingle("select count(*) from newtips where id=".$uid);
			if ($pending > 0) {
				// yes. let's only show ONE tip to not flood the user with messages.
				$r = $temp->query("select nick,address,amount from newtips where id=".$uid);
				$res = $r->fetchArray(SQLITE3_ASSOC);
				if ($res['address'] != "") {
					// new tipbot account + tip
					$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Hey, ".$res['nick']." sent you a ".CTRL_B.base64_decode("w5A=").$res['amount'].CTRL_B." tip.".($pending > 1?" You have also been sent ".($pending-1)." more tips.":"")." You can now send others tips with this, or withdraw it (PM me and say ".CTRL_B."info".CTRL_B." ). Your tipping account address is ".CTRL_B.$res['address'].CTRL_B." - do NOT use it as your wallet!");
				} else {
					// new tip, tipbot account already exists
					$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"Hey, ".$res['nick']." sent you a ".CTRL_B.base64_decode("w5A=").$res['amount'].CTRL_B." tip.".($pending > 1?" You have also been sent ".($pending-1)." more tips.":"")." You can now send others tips with this, or withdraw it (PM me and say ".CTRL_B."info".CTRL_B." ).");
				}
				$temp->querySingle("delete from newtips where id=".$uid);
			}
			$s->close();
			$temp->close();
			return;
		}
		if ((strlen($msg[1]) > 72) || (!$p->CheckPassword($msg[1],$res->password))) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Username or password incorrect.");
			$s->close();
			$temp->close();
			return;
		}
		// if we got here we're identified.
		$uid = (int)$res->id;
		$r = $s->query("select reason from bans where uid=".$uid);
		if ($r->num_rows) {
			$banreason = $r->fetch_object()->reason;
			// is this a lightIRC user? if so ban by ident [stored in a flash cookie], as well as by services' bantype.
			if (strlen($data->ident) == 11) $irc->message(SMARTIRC_TYPE_QUERY,"chanserv","ban #dogecoin *!".$data->ident."@*");
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You are banned. ".$banreason);
			$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","kickban #dogec0in ".$data->nick." Banned user trying to identify.");
			$s->close();
			$temp->close();
			return;
		}
		// check for voice
		$v = $s->query("select id from voice where uid=".$uid." and time > ".time());
		if ($v->num_rows) {
			// this user has voice
			$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","voice #dogec0in ".$data->nick);
		}
		$temp->exec("INSERT INTO ident (nick,address,host) values ('".$temp->escapeString($data->nick)."','".$temp->escapeString($res->address)."','".$temp->escapeString($data->host)."')");
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Much identified! Such gifts, many channel messages!");
		// does this user have a pending tipbot message?
		$pending = $temp->querySingle("select count(*) from newtips where id=".$uid);
		if ($pending > 0) {
			// yes. let's only show ONE tip to not flood the user with messages.
			$r = $temp->query("select nick,address,amount from newtips where id=".$uid);
			$res = $r->fetchArray(SQLITE3_ASSOC);
			if ($res['address'] != "") {
				// new tipbot account + tip
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Hey, ".$res['nick']." sent you a ".CTRL_B.base64_decode("w5A=").$res['amount'].CTRL_B." tip.".($pending > 1?" You have also been sent ".($pending-1)." more tips.":"")." You can now send others tips with this, or withdraw it (PM wowsuchtips and say ".CTRL_B."info".CTRL_B." ). Your tipping account address is ".CTRL_B.$res['address'].CTRL_B." - do NOT use it as your wallet!");
			} else {
				// new tip, tipbot account already exists
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Hey, ".$res['nick']." sent you a ".CTRL_B.base64_decode("w5A=").$res['amount'].CTRL_B." tip.".($pending > 1?" You have also been sent ".($pending-1)." more tips.":"")." You can now send others tips with this, or withdraw it (PM wowsuchtips and say ".CTRL_B."info".CTRL_B." ).");
			}
			$temp->querySingle("delete from newtips where id=".$uid);
		}
		$s->close();
		$temp->close();
		return;
	}
	function msgQuery(&$irc,&$data) {
		if (($data->nick != "slipstream") || ($data->ident != "raylee") || ($data->host != "going.the.extra.mile.to.make.you.smile")) {
			if (($data->nick != "wowsuchtips") || ($data->ident != "manydoge") || ($data->host != "wow.....such.doge........many.shibe.........much.coins...")) return;
		}
		if (substr($data->message,0,7) == "query2 ") {
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_QUERY,"slipstream","Database connection error.");
				return;
			}
			$r = $s->query(str_replace("query2 ","",$data->message));
			if (is_bool($r)) $irc->message(SMARTIRC_TYPE_QUERY,"slipstream","Result: ".$r);
			else {
				if ($r->num_rows == 0) $irc->message(SMARTIRC_TYPE_QUERY,"slipstream","No rows returned");
				else {
					$irc->message(SMARTIRC_TYPE_QUERY,"slipstream",$r->num_rows." rows returned");
					$i = 1;
					while ($arr = $r->fetch_array(MYSQLI_NUM)) {
						if ($i > 10) break;
						// craft message
						$msg = "";
						for ($ii = 0; $ii < count($arr); $ii++) $msg .= $arr[$ii]." || ";
						$msg = substr($msg,0,-4);
						$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream",$msg);
						$i++;
					}
				}
			}
			$s->close();
			return;
		}
		$temp = $this->sqliteConnect();
		$irc->message(SMARTIRC_TYPE_QUERY,"slipstream",$temp->querySingle(str_replace("query ","",$data->message)));
		$temp->close();
		return;
	}
	function msgAd(&$irc,&$data) {
		if ($data->nick != "slipstream") return;
		if ($data->ident != "raylee") return;
		if ($data->host != "going.the.extra.mile.to.make.you.smile") return;
		$msg = explode(" ",str_replace("ad ","",$data->message));
		if ($msg[0] == "list") {
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Database connection error.");
				return;
			}
			$r = $s->query("select id,text,time from ads where time > ".time());
			if ($r->num_rows == 0) {
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","There are no ads in the db.");
				$s->close();
				return;
			}
			$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","There are ".$r->num_rows." ads in the db.");
			$i = 1;
			while ($obj = $r->fetch_object()) {
				if ($i > 10) break;
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream",$obj->id.": (Expires ".date("r",$obj->time).") ".base64_decode($obj->text));
				$i++;
			}
			$s->close();
			return;
		} else if ($msg[0] == "del") {
			if (count($msg) < 2) {
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Usage: ad del ".CTRL_B."id".CTRL_B);
				return;
			}
			$msg[1] = filter_var($msg[1],FILTER_VALIDATE_INT);
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("delete from ads where id='".((int)$msg[1])."' or time < ".time()); // clean up the db too!
			$s->close();
			$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Removed ad with id ".CTRL_B.$msg[1].CTRL_B." and removed expired ads from the database.");
			return;
		} else if ($msg[0] == "add") {
			if (count($msg) < 3) {
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Usage: ad add ".CTRL_B."days".CTRL_B." ".CTRL_B."message".CTRL_B);
				return;
			}
			$msg[1] = (int)filter_var($msg[1],FILTER_VALIDATE_INT);
			if ($msg[1] < 1) {
				// not an int.
				$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Usage: ad add ".CTRL_B."days".CTRL_B." ".CTRL_B."message".CTRL_B);
				return;
			}
			$msg2 = $msg;
			array_shift($msg2); // "add"
			array_shift($msg2); // days
			$msg2 = implode(" ",$msg2);
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("insert into ads (text,time) values ('".base64_encode($msg2)."',".(time() + (86400*$msg[1])).")");
			$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Added new ad with id ".CTRL_B.$s->insert_id.CTRL_B);
			$s->close();
			return;
		} else if ($msg[0] == "show") {
			if ((count($msg) > 1) && (($msg[1] = filter_var($msg[1],FILTER_VALIDATE_INT)) >= 1)) {
				$s = $this->sqlConnect();
				if ($s === false) {
					$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
					return;
				}
				$r = $s->query("select text from ads where id=".$msg[1]);
				if ($r->num_rows)
					$irc->message(SMARTIRC_TYPE_NOTICE,"#dogec0in",base64_decode($r->fetch_object()->text));
				$s->close();
				return;
			}
			$this->adTick($irc);
			return;
		} else {
			$irc->message(SMARTIRC_TYPE_NOTICE,"slipstream","Usage: ad {list/del id/add days message}");
			return;
		}
	}
	function msgVoice(&$irc,&$data) {
		if (!$irc->isOpped("#dogec0in",$data->nick)) return;
		$msg = explode(" ",str_replace("voice ","",$data->message));
		if ($msg[0] == "list") {
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("select id,uid,time from voice where time > ".time()." order by id desc");
			if ($r->num_rows == 0) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There are no voiced users in the db.");
				$s->close();
				return;
			}
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"There are ".$r->num_rows." voiced users in the db.");
			$i = 1;
			while ($obj = $r->fetch_object()) {
				if ($i > 10) break;
				// get the username
				$r2 = $s->query("select username from users where id=".((int)$obj->uid));
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$obj->id.": (Expires ".date("r",$obj->time).") ".$r2->fetch_object()->username);
				$i++;
			}
			$s->close();
			return;
		} else if ($msg[0] == "search") {
			if (count($msg) < 2) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: voice search ".CTRL_B."nick".CTRL_B);
				return;
			}
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("select id,time from voice where time > ".time()." and uid=(select id from users where username='".$s->real_escape_string($msg[1])."')");
			if ($r->num_rows == 0) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"That user is not voiced.");
				$s->close();
				return;
			}
			$obj = $r->fetch_object();
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$obj->id.": (Expires ".date("r",$obj->time).") ".$msg[1]);
			$s->close();
			return;
		} else if ($msg[0] == "del") {
			if (count($msg) < 2) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: voice del ".CTRL_B."id".CTRL_B);
				return;
			}
			$msg[1] = filter_var($msg[1],FILTER_VALIDATE_INT);
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("delete from voice where id='".((int)$msg[1])."' or time < ".time()); // clean up the db too!
			$s->close();
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Removed voiced users with id ".CTRL_B.$msg[1].CTRL_B." and removed expired voiced users from the database.");
			return;
		} else if ($msg[0] == "add") {
			if (count($msg) < 3) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: voice add ".CTRL_B."days".CTRL_B." ".CTRL_B."username".CTRL_B);
				return;
			}
			$msg[1] = (int)filter_var($msg[1],FILTER_VALIDATE_INT);
			if ($msg[1] < 1) {
				// not an int.
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: voice add ".CTRL_B."days".CTRL_B." ".CTRL_B."username".CTRL_B);
				return;
			}
			$msg2 = $msg;
			array_shift($msg2); // "add"
			array_shift($msg2); // days
			$msg2 = implode(" ",$msg2);
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			// get the userid
			$r = $s->query("select id from users where username='".$s->real_escape_string($msg2)."'");
			if (!$r->num_rows) {
				$s->close();
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"That username does not exist in the db.");
				return;
			}
			// is this user already voiced?
			$id = (int)$r->fetch_object()->id;
			$r = $s->query("select time from voice where uid=".$id);
			if (!$r->num_rows) {
				$r = $s->query("insert into voice (uid,time) values (".($id).",".(time() + (86400*$msg[1])).")");
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Added new voiced user with id ".CTRL_B.$s->insert_id.CTRL_B);
			} else {
				$r = $s->query("update voice set time=".($r->fetch_object()->time + (86400*$msg[1]))." where uid=".$id);
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Extended time of old voiced user with id ".CTRL_B.$s->insert_id.CTRL_B);
			}
			$s->close();
			return;
		} else {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: voice {list/del id/search username/add days username}");
			return;
		}
	}
	function msgBan(&$irc,&$data) {
		if (!$irc->isOpped("#dogec0in",$data->nick)) return;
		$msg = explode(" ",str_replace("ban ","",$data->message));
		if (count($msg) < 2) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: ban ".CTRL_B."nick".CTRL_B." ".CTRL_B."reason".CTRL_B);
			return;
		}
		$msg2 = $msg;
		array_shift($msg2); // nick
		$msg2 = implode(" ",$msg2);
		$temp = $this->sqliteConnect();
		$addy = $temp->querySingle("SELECT address FROM ident WHERE nick='".$temp->escapeString($msg[0])."' COLLATE NOCASE");
		$temp->close();
		if (($addy === false) || ($addy == null)) {
			// undoc'd functionality: assume this is an id and go from there.
			$useid = (int)filter_var($msg[0],FILTER_VALIDATE_INT);
			if ($useid < 1) {
				// not an int.
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$msg[0]." is not identified.");
				return;
			}
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$id = $useid;
		} else {
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Database connection error.");
				return;
			}
			$r = $s->query("select id from users where address='".$s->real_escape_string($addy)."'");
			if ($r->num_rows == 0) {
				// wtf?
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Can't get id from database...");
				if ($data->nick != "slipstream") $this->msgSlip($irc,CTRL_B."ERROR".CTRL_B.": couldn't get id from database, nick=".$msg[0]." address=".$addy);
				$s->close();
				return;
			}
			$id = $r->fetch_object()->id;
			$id2 = (int)filter_var($id,FILTER_VALIDATE_INT);
			if ($id2 < 1) {
				// wtf?
				$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Can't get id from database...");
				if ($data->nick != "slipstream") $this->msgSlip($irc,CTRL_B."ERROR".CTRL_B.": couldn't get id from database, nick=".$msg[0]." address=".$addy." (got non-int id=".$id." )");
				$s->close();
				return;
			}
			$id2 = $id;
		}
		// we now have id in $id, let's add the ban (we already made sure it's an int, we don't need to sanitise again)
		$s->query("INSERT INTO bans (uid,reason) values (".$id.",'".$s->real_escape_string($msg2)."')");
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Added ban with id ".CTRL_B.$s->insert_id.CTRL_B);
		$s->close();
		// now act on the ban if we can
		if (!isset($useid)) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$msg[0],"You just got banned. ".$banreason);
			$irc->message(SMARTIRC_TYPE_QUERY,"chanserv","kickban #dogec0in ".$msg[0]." This user just got banned.");
		}
		return;
	}
	function msgExit(&$irc,&$data) {
		// Dumps the DB and exits. Now i should find it easier to restart this damn bot :-)
		if ($data->nick != "slipstream") return;
		if ($data->ident != "raylee") return;
		if ($data->host != "going.the.extra.mile.to.make.you.smile") return;
		$msg = str_replace("exit ","",$data->message);
		// No need to dump the db anymoar now it's no longer stored completely in RAM
		$irc->_send('QUIT :wowsuchdoge exiting: '.$msg, SMARTIRC_CRITICAL);
		usleep(100000);
		$irc->disconnect(true);
		exit();
	}
	function msgGlobal(&$irc,&$data) {
		// Global notice :)
		if ($data->nick != "slipstream") return;
		if ($data->ident != "raylee") return;
		if ($data->host != "going.the.extra.mile.to.make.you.smile") return;
		$msg = str_replace("global ","",$data->message);
		$irc->message(SMARTIRC_TYPE_NOTICE,"#dogec0in",">Global Notice< ".$msg);
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Sent global notice.");
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
	function timedChanMsg(&$irc,&$data) {
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$starttime = $mtime;
		$this->chanMsg($irc,$data);
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$endtime = $mtime; 
		$totaltime = ($endtime - $starttime); 
		echo "chanMsg() ran in ".$totaltime." seconds.\n"; 
	}
	function chanMsg(&$irc,&$data) {
		global $doge;
		global $dogetip;
		if ($data->nick == $irc->_nick) return;
		if (!preg_match('/\#dogec0in(-vip)?$/',$data->channel)) return;
		if ($data->nick == "CuteB0t") return;
		if ($data->nick == "hell-of-a-shibe") return;
		if ($data->nick == "wowsuchtips") return;
		if ($data->nick == "ExperimentalBot") return;
		$data->message = $this->stripControlCharacters($data->message);
		if (file_exists("../dogeinfo")) {
			$dogeinfo = explode("|",file_get_contents("../dogeinfo"));
			if ($dogeinfo[0] == "ok") {
				$this->balance = $dogeinfo[1];
				$this->given = $dogeinfo[2];
				$this->given = ($this->given - $this->balance);
			}
			unlink("../dogeinfo");
		}
		if (($data->message == "!waterbowl") || (preg_match("/^\!waterbowl\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."Waterbowl has ".$this->balance." DOGE. Wow, such donate: ".CTRL_K."5DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");
			return;
		} else if (($data->message == "!new") || (preg_match("/^\!new\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"You get dogecoin here by chatting; there's a 1/10 chance of getting 2-20 DOGE from every message you send. However, double messages do not count. Remember the rules: you can find them by typing ".CTRL_B."!rules".CTRL_B.".");
			return;
		} else if (($data->message == "!rules") || (preg_match("/^\!rules\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"dogec0in is an all-ages chat! Please get any topics and conversations PG-13, and use common sense. There will be no tolerance for NSFW content, sexism, racism, homophobia, transphobia, or related bigotry. Channel operators (%/@/&/~/! before their name) have final say on what is and is not acceptable.");
			return;
		} else if (($data->message == "!help") || (preg_match("/^\!help\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"My triggers: !waterbowl !new !rules !given !woof !referrals");
			return;
		} else if (($data->message == "!woof") || (preg_match("/^\!woof\s/",$data->message))) {
			switch (mt_rand(1,7)) {
				case 1:
					$irc->message(SMARTIRC_TYPE_ACTION,$data->channel,"barks.");
					break;
				case 2:
					$irc->message(SMARTIRC_TYPE_ACTION,$data->channel,"whines.");
					break;
				case 3:
					$irc->message(SMARTIRC_TYPE_ACTION,$data->channel,"growls.");
					break;
				case 4:
					$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"Woof woof!");
					break;
				case 5:
					$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"TO THE MOON!");
					break;
				case 6:
					$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,"Grrrrr...woof!");
					break;
				case 7:
					$irc->message(SMARTIRC_TYPE_ACTION,$data->channel,"howls at the moon.");
					break;
			}
			return;
		} else if (($data->message == "!given") || (preg_match("/^\!given\s/",$data->message))) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."Waterbowl has given out ".$this->given." DOGE. Wow, such donate: ".CTRL_K."5DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");
			return;
		} else if (($data->message == "!referrals") || (preg_match("/^\!referrals\s/",$data->message))) {
			// are we identified?
			$temp = $this->sqliteConnect();
			$address = $temp->querySingle("SELECT address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
			$temp->close();
			if ($address == "") {
				$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."REFERRALS".CTRL_B.": You are not identified, please identify and try again!");
				return;
			}
			$s = $this->sqlConnect();
			if ($s === false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."REFERRALS".CTRL_B.": An internal error occured, try again later.");
				return;
			}
			// get the number of referrals
			$r = $s->query("select count(*) as count from referrals where rid=(select id from users where address='".$s->real_escape_string($address)."')");
			if (($r === false) || (!$r->num_rows)) {
				// nope.
				$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."REFERRALS".CTRL_B.": You have no referrals.");
				$s->close();
				return;
			}
			$refs = $r->fetch_object()->count;
			// get this referral's balance.
			$r = $s->query("select balance from refbal where uid=(select id from users where address='".$s->real_escape_string($address)."')");
			if (!$r->num_rows) {
				// insert the row.
				$s->query("insert into refbal (uid,balance) values ((select id from users where address='".$s->real_escape_string($address)."'),0)");
				$refbal = (float)0;
			} else {
				$refbal = $r->fetch_object()->balance;
			}
			$s->close();
			$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B."REFERRALS".CTRL_B.": You have ".$refs." referral".($refs!=1?"s":"")." and your outstanding referral balance is ".$refbal." DOGE.");
			return;
		} else if (substr($data->message,0,1) == "!") return;
		else if (substr($data->message,0,1) == "~") return;
		else {
			foreach (explode(" ",$data->message) as $part)
				if (Dogecoin::checkAddress($part)) {
					$irc->message(SMARTIRC_TYPE_CHANNEL,$data->channel,CTRL_B.$data->nick.CTRL_B.": Posting Dogecoin addresses is against the rules.");
					return;
				}
		}
		if ($this->lastgiven == $data->nick) return;
		if ($data->channel == "#dogec0in-vip") {
			if ($this->lastsaidv == $data->nick) return;
			$this->lastsaidv = $data->nick;
		} else {
			if ($this->lastsaid == $data->nick) return;
			$this->lastsaid = $data->nick;
		}
		$balance = $this->balance;
		if ($balance < 20) {
			if (!$this->saiddry) {
				$irc->message(SMARTIRC_TYPE_NOTICE,"#dogec0in",CTRL_B.CTRL_K."4,1Waterbowl much dry, only has ".$balance." DOGE. Wow, such donate: ".CTRL_K."5DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");
				$irc->message(SMARTIRC_TYPE_NOTICE,"#dogec0in-vip",CTRL_B.CTRL_K."4,1Waterbowl much dry, only has ".$balance." DOGE. Wow, such donate: ".CTRL_K."5DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");
				$this->saiddry = true;
			}
			return;
		}
		$this->saiddry = false;
		$temp = $this->sqliteConnect();
		$waittime = (int)$temp->querySingle("SELECT wait FROM given WHERE nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE"); // if no result, returns null, which when casted to int is 0
		if ($waittime > 0) $waittime = time() - (60 * $waittime);
		$temp->exec("DELETE FROM given WHERE nick='".$temp->escapeString($data->nick)."' and time<".$waittime);
		$shouldgive = !((bool)$temp->querySingle("SELECT count(*) FROM given WHERE nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE"));
		$isVip = $this->isVip($data->nick);
		// voice if vip and not voiced.
//		if (($isVip) && (!$irc->isVoiced("#dogec0in",$data->nick))) $irc->message(SMARTIRC_TYPE_QUERY,"chanserv","voice #dogec0in ".$data->nick); // bugged out.
		if (!(($shouldgive) && (mt_rand(1,($isVip?9:10)) == 1))) {
			$temp->close();
			return;
		}
		// if we're here that means we can give some coin!
		// is this person registered?
		$address = $temp->querySingle("SELECT address from ident where nick='".$temp->escapeString($data->nick)."' COLLATE NOCASE");
		if ($address == "") {
			$temp->close();
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You would have got some dogecoin, but you aren't identified! If you don't have an account, register at http://dogec0in.com/ - if you do, you need to refresh or identify again.");
			return;
		}
		if (!Dogecoin::checkAddress($address)) {
			// ???????
			$temp->close();
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"You should have got some dogecoin, but the address in the db is invalid for some reason. The administrator has been alerted.");
			$this->msgSlip($irc,CTRL_B."ERROR".CTRL_B.": got invalid dogecoin address, nick=".$data->nick." address=".$address);
			return;
		}
		// gogogo!
		$waittime = mt_rand(30,($isVip?60:120));
		$temp->exec("INSERT INTO given (nick,time,wait) values ('".$data->nick."',".time().",".$waittime.")");
		$temp->close();
		$this->lastgiven = $data->nick;
		$amount = (float) mt_rand(200000000,2000000000);
		$amount = (float)($amount / 100000000);
		try {
			//$doge->sendtoaddress($address,$amount);
			proc_close(proc_open("../dogecoind sendtoaddress ".$address." ".$amount." &",array(),$foo));
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_QUERY,$data->nick,"You should have got some dogecoin, but an internal error occured...");
			return;
		}
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Wow, much get: ".$amount." DOGE");
		$this->balance -= $amount;
		// referral stuff.
		// does this guy have a referral?
		$s = $this->sqlConnect();
		if ($s === false) return;
		$r = $s->query("select rid from referrals where uid=(select id from users where address='".$s->real_escape_string($address)."')");
		if (($r === false) || (!$r->num_rows)) {
			// nope.
			$s->close();
			return;
		}
		$rid = $r->fetch_object()->rid;
		if (!$rid) {
			// nope.
			$s->close();
			return;
		}
		// get this referral's balance.
		$r = $s->query("select balance from refbal where uid=".$rid);
		if (!$r->num_rows) {
			// insert the row.
			$s->query("insert into refbal (uid,balance) values (".$rid.",0)");
			$refbal = (float)0;
		} else {
			$refbal = $r->fetch_object()->balance;
		}
		// add 10% of the amount to the balance
		$addbal = ($amount / 10);
		$refbal += $addbal;
		// is it over 10 doge ?
		if ($refbal > 10) {
			// yes, send it out and set it back to 0.
			$r = $s->query("select address from users where id=".$rid);
			if (($r === false) || (!$r->num_rows)) {
				// wait, what the? just update the db balance.
				$s->query("update refbal set balance=".$refbal." where uid=".$rid);
				$s->close();
				return;
			}
			$refaddy = $r->fetch_object()->address;
			try {
				proc_close(proc_open("../dogecoind sendtoaddress ".$refaddy." ".$refbal." &",array(),$foo));
			} catch (Exception $e) {
				// update the db balance
				$s->query("update refbal set balance=".$refbal." where uid=".$rid);
				$s->close();
				return;
			}
			$this->balance -= $refbal;
			$refbal = (float)0;
		}
		// update the db balance.
		$s->query("update refbal set balance=".$refbal." where uid=".$rid);
		$s->close();
		return;
	}
	function adTick(&$irc) {
		$s = $this->sqlConnect();
		if ($s === false) return;
		// clean out db
		$r = $s->query("delete from ads where time < ".time());
		$r = $s->query("delete from voice where time < ".time());
		$r = $s->query("select text from ads where time > ".time()." order by rand() limit 0,1");
		if ($r->num_rows == 0)
			$admsg = "This space can be bought with dogecoins! PM slipstream for more information!";
		else {
			$res = $r->fetch_object();
			$admsg = base64_decode($res->text);
		}
		$s->close();
		if ($this->lastadmsg == $admsg) {
			$this->lastadmsg = "";
			return;
		}
		if (substr($admsg,0,16) == ">Global Notice< ") $admsg = substr($admsg,16); // to avoid any exploits by that way ;>
		$this->lastadmsg = $admsg;
		$irc->message(SMARTIRC_TYPE_NOTICE,"#dogec0in",$admsg);
		return;
	}
}

date_default_timezone_set("Europe/Berlin");
$bot = new wowsuchdoge();
$irc = new Net_SmartIRC();
$irc->setDebug(0);
//$irc->setUseSockets(true);
$irc->setChannelSyncing(true);
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^identify\s/', $bot, "msgIdent");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^query(2)?\s.*/', $bot, "msgQuery");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^ad\s/', $bot, "msgAd");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^ban\s/', $bot, "msgBan");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^voice\s/', $bot, "msgVoice");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^exit\s/', $bot, "msgExit");
$irc->registerActionHandler(SMARTIRC_TYPE_QUERY, '/^global\s/', $bot, "msgGlobal");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '.*', $bot, "timedChanMsg");
$irc->registerActionHandler(SMARTIRC_TYPE_ACTION, '.*', $bot, "timedChanMsg");
$irc->registerActionHandler(SMARTIRC_TYPE_JOIN, '/\#dogec0in$/', $bot, "onJoin");
$irc->registerActionHandler(SMARTIRC_TYPE_JOIN, '/\#dogec0in-vip$/', $bot, "onJoinVip");
$irc->registerActionHandler(SMARTIRC_TYPE_PART, '.*', $bot, "onPart");
$irc->registerActionHandler(SMARTIRC_TYPE_KICK, '.*', $bot, "onKick");
$irc->registerActionHandler(SMARTIRC_TYPE_QUIT, '.*', $bot, "onQuit");
$irc->registerActionHandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $bot, "onNickChange");
$irc->registerTimeHandler(600000, $bot, "adTick");
while(1) {
$irc->connect("localhost",7000,true);
$irc->login("wowsuchdoge","much coins",8,"manyshibe",'');
$irc->send("oper username password"); // oline
$irc->send("part #services :");
$irc->message(SMARTIRC_TYPE_QUERY,"nickserv","identify password");
$irc->join(array("#dogec0in","#dogec0in-vip"));
$irc->send("samode #dogec0in +Y wowsuchdoge");
$irc->send("samode #dogec0in-vip +Y wowsuchdoge");
$irc->setModulepath(".");
$irc->loadModule("PingFix");
$irc->listen();
$irc->disconnect();
}
