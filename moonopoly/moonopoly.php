<?php
// (c) 2013-2014 slipstream

include("SmartIRC.php");
include("jsonRPCClient.php");
include("Dogecoin.php");

$doge = new jsonRPCClient(""); // waterbowl dogecoind

define("CTRL_B","\002");
define("CTRL_K","\003");
define("CTRL_U","\037");
define("CTRL_I","\035");
define("CTRL_O","\017");
define("CTRL_R","\026");

define("PROPERTY",0);
define("STATION",1);
define("UTILITY",2);
define("CHANCE",3);
define("CHEST",4);

define("ACTION_MOVE",0);
define("ACTION_MOVE_UTILITY",1);
define("ACTION_MOVE_STATION",2);
define("ACTION_MOVEBACK",3);
define("ACTION_CASH",4);
define("ACTION_CASH_PLAYER",5);
define("ACTION_CASH_PROPERTY",6);
define("ACTION_CASH_PROPERTYDEV",7);
define("ACTION_OUTOFJAIL",8);

$STATION_PRICING = array(25,50,100,200);
define("STATION_MORTGAGE",100);
$UTILITY_PRICING = array(4,10);
define("UTILITY_MORTGAGE",75);

define("DOGE",base64_decode("w5A="));

define("GAMECHANNEL","#dogec0in-moonopoly");

class Game {
	// instantiate this class to start a new game.
	public $freeParking = 0;
	public $players = array();
	public $spaces = array();
	public $chance = array();
	public $chest = array();
	public $currchance = 0;
	public $currchest = 0;
	public $turn = "";
	public $auction = false;
	public $trading = false;
	public $winner = "";
	public $passedgo = false;
	function __construct() {
		$utilsone = array("Dogehouse","Dogecoinpool","Netcodepool","Rapidhash","Teamdoge");
		$utilstwo = array("CGMiner","CUDAMiner","Minerd");
		$this->spaces = array(
			new Space("Launchpad"),
			new Property("4chan",60,array(2,10,30,90,160,250),PROPERTY,0,50),
			new CardSpace("Comoonity Chest",CHEST),
			new Property("9gag",60,array(4,20,60,180,360,450),PROPERTY,0,50),
			new TaxSpace("Pool Fees",200),
			new Property("Apollo",200,$GLOBALS['STATION_PRICING'],STATION,-1,STATION_MORTGAGE),
			new Property("/r/dogecoincharity",100,array(6,30,90,270,400,550),PROPERTY,1,50),
			new CardSpace("Such Chance",CHANCE),
			new Property("/r/dogecoinpif",100,array(6,30,90,270,400,550),PROPERTY,1,50),
			new Property("SaveDogemas",120,array(8,40,100,300,450,600),PROPERTY,1,60),
			new Space("Orphaned Block"),
			new Property("RoL #dogec0in-tip",140,array(10,50,150,450,625,750),PROPERTY,2,70),
			new Property($utilsone[array_rand($utilsone)],150,$GLOBALS['UTILITY_PRICING'],UTILITY,-2,UTILITY_MORTGAGE),
			new Property("freenode #dogebeg",140,array(10,50,150,450,625,750),PROPERTY,2,70),
			new Property("/r/dogecoinbeg",160,array(12,60,180,500,700,900),PROPERTY,2,80),
			new Property("Luna",200,$GLOBALS['STATION_PRICING'],STATION,-1,STATION_MORTGAGE),
			new Property("/r/dogebetting",180,array(14,70,200,550,750,950),PROPERTY,3,90),
			new CardSpace("Comoonity Chest",CHEST),
			new Property("Doge Wheel",180,array(14,70,200,550,750,950),PROPERTY,3,90),
			new Property("Doge Dice",200,array(16,80,220,600,800,1000),PROPERTY,3,100),
			new Space("Doge Walking"),
			new Property("Doges.org",220,array(18,90,250,700,875,1050),PROPERTY,4,110),
			new CardSpace("Much Chance",CHANCE),
			new Property("Bitcointalk",220,array(18,90,250,700,875,1050),PROPERTY,4,110),
			new Property("Doge Road",240,array(20,100,300,750,925,1100),PROPERTY,4,120),
			new Property("Ranger",200,$GLOBALS['STATION_PRICING'],STATION,-1,STATION_MORTGAGE),
			new Property("Amazon EC2",260,array(22,110,330,800,975,1150),PROPERTY,5,130),
			new Property("Windows Azure",260,array(22,110,330,800,975,1150),PROPERTY,5,130),
			new Property($utilstwo[array_rand($utilstwo)],150,$GLOBALS['UTILITY_PRICING'],UTILITY,-2,UTILITY_MORTGAGE),
			new Property("Dogecoin Foundation",280,array(24,120,360,850,1025,1200),PROPERTY,5,140),
			new Space("Found Orphaned Block"),
			new Property("Dogechain",300,array(26,130,390,900,1100,1275),PROPERTY,6,150),
			new Property("/u/dogetipbot",300,array(26,130,390,900,1100,1275),PROPERTY,6,150),
			new CardSpace("Comoonity Chest",CHEST),
			new Property("Dogec0in",320,array(28,150,450,1000,1200,1400),PROPERTY,6,160),
			new Property("Surveyor",200,$GLOBALS['STATION_PRICING'],STATION,-1,STATION_MORTGAGE),
			new CardSpace("Very Chance",CHANCE),
			new Property("/r/dogecoin",350,array(35,175,500,1100,1300,1500),PROPERTY,7,175),
			new TaxSpace("Trading Fees",100),
			new Property("The Moon",400,array(50,200,600,1400,1700,2000),PROPERTY,7,200)
		);
		$this->chance = array(
			new Card("Sudden hashrate increase: advance to Launchpad (collect ".DOGE."400)",CHANCE,ACTION_MOVE,0),
			new Card("Advance to Doge Road. If you pass Launchpad, collect ".DOGE."200.",CHANCE,ACTION_MOVE,24),
			new Card("Advance to the nearest Utility. If unowned, you may buy it. If owned, throw dice and pay owner a total ten times the amount thrown.",CHANCE,ACTION_MOVE_UTILITY,0),
			new Card("Advance to the nearest Space Program and pay owner twice the rental to which they are otherwise entitled. If it is unowned, you may buy it.",CHANCE,ACTION_MOVE_STATION,0),
			new Card("Advance to RoL #dogec0in-tip. If you pass Launchpad, collect ".DOGE."200.",CHANCE,ACTION_MOVE,11),
			new Card("Bank pays you dividend of ".DOGE."50.",CHANCE,ACTION_CASH,50),
			new Card("Recover from Orphaned Block free (this card may be kept until needed or sold)",CHANCE,ACTION_OUTOFJAIL,0),
			new Card("Go back 3 spaces",CHANCE,ACTION_MOVEBACK,-3),
			new Card("Found three Orphaned Blocks in a row. Go to Orphaned Block, do not pass Launchpad, do not collect ".DOGE."200.",CHANCE,ACTION_MOVE,10),
			new Card("Update your property. For each house pay ".DOGE."25, for each hotel pay ".DOGE."100.",CHANCE,ACTION_CASH_PROPERTYDEV,array(25,100)),
			new Card("Caught botnet mining, pay fines of ".DOGE."150.",CHANCE,ACTION_CASH,-150),
			new Card("Join the astronauts of the Apollo, if you pass Launchpad collect ".DOGE."200.",CHANCE,ACTION_MOVE,5),
			new Card("Take a trip to the Moon!",CHANCE,ACTION_MOVE,39),
			new Card("You have been elected moderator of /r/dogecoin, pay each player ".DOGE."50.",CHANCE,ACTION_CASH_PLAYER,-50),
			new Card("Dogecoin reaches 0.01 USD, collect ".DOGE."150.",CHANCE,ACTION_CASH,150),
			new Card("You have won a tipbot development bounty, collect ".DOGE."100.",CHANCE,ACTION_CASH,100)
			
		);
		$this->chest = array(
			new Card("Sudden hashrate increase: advance to Launchpad (collect ".DOGE."400)",CHEST,ACTION_MOVE,0),
			new Card("Doge Dice error in your favour, collect ".DOGE."75.",CHEST,ACTION_CASH,75),
			new Card("Tipbot withdrawal fees, pay ".DOGE."50.",CHEST,ACTION_CASH,-50),
			new Card("Recover from Orphaned Block free (this card may be kept until needed or sold)",CHEST,ACTION_OUTOFJAIL,0),
			new Card("Your block didn't confirm. Go to Orphaned Block, do not pass Launchpad, do not collect ".DOGE."200.",CHEST,ACTION_MOVE,10),
			new Card("It is your Reddit Cake Day. Collect ".DOGE."10 from each player.",CHEST,ACTION_CASH_PLAYER,10),
			new Card("Successful post in /r/dogecoinbeg - get tipped ".DOGE."50 by every player.",CHEST,ACTION_CASH_PLAYER,50),
			new Card("Pool fees refund, collect ".DOGE."20.",CHEST,ACTION_CASH,20),
			new Card("Dogecoin insurance matures, collect ".DOGE."100",CHEST,ACTION_CASH,100),
			new Card("Pay withdrawal fees of ".DOGE."100.",CHEST,ACTION_CASH,-100),
			new Card("Tipping spree in #dogec0in - pay ".DOGE."50.",CHEST,ACTION_CASH,-50),
			new Card("Receive ".DOGE."25 tip for a funny comment.",CHEST,ACTION_CASH,25),
			new Card("Your property got hacked, pay to secure it. For each house pay ".DOGE."40, for each hotel pay ".DOGE."115",CHEST,ACTION_CASH_PROPERTYDEV,array(40,115)),
			new Card("You have won second prize in a development competition, collect ".DOGE."10.",CHEST,ACTION_CASH,10),
			new Card("You get tipped ".DOGE."100.",CHEST,ACTION_CASH,100),
			new Card("From sale of Coinye you get ".DOGE."50.",CHEST,ACTION_CASH,50),
			new Card("Spend a night mining, receive ".DOGE."100.",CHEST,ACTION_CASH,100),
			new Card("You're feeling a little immature, go back to 4chan",CHEST,ACTION_MOVEBACK,1)
		);
		shuffle($this->chance);
		shuffle($this->chest);
	}
	function getProperty($name) {
		// getProperty(): from the property name specified, get the property object or FALSE if this isn't a valid property
		foreach ($this->spaces as $s) {
			// is this a property?
			if (get_class($s) != "Property") continue;
			if ($name == $s->name) return $s;
		}
		return false;
	}
	function getJsonData() {
		$var = get_object_vars($this);
        foreach($var as &$value)
			if ((is_object($value)) && (method_exists($value,'getJsonData'))) $value = $value->getJsonData();
		return $var;
    }
}

class Space {
	// for any generic space. (the four corner spaces)
	private $name;
	function getName() { return $this->name; }
	function __construct($n) { $this->name = $n; }
	function getNameIRC() { return CTRL_B.$this->name.CTRL_B; }
	function doSomething($game,$player) {
		// doSomething(): does whatever needs to be done when the player lands on the space.
		// default function: handles the four corners.
		switch ($this->name) {
			case "Launchpad":
				// Landed on go, give the extra cash
				$game->passedgo = true;
				$player->money += 200;
				$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->getUsername().CTRL_B." landed on Launchpad and collected another ".DOGE."200 - how lucky!");
				break;
			case "Orphaned Block":
				if ($player->jailturn === false) $GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B." is Just Visiting.");
				else $GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->getUsername().CTRL_B." found an Orphaned Block...");
				break;
			case "Doge Walking":
				if ($game->freeParking == 0) $GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"But there were no dogecoins waiting there...");
				else {
					$player->addMoney($game->freeParking);
					$game->freeParking = 0;
				}
				break;
			case "Found Orphaned Block":
				$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->getUsername().CTRL_B." found an Orphaned Block and must pay the penalty...");
				$player->space = 10;
				$player->jailturn = 0;
				$player->gotdouble = false;
				break;
		}
	}
	function getJsonData() {
		$var = get_object_vars($this);
        foreach($var as &$value)
			if ((is_object($value)) && (method_exists($value,'getJsonData'))) $value = $value->getJsonData();
		return $var;
    }
}

class Player {
	// various player stuff
	public $money = 0;
	public $space = 0;
	public $properties = array();
	public $cardoutofjail = false;
	public $jailturn = false;
	public $status = "";
	public $hastopay = array(0,"");
	public $lastdice = 0; // for utility payments.
	public $lastpersonpaid = ""; // so the rest of the debt can be paid off when paying multiple people. also so player 2 can raise money for player1 who got "it is your birthday" card etc.
	public $gotdouble = true; // :)
	public $numdoubles = 0;
	public $username = "";
	function getUsername() { return $this->username; }
	function __construct($un) {
		$this->username = $un;
		$this->money = 1500;
	}
	function takeTurn() {
		// takeTurn(): does the dice rolling.
		// TODO: jail.
		$dice1 = mt_rand(1,6);
		$dice2 = mt_rand(1,6);
		if ($dice1 == $dice2) {
			$this->gotdouble = true;
			$this->numdoubles++;
		}
		else {
			$this->gotdouble = false;
			$this->numdoubles = 0;
		}
		$dice = $dice1+$dice2;
		$this->lastdice = $dice;
		// was this our third double? if so, go to jail
		if ($this->numdoubles == 3) {
			$this->numdoubles = 0;
			$this->jailturn = 0;
			$this->space = 10;
			$this->gotdouble = false;
			$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." rolled three doubles in a row and therefore found an Orphaned Block!");
			return;
		}
		$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." rolled a ".$dice." (".$dice1." and ".$dice2.($this->gotdouble?" - double!":"").")!");
		// if we're in jail and haven't got a double, stay there.
		if (($this->jailturn !== false) && (!$this->gotdouble)) {
			$this->jailturn++;
			// if that was turn three, they have to pay anyway!
			$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B.": That was turn ".$this->jailturn." of the orphaned block penalty!");
			if ($this->jailturn == 3) {
				$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B.": Being turn three, you have to pay the ".DOGE."50 fine anyway!");
				$this->payToBank(50);
				$this->jailturn = false;
				if ($this->hastopay[0] != 0) return; // we have to raise some cash
			}
			else return;
		}
		if (($this->jailturn !== false) && ($this->gotdouble)) {
			$this->jailturn = false;
			$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B.": You finished the orphaned block penalty early by rolling a double!");
		}
		$this->space += $dice;
		if ($this->space > 39) {
			$this->space %= 40;
			$this->money += 200;
			$GLOBALS['bot']->game->passedgo = true;
			$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." passed Launchpad and collected ".DOGE."200!");
		}
	}
	function addMoney($amount) {
		// addMoney(): adds money and announces it.
		// used for (at least) free parking and rent payments.
		$this->money += $amount;
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." collected ".DOGE.$amount." and now has ".DOGE.$this->money.".");
	}
	
	function payMoney($payment,$owner) {
		if ($this->money < $payment) {
			// nope. give the creditor what they can and set them in has-to-raise mode.
			$owner->addMoney($this->money);
			$payment -= $this->money;
			// calculate our total assets
			$assets = $this->getAssets(true);
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$this->money."! You have to raise ".DOGE.$payment." to pay ".$owner->username.". ".CTRL_B."!sellhouse !mortgage !starttrade !bankrupt".CTRL_B);
			if ($assets < $payment)
				$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$assets." in assets! You cannot pay off this debt unless you make a successful trade. You must go ".CTRL_B."!bankrupt".CTRL_B." - you can try the other commands, but essentially, you're screwed.");
			$this->money = 0;
			$this->hastopay = array($payment,$owner);
			return;
		}
		// yes, we do.
		$owner->addMoney($payment);
		$this->money -= $payment;
		$this->hastopay = array(0,"");
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." paid off the debt to ".$owner->username." in full! You now have ".DOGE.$this->money.".");
	}
	
	function payToBank($payment) {
		if ($this->money < $payment) {
			// nope. give the creditor what they can and set them in has-to-raise mode.
			$payment -= $this->money;
			// calculate our total assets
			$assets = $this->getAssets(true);
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$this->money."! You have to raise ".DOGE.$payment." to pay the bank. ".CTRL_B."!sellhouse !mortgage !starttrade !bankrupt".CTRL_B);
			if ($assets < $payment)
				$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$assets." in assets! You cannot pay off this debt unless you make a successful trade. You must go ".CTRL_B."!bankrupt".CTRL_B." - you can try the other commands, but essentially, you're screwed.");
			$this->money = 0;
			$this->hastopay = array($payment,"Bank");
			return;
		}
		// yes, we do.
		$this->money -= $payment;
		$this->hastopay = array(0,"");
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." paid off the debt to the bank in full! You now have ".DOGE.$this->money.".");
	}
	
	function payToFreeParking($payment,$game) {
		if ($this->money < $payment) {
			// nope. give the creditor what they can and set them in has-to-raise mode.
			$payment -= $this->money;
			// calculate our total assets
			$assets = $this->getAssets(true);

			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$this->money."! You have to raise ".DOGE.$payment." to pay the bank. ".CTRL_B."!sellhouse !mortgage !starttrade !bankrupt".CTRL_B);
			if ($assets < $payment)
				$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You only have ".DOGE.$assets." in assets! You cannot pay off this debt unless you make a successful trade. You must go ".CTRL_B."!bankrupt".CTRL_B." - you can try the other commands, but essentially, you're screwed.");
			$this->money = 0;
			$this->hastopay = array($payment,"Middle");
			return;
		}
		// yes, we do.
		$this->money -= $payment;
		$game->freeParking += $payment;
		$this->hastopay = array(0,"");
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->username.CTRL_B." paid off the debt to the bank in full! You now have ".DOGE.$this->money.".");
	}
	
	function payEveryone($payment,$game) {
		$start = 0;
		$players = array_keys($game->players);
		if ($this->lastpersonpaid != "") {
			$numPlayer = array_search($this->lastpersonpaid->username,$players);
			if ($numPlayer !== false) $i = $numPlayer;
		}
		for ($i = $start; $i < count($players); $i++) {
			$p = $game->players[$players[$i]];
			if ($p->getUsername() == $this->username) continue;
			$this->payMoney($payment,$p);
			if ($this->hastopay[0] > 0) {
				$this->lastpersonpaid = $p;
				return;
			}
		}
		$this->lastpersonpaid = "";
	}
	
	function getPaidByEveryone($payment,$game) {
		// Get a payment from everyone (like: It is your birthday, collect 10 DOGE from each player)
		// Stops if someone can't pay us so they can raise the money.
		$start = 0;
		$players = array_keys($game->players);
		if ($this->lastpersonpaid != "") {
			$numPlayer = array_search($this->lastpersonpaid->username,$players);
			if ($numPlayer !== false) $i = $numPlayer;
		}
		for ($i = $start; $i < count($players); $i++) {
			$p = $game->players[$players[$i]];
			if ($p->getUsername() == $this->username) continue;
			$p->payMoney($payment,$this);
			if ($p->hastopay[0] > 0) {
				$this->lastpersonpaid = $p;
				return;
			}
		}
		$this->lastpersonpaid = "";
	}
	
	function getAssets($notMoney = false) {
		// getAssets(): returns the total assets of this Player
		// $notMoney: if true, does not include money.
		$ret = ($notMoney?0:$this->money);
		// iterate through all properties.
		foreach ($this->properties as $prop) {
			// if it's unmortgaged...
			if ($prop->mortgaged) continue;
			// ...add the sell value of the houses....
			$ret += $prop->houses * ($prop->getHouseValue() / 2);
			// ...and the mortgage value.
			$ret += $prop->getMortgage();
		}
		return $ret;
	}
	
	function canEndTurn() {
		return (($this->hastopay[0] == 0) && ($this->lastpersonpaid == "") && ($this->status == ""));
	}
	
	function hasThisSet($set) {
		if ($set < 0) return false; // no concept of sets for stations + utilities
		$numInSet = 0;
		foreach ($this->properties as $prop)
			if ($prop->getGroup() == $set) {
				if ($prop->mortgaged) {
					$numInSet = 0;
					break;
				}
				$numInSet++;
			}
		if ($numInSet == (($set == 0) || ($set == 7)?2:3)) return true;
		return false;
	}
	function getJsonData() {
		$var = get_object_vars($this);
        foreach($var as &$value)
			if ((is_object($value)) && (method_exists($value,'getJsonData'))) $value = $value->getJsonData();
		return $var;
    }
}

class Property extends Space {
	// defines all properties.
	private $price;
	private $rents = array();
	private $type = PROPERTY;
	private $group = 0;
	private $mortgage;
	public $owner = "";
	public $mortgaged = false;
	public $houses = 0;
	function getPrice() { return $this->price; }
	function setPrice($p,$game) {
		if ($game->auction == $this) $this->price = $p;
	}
	function getRents() { return $this->rents; }
	function getType() { return $this->type; }
	function getGroup() { return $this->group; }
	function getMortgage() { return $this->mortgage; }
	function __construct($n,$p,$r,$t,$g,$m) {
		if (!(($t >= PROPERTY) && ($t <= UTILITY))) throw new Exception(sprintf("Type passed (%d) is invalid!",$t));
		if (($g < 0) && ($t == PROPERTY)) throw new Exception(sprintf("Group passed (%d) is invalid!",$g));
		if ($p < 0) throw new Exception(sprintf("Price passed (%d) is invalid!",$p));
		if ($m < 0) throw new Exception(sprintf("Mortgage value passed (%d) is invalid!",$m));
		$this->type = $t;
		$this->rents = $r;
		$this->price = $p;
		$this->name = $n;
		$this->mortgage = $m;
		$this->group = $g;
	}
	function getNameIRC() {
		if ($this->group < 0) return CTRL_B.$this->name.CTRL_B;
		// overriden to show the property's colour
		$colourarray = array(0=>5,1=>11,2=>13,3=>7,4=>4,5=>8,6=>9,7=>6);
		return CTRL_K.$colourarray[$this->group].",1".CTRL_B.$this->name.CTRL_O;
	}
	function doSomething($game,$player,$max = false) {
		// $max = true is for the chance cards where "advance to the nearest utility/station and pay 10*dice or double the usual"
		// is this property owned?
		if ($this->owner === "") {
			// nope.
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"This property is unowned. ".CTRL_B.$player->getUsername().CTRL_B.", you may either ".CTRL_B."!buy".CTRL_B." it for ".DOGE.$this->price." or ".CTRL_B."!auction".CTRL_B." it.");
			$player->status = "Landed on Unowned Property";
			return;
		}
		// are we the owner?
		else if ($this->owner === $player) {
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"This property is yours!");
			return;
		}
		// is it mortgaged?
		else if ($this->mortgaged) {
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"This property belongs to ".$this->owner->username." but is mortgaged. You pay nothing.");
			return;
		}
		// time to pay up
		// how much ?
		$payment = $this->rents[$this->houses];
		// are we a station?
		if ($this->type == STATION) {
			// how many stations does this player own?
			$stations = 0;
			foreach (array(5,15,25,35) as $prop)
				if ($game->spaces[$prop]->owner == $this->owner) $stations++;
			// use the payment based on this value
			$payment = $this->rents[$stations - 1];
			if ($max) $payment *= 2;
		}
		// are we a utility?
		else if ($this->type == UTILITY) {
			$utils = 0;
			foreach (array(12,28) as $prop)
				if ($game->spaces[$prop]->owner == $this->owner) $utils++;
			// use the payment based on the dice roll + this value.
			$payment = ($max?10:$this->rents[$utils - 1]) * $player->lastdice;
		}
		else if ((!$this->houses) && ($this->owner->hasThisSet($this->group))) $payment *= 2; // double if set + not developed
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"This property belongs to ".$this->owner->username.". Your rent is ".DOGE.$payment);
		$player->payMoney($payment,$this->owner);
	}
	function getHouseValue() {
		return (int)round((float)(($this->group +1)/2))*50;
	}
}

class CardSpace extends Space {
	// defines chance / chest spaces
	private $type = CHANCE;
	function getType() { return $this->type; }
	function __construct($n,$t) {
		if (!(($t >= CHANCE) && ($t <= CHEST))) throw new Exception(sprintf("Type passed (%d) is invalid!",$t));
		$this->type = $t;
		$this->name = $n;
	}
	function getNameIRC() { return CTRL_B.$this->name.CTRL_B; }
	function doSomething($game,$player) {
		if ($this->type == CHANCE) {
			$card = $game->chance[$game->currchance];
			$game->currchance++;
			if ($game->currchance > (count($game->chance) -1)) $game->currchance = 0;
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B."CHANCE".CTRL_B.": ".$card->getText());
			$card->doSomething($game,$player);
		} else { // CHEST
			$card = $game->chest[$game->currchest];
			$game->currchest++;
			if ($game->currchest > (count($game->chest) -1)) $game->currchest = 0;
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B."COMOONITY CHEST".CTRL_B.": ".$card->getText());
			$card->doSomething($game,$player);
		}
	}
}

class Card {
	// defines a chance / chest card
	private $cardtype = CHANCE;
	private $actiontype = ACTION_MOVE;
	private $subaction;
	private $text;
	function getType() { return $this->cardtype; }
	function getAction() { return $this->actiontype; }
	function getText() { return $this->text; }
	function __construct($tx,$t,$a,$s) {
		if (!(($t >= CHANCE) && ($t <= CHEST))) throw new Exception(sprintf("Type passed (%d) is invalid!",$t));
		if (!(($a >= ACTION_MOVE) && ($t <= ACTION_CASH_PROPERTYDEV))) throw new Exception(sprintf("Action passed (%d) is invalid!",$a));
		$this->cardtype = $t;
		$this->actiontype = $a;
		$this->text = $tx;
		$this->subaction = $s;
	}
	function doSomething($game,$player) {
		switch ($this->actiontype) {
			case ACTION_MOVE:
				// if we go to jail, do not pass go and do not collect 200 DOGE :)
				$passgo = (($this->subaction != 10) && ($player->space > $this->subaction));
				$player->space = $this->subaction;
				if ($this->subaction == 10) {
					$player->jailturn = 0;
					$player->gotdouble = false;
				}
				if ($passgo) {
					$player->money += 200;
					$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B." passed Launchpad and collected ".DOGE."200!");
				}
				$game->spaces[$player->space]->doSomething($game,$player);
				break;
			case ACTION_MOVE_UTILITY:
				// utilities are space 12 and 28.
				// where's the nearest one to us?
				if ($player->space < 12) $player->space = 12;
				elseif ($player->space < 28) $player->space = 28;
				else {
					// passed go. :)
					$player->money += 200;
					$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B." passed Launchpad and collected ".DOGE."200!");
					$player->space = 12;
				}
				// do what we need to do.
				$game->spaces[$player->space]->doSomething($game,$player,true);
				break;
			case ACTION_MOVE_STATION:
				// stations are spaces 5, 15, 25, and 35
				// where's the nearest one to us?
				if ($player->space < 5) $player->space = 5;
				elseif ($player->space < 15) $player->space = 15;
				elseif ($player->space < 25) $player->space = 25;
				elseif ($player->space < 35) $player->space = 35;
				else {
					// passed go. :)
					$player->money += 200;
					$GLOBALS["irc"]->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B." passed Launchpad and collected ".DOGE."200!");
					$player->space = 5;
				}
				// do what we need to do.
				$game->spaces[$player->space]->doSomething($game,$player,true);
				break;
			case ACTION_MOVEBACK:
				// if we're less than 0, move back that number of spaces
				if ($this->subaction < 0) $player->space += $this->subaction;
				// if not, just move directly to that space
				else $player->space = $this->subaction;
				// do what we need to do.
				$game->spaces[$player->space]->doSomething($game,$player);
				break;
			case ACTION_CASH:
				// if we're less than 0, we pay. if not, we collect.
				if ($this->subaction > 0) $player->addMoney($this->subaction);
				else $player->payToFreeParking(($this->subaction * -1),$game);
				break;
			case ACTION_CASH_PLAYER:
				// if we're less than 0, we pay every player. if not, we collect from every player.
				if ($this->subaction > 0) $player->getPaidByEveryone($this->subaction,$game);
				else $player->payEveryone(($this->subaction * -1),$game);
				break;
			case ACTION_CASH_PROPERTY:
				// if we're less than 0, we pay for every property. if not, we collect for every property.
				if ($this->subaction > 0) $player->addMoney(($this->subaction * count($player->properties)));
				else $player->payToFreeParking((($this->subaction * -1) * count($player->properties)),$game);
				break;
			case ACTION_CASH_PROPERTYDEV:
				// figure out how much to pay
				$pay = 0;
				foreach ($player->properties as $p) {
					if ($p->houses > 0) {
						if ($p->houses == 5) $pay += $this->subaction[1];
						else $pay += ($this->subaction[0] * $p->houses);
					}
				}
				// and pay it!
				$player->payToFreeParking($pay,$game);
				break;
			case ACTION_OUTOFJAIL:
				$player->cardoutofjail = true;
				break;
		}
	}
	function getJsonData() {
		$var = get_object_vars($this);
        foreach($var as &$value)
			if ((is_object($value)) && (method_exists($value,'getJsonData'))) $value = $value->getJsonData();
		return $var;
    }
}

class TaxSpace extends Space {
	// defines income / super tax
	private $price = 0;
	function getPrice() { return $this->price; }
	function getNameIRC() { return CTRL_B.$this->name.CTRL_B; }
	function __construct($n,$p) {
		if ($p < 0) throw new Exception(sprintf("Price passed (%d) is invalid!",$p));
		$this->name = $n;
		$this->price = $p;
	}
	function doSomething($game,$player) {
		$player->payToFreeParking($this->price,$game);
	}
}

class Trade {
	// defines a trade.
	public $trader;
	public $tradewith;
	public $giveproperties = array();
	public $givecard = false;
	public $givemoney = 0;
	public $takeproperties = array();
	public $takecard = false;
	public $takemoney = 0;
	public $proposed = false;
	function __construct($t,$n) {
		$this->trader = $t;
		$this->tradewith = $n;
	}
	function getJsonData() {
		$var = get_object_vars($this);
        foreach($var as &$value)
			if ((is_object($value)) && (method_exists($value,'getJsonData'))) $value = $value->getJsonData();
		return $var;
    }
}

class monopoly {
	var $game = false;
	var $thid = false;
	var $thid_auction = false;
	var $lastbidder;
	function takeTurn(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if (!$this->game->players[$data->nick]->gotdouble) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You already took your turn!");
			return;
		}
		if (!$this->game->players[$data->nick]->canEndTurn()) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You cannot take your turn yet!");
			return;
		}
		$this->game->players[$data->nick]->takeTurn();
		if ($this->game->players[$data->nick]->jailturn === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just landed on ".$this->game->spaces[$this->game->players[$data->nick]->space]->getNameIRC());
			$this->game->spaces[$this->game->players[$data->nick]->space]->doSomething($this->game,$this->game->players[$data->nick]);
		}
		if ($this->game->players[$data->nick]->canEndTurn())
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".CTRL_B.($this->game->players[$data->nick]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
	}
	function endTurn(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if (!$this->game->players[$data->nick]->canEndTurn()) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You cannot end your turn yet!");
			return;
		}
		if ($this->game->players[$data->nick]->gotdouble) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You got a double, take another turn!");
			return;
		}
		if ($this->game->trading !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": A trade is in progress!");
			return;
		}
		$players = array_keys($this->game->players);
		$numPlayer = array_search($data->nick,$players);
		$numPlayer++;
		if ($numPlayer > (count($this->game->players) -1)) $numPlayer = 0;
		$this->game->turn = $players[$numPlayer];
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"It is now ".CTRL_B.$this->game->turn.CTRL_B."'s turn.");
		// is this player in jail?
		if ($this->game->players[$this->game->turn]->jailturn !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You found an orphaned block. ".CTRL_B."!payfine !taketurn".($this->game->players[$this->game->turn]->cardoutofjail?" !usecard":""));
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You can still do the usual actions, ".CTRL_B."!buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} else
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B."!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		$this->game->players[$this->game->turn]->gotdouble = true;
		file_put_contents("/tmp/moon.info",json_encode($this->game));
	}
	function payFine(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->players[$data->nick]->jailturn === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You haven't found an orphaned block!");
			return;
		}
		if ($this->game->players[$data->nick]->money < 50) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You don't have ".DOGE."50 to pay the fine. ".CTRL_B."!taketurn".CTRL_B." or just raise the money now.");
			return;
		}
		$this->game->players[$data->nick]->payToFreeParking(50,$this->game);
		$this->game->players[$data->nick]->jailturn = false;
		$this->takeTurn($irc,$data);
	}
	function useCard(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->players[$data->nick]->jailturn === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You haven't found an orphaned block!");
			return;
		}
		if (!$this->game->players[$data->nick]->cardoutofjail) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You don't have a Recover from Orphaned Block Free card!");
			return;
		}
		// use the card
		$this->game->players[$data->nick]->cardoutofjail = false;
		$this->game->players[$data->nick]->jailturn = false;
		$this->takeTurn($irc,$data);
	}
	function buyProperty(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->players[$data->nick]->status != "Landed on Unowned Property") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": What do you think you are buying?");
			return;
		}
		$p = $this->game->spaces[$this->game->players[$data->nick]->space];
		if ($this->game->players[$data->nick]->money < $p->getPrice()) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You don't have enough money to buy this property! ".CTRL_B."!auction".CTRL_B." it or just raise the money now.");
			return;
		}
		$this->game->players[$data->nick]->payToBank($p->getPrice());
		$p->owner = $this->game->players[$data->nick];
		$this->game->players[$data->nick]->properties[] = $p;
		$this->game->players[$data->nick]->status = "";
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B." bought ".$p->getNameIRC()."!");
		if ($this->game->players[$data->nick]->canEndTurn())
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".CTRL_B.($this->game->players[$data->nick]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
	}
	function auctionProperty(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->players[$data->nick]->status != "Landed on Unowned Property") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": What do you think you are putting up for auction?");
			return;
		}
		if ($this->game->auction !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is already an auction happening!");
			return;
		}
		$p = $this->game->spaces[$this->game->players[$data->nick]->space];
		$this->game->auction = $p;
		$this->game->auction->setPrice(0,$this->game);
		$this->thid_auction = $irc->registerTimeHandler(mt_rand(30000,120000),$this,"completeAuction");
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,$p->getNameIRC()." has just been put up for auction! Someone start the ".CTRL_B."!bid increment".CTRL_B."'ing!");
	}
	function bidProperty(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->auction === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no auction happening!");
			return;
		}
		// get the price
		$inc = str_replace("!bid ","",$data->message);
		$inc = (int)filter_var($inc,FILTER_VALIDATE_INT);
		if ($inc < 1) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That's not a whole number greater than 0!");
			return;
		}
		if ($this->game->players[$data->nick]->money < ($this->game->auction->getPrice() + $inc)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You don't have enough money to bid that much!");
			return;
		}
		$this->game->auction->setPrice(($this->game->auction->getPrice() + $inc),$this->game);
		$this->lastbidder = $this->game->players[$data->nick];
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just bid ".DOGE.$this->game->auction->getPrice()."!");
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"Bidding is still open, ".CTRL_B."!bid increment");
	}
	function completeAuction(&$irc) {
			// they won the auction, give them the property
			if ($this->game === false) {
				$irc->unregisterTimeId($this->thid_auction);
				return;
			}
			if (!is_object($this->lastbidder))
				return; // keep the auction going, nobody bid
			if (!is_object($this->game->auction)) {
				// something screwed up.
				$irc->unregisterTimeId($this->thid_auction);
				return;
			}
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->lastbidder->username.CTRL_B." just won the auction and bought ".$this->game->auction->getNameIRC()."!");
			$this->lastbidder->payToBank($this->game->auction->getPrice());
			$this->game->auction->owner = $this->lastbidder;
			$this->lastbidder->properties[] = $this->game->auction;
			$this->game->auction = false;
			$irc->unregisterTimeId($this->thid_auction);
			$this->lastbidder = "";
			// let the auction starter finish their turn up
			$this->game->players[$this->game->turn]->status = "";
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
	}
	// --- building and selling houses ---
	function buildHouse(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		$bname = str_replace("!buildhouse ","",$data->message);
		if ($bname == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are building on? [".CTRL_B."!buildhouse property".CTRL_B."]");
			return;
		}
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are building on? [".CTRL_B."!buildhouse property".CTRL_B."]");
			return;
		}
		if ($p->owner->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
			return;
		}
		// is this a property that can be built on?
		$set = $p->getGroup();
		if ($set < 0) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You can't build on this space program or utility!");
			return;
		}
		// do we have the entire set?
		$propsInSet = 0;
		$houses = array();
		foreach ($this->game->players[$data->nick]->properties as $prop) {
			if ($prop->getGroup() == $set) {
				$propsInSet++;
				// is one of them mortgaged?
				if ($prop->mortgaged) {
					$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You can't build when ".$prop->getNameIRC()." is mortgaged!");
					return;
				}
				if ($prop != $p) $houses[] = array($prop->getNameIRC(),$prop->houses);
			}
		}
		if ($propsInSet != (($set == 0) || ($set == 7)?2:3)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You don't have the full set of properties!");
			return;
		}
		// does this property have a hotel already?
		if ($p->houses == 5) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This property is fully developed!");
			return;
		}
		// we must even build
		foreach ($houses as $h) {
			if (($h[1] != $p->houses) && ($h[1] -1 != $p->houses)) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You have to even build houses. ".$h[0]." has ".$h[1]." houses.");
				return;
			}
		}
		// checks done, let's build!
		if ($this->game->players[$data->nick]->money < $p->getHouseValue()) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You don't have enough money to build a ".($p->houses==4?"hotel":"house")." here! You'd need to raise ".DOGE.($p->getHouseValue() - $this->game->players[$data->nick]->money).".");
			return;
		}
		$this->game->players[$data->nick]->payToBank($p->getHouseValue());
		$p->houses++;
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just built a ".($p->houses==5?"hotel":"house")." on ".$p->getNameIRC()."!".($p->houses!=5?" It now has ".$p->houses." houses.":""));
	}
	function sellHouse(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		// so someone else can pay.
		if (($this->game->turn != $data->nick) || (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] != 0)) || ($this->game->players[$data->nick]->hastopay[1] == $this->game->players[$this->game->turn])) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		$bname = str_replace("!sellhouse ","",$data->message);
		if ($bname == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are selling from? [".CTRL_B."!sellhouse property".CTRL_B."]");
			return;
		}
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are selling from? [".CTRL_B."!sellhouse property".CTRL_B."]");
			return;
		}
		if ($p->owner->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
			return;
		}
		if ($p->houses == 0) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This property is not developed!");
			return;
		}
		// -- even build rule --
		$set = $p->getGroup();
		foreach ($this->game->players[$data->nick]->properties as $prop) {
			if ($prop->getGroup() == $set) {
				if (($prop->houses != $p->houses) && ($prop->houses +1 != $p->houses)) {
					$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You have to even build houses. ".$prop->getNameIRC()." has ".$prop->houses." houses.");
					return;
				}
			}
		}
		$p->houses--;
		$this->game->players[$data->nick]->addMoney(($p->getHouseValue() / 2));
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just sold a ".($p->houses==4?"hotel":"house")." on ".$p->getNameIRC()."!".($p->houses!=4?" It now ".($p->houses==0?"is undeveloped":"has ".$p->houses." houses").".":""));
		if ($this->game->turn != $data->nick) {
			// this person has to raise money.
			$p = $this->game->players[$data->nick];
			if ($p->hastopay[0] != 0) {
				if ($p->lastpersonpaid != "") $p->payEveryone($p->hastopay[0],$this->game);
				elseif ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
				elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
				else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			}
			// if this isn't the case, >what the fuck?<
			// throw an exception, this is obviously an awkward bug that must be fixed
			else throw new Exception("it's not this user's turn but they don't have to pay anything?");
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] == 0)) {
			// the current player was in the middle of collecting from everyone else.
			$this->game->players[$this->game->turn]->getPaidByEveryone($this->game->players[$data->nick]->hastopay[0],$this->game);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif ($this->game->players[$data->nick]->hastopay[0] != 0) {
			$p = $this->game->players[$data->nick];
			if ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
			elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
			else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		}
	}
	// --- mortgaging and unmortgaging ---
	function mortgageProperty(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		// so someone else can pay.
		if (($this->game->turn != $data->nick) || (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] != 0)) || ($this->game->players[$data->nick]->hastopay[1] == $this->game->players[$this->game->turn])) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		$bname = str_replace("!mortgage ","",$data->message);
		if ($bname == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are mortgaging? [".CTRL_B."!mortgage property".CTRL_B."]");
			return;
		}
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are mortgaging? [".CTRL_B."!mortgage property".CTRL_B."]");
			return;
		}
		if ($p->owner->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
			return;
		}
		if ($p->mortgaged) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This property is already mortgaged!");
			return;
		}
		if ($p->houses > 0) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This property has houses, sell them first!");
			return;
		}
		$p->mortgaged = true;
		$this->game->players[$data->nick]->addMoney($p->getMortgage());
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just mortgaged ".$p->getNameIRC()."!");
		if ($this->game->turn != $data->nick) {
			// this person has to raise money.
			$p = $this->game->players[$data->nick];
			if ($p->hastopay[0] != 0) {
				if ($p->lastpersonpaid != "") $p->payEveryone($p->hastopay[0],$this->game);
				elseif ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
				elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
				else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			}
			// if this isn't the case, >what the fuck?<
			// throw an exception, this is obviously an awkward bug that must be fixed
			else throw new Exception("it's not this user's turn but they don't have to pay anything?");
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] == 0)) {
			// the current player was in the middle of collecting from everyone else.
			$this->game->players[$this->game->turn]->getPaidByEveryone($this->game->players[$data->nick]->hastopay[0],$this->game);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif ($this->game->players[$data->nick]->hastopay[0] != 0) {
			$p = $this->game->players[$data->nick];
			if ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
			elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
			else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		}
	}
	function unmortgageProperty(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->turn != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		$bname = str_replace("!unmortgage ","",$data->message);
		if ($bname == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are unmortgaging? [".CTRL_B."!unmortgage property".CTRL_B."]");
			return;
		}
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Where do you think you are unmortgaging? [".CTRL_B."!unmortgage property".CTRL_B."]");
			return;
		}
		if ($p->owner->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
			return;
		}
		if (!$p->mortgaged) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This property is not mortgaged!");
			return;
		}
		$price = $p->getMortgage();
		$tenp = (int)($price / 10);
		$price += $tenp;
		if ($this->game->players[$data->nick]->money < $price) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You don't have enough money to unmortgage this property! You'd need to raise ".DOGE.($price - $this->game->players[$data->nick]->money).".");
			return;
		}
		$this->game->players[$data->nick]->payToBank($price);
		$p->mortgaged = false;
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just unmortgaged ".$p->getNameIRC()."!");
	}
	// -- trading --
	function startTrade(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if (($this->game->turn != $data->nick) || (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] != 0)) || ($this->game->players[$data->nick]->hastopay[1] == $this->game->players[$this->game->turn])) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": A trade is already in progress!");
			return;
		}
		$bname = str_replace("!starttrade ","",$data->message);
		if (substr($bname,-1) == " ") $bname = substr($bname,0,-1);
		if (!array_key_exists($bname,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This person isn't playing! [".CTRL_B."!starttrade username".CTRL_B."]");
			return;
		}
		if ($bname == $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You can't trade with yourself!");
			return;
		}
		$this->game->trading = new Trade($this->game->players[$data->nick],$this->game->players[$bname]);
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just started trading with ".CTRL_B.$bname.CTRL_B." - ".CTRL_B."!addpropgive !delpropgive !addproptake !delproptake !addcashgive !delcashgive !addcashtake !delcashtake ".((!(($this->game->players[$data->nick]->cardoutofjail) && ($this->game->players[$bname]->cardoutofjail)))?($this->game->players[$data->nick]->cardoutofjail?"!addcardgive !delcardgive ":"").($this->game->players[$bname]->cardoutofjail?"!addcardtake !delcardtake ":""):"")."!dotrade !stoptrade");
	}
	function stopTrade(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		$this->game->trading = false;
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just stopped a trade in progress.");
	}
	// -- trading : properties --
	function addProp(&$irc,&$data) {
		$cmd = str_replace("!addprop","",$data->message);
		$part = substr($cmd,0,strpos($cmd," "));
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		$bname = str_replace($part." ","",$cmd);
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't exist. [".CTRL_B."!addprop".$part." property".CTRL_B."]");
			return;
		}
		if ($p->houses > 0) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You cannot trade a property with houses!");
			return;
		}
		if ($part == "give") {
			if ($p->owner->username != $data->nick) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
				return;
			}
			if (in_array($p,$this->game->trading->giveproperties)) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property is already in the list of properties to ".$part."!");
				return;
			}
			$this->game->trading->giveproperties[] = $p;
		} else { // take
			if ($p->owner != $this->game->trading->tradewith) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B."!");
				return;
			}
			if (in_array($p,$this->game->trading->takeproperties)) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property is already in the list of properties to ".$part."!");
				return;
			}
			$this->game->trading->takeproperties[] = $p;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".$p->getNameIRC()." has been added to the list of properties to ".$part.".");
	}
	function delProp(&$irc,&$data) {
		$cmd = str_replace("!delprop","",$data->message);
		$part = substr($cmd,0,strpos($cmd," "));
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		$bname = str_replace($part." ","",$cmd);
		$p = $this->game->getProperty($bname);
		if ($p === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't exist. [".CTRL_B."!addprop".$part." property".CTRL_B."]");
			return;
		}
		if ($part == "give") {
			if ($p->owner->username != $data->nick) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to you!");
				return;
			}
			$prop = array_search($p,$this->game->trading->giveproperties);
			if ($prop === false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property isn't being traded!");
				return;
			}
			unset($this->game->trading->giveproperties[$prop]);
			$this->game->trading->giveproperties = array_values($this->game->trading->giveproperties);
		} else { // take
			if ($p->owner != $this->game->trading->tradewith) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property doesn't belong to ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B."!");
				return;
			}
			$prop = array_search($p,$this->game->trading->takeproperties);
			if ($prop === false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That property isn't being traded!");
				return;
			}
			unset($this->game->trading->takeproperties[$prop]);
			$this->game->trading->takeproperties = array_values($this->game->trading->takeproperties);
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".$p->getNameIRC()." has been removed from the list of properties to ".$part.".");
	}
	// -- trading : cash --
	function addCash(&$irc,&$data) {
		$cmd = str_replace("!addcash","",$data->message);
		$part = substr($cmd,0,strpos($cmd," "));
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		$cash = (int)filter_var(str_replace($part." ","",$cmd),FILTER_VALIDATE_INT);
		if ($cash < 1) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Sorry, how much is that? There's no number there!");
			return;
		}
		if ($part == "give") {
			if ($this->game->players[$data->nick]->money < ($this->game->trading->givemoney + $cash)) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You don't have ".DOGE.($this->game->trading->givemoney + $cash)."!");
				return;
			}
			$this->game->trading->givemoney += $cash;
		} else { // take
			if ($this->game->trading->tradewith->money < ($this->game->trading->takemoney + $cash)) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B." doesn't have ".DOGE.($this->game->trading->givemoney + $cash)."!");
				return;
			}
			$this->game->trading->takemoney += $cash;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".DOGE.$cash." has been added to the amount of money to ".$part.".");
	}
	function delCash(&$irc,&$data) {
		$cmd = str_replace("!delcash","",$data->message);
		$part = substr($cmd,0,strpos($cmd," "));
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		$cash = (int)filter_var(str_replace($part." ","",$cmd),FILTER_VALIDATE_INT);
		if ($cash < 1) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": Sorry, how much is that? There's no number there!");
			return;
		}
		if ($part == "give") {
			if ($this->game->trading->givemoney < $cash)
				$cash = $this->game->trading->givemoney;
			$this->game->trading->givemoney -= $cash;
		} else { // take
			if ($this->game->trading->takemoney < $cash)
				$cash = $this->game->trading->takemoney;
			$this->game->trading->takemoney -= $cash;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".DOGE.$cash." has been removed from the amount of money to ".$part.".");
	}
	// -- trading : get out of jail card --
	function addCard(&$irc,&$data) {
		$cmd = str_replace("!addcard","",$data->message);
		if (strpos($cmd," ") !== false)
			$part = substr($cmd,0,strpos($cmd," "));
		else
			$part = $cmd;
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		if ($part == "give") {
			if (!$this->game->players[$data->nick]->cardoutofjail) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You don't have a Recover from Orphaned Block Free card!");
				return;
			}
			if ($this->game->trading->tradewith->cardoutofjail) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B." already has a Recover from Orphaned Block Free card!");
				return;
			}
			if ($this->game->trading->givecard) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You already added your Recover from Orphaned Block Free card!");
				return;
			}
			$this->game->trading->givecard = true;
		} else { // take
			if (!$this->game->trading->tradewith->cardoutofjail) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B." doesn't have a Recover from Orphaned Block Free card!");
				return;
			}
			if ($this->game->players[$data->nick]->cardoutofjail) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You already have a Recover from Orphaned Block Free card!");
				return;
			}
			if ($this->game->trading->takecard) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You already added ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B."'s Recover from Orphaned Block Free card!");
				return;
			}
			$this->game->trading->takecard = true;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": A Recover from Orphaned Block card has been added to the amount of assets to ".$part.".");
	}
	function delCard(&$irc,&$data) {
		$cmd = str_replace("!delcard","",$data->message);
		if (strpos($cmd," ") !== false)
			$part = substr($cmd,0,strpos($cmd," "));
		else
			$part = $cmd;
		if (($part != "give") && ($part != "take")) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		if ($part == "give") {
			if (!$this->game->trading->givecard) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You haven't added your Recover from Orphaned Block Free card!");
				return;
			}
			$this->game->trading->givecard = false;
		} else { // take
			if (!$this->game->trading->takecard) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You haven't added ".CTRL_B.$this->game->trading->tradewith->username.CTRL_B."'s Recover from Orphaned Block Free card!");
				return;
			}
			$this->game->trading->takecard = false;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": A Recover from Orphaned Block card has been removed from the amount of assets to ".$part.".");
	}
	// -- trading : do it! --
	function doTrade(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->trader->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade has already been proposed!");
			return;
		}
		$this->game->trading->proposed = true;
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->trading->tradewith->username.CTRL_B.": ".CTRL_B."!tradeinfo !accepttrade !denytrade");
	}
	function denyTrade(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->tradewith->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not being traded with!");
			return;
		}
		if (!$this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade hasn't been proposed!");
			return;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." denied the trade proposed by ".CTRL_B.$this->game->trading->trader->username.CTRL_B.".");
		$this->game->trading = false;
	}
	function acceptTrade(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		if ($this->game->trading->tradewith->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not being traded with!");
			return;
		}
		if (!$this->game->trading->proposed) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": This trade hasn't been proposed!");
			return;
		}
		// ok, let's iterate through everything and see.
		// the easiest thing for sure is get out of jail cards.
		if ($this->game->trading->givecard) {
			$this->game->trading->trader->cardoutofjail = false;
			$this->game->players[$data->nick]->cardoutofjail = true;
		} else if ($this->game->trading->takecard) {
			$this->game->trading->trader->cardoutofjail = true;
			$this->game->players[$data->nick]->cardoutofjail = false;
		}
		// now money
		if ($this->game->trading->givemoney > 0) {
			$this->game->trading->trader->money -= $this->game->trading->givemoney;
			$this->game->players[$data->nick]->money += $this->game->trading->givemoney;
		}
		if ($this->game->trading->takemoney > 0) {
			$this->game->trading->trader->money += $this->game->trading->takemoney;
			$this->game->players[$data->nick]->money -= $this->game->trading->takemoney;
		}
		// and now property. this is the hardest of all :)
		foreach($this->game->trading->giveproperties as $p) {
			// take it from the trader
			$k = array_search($p,$this->game->trading->trader->properties);
			// we don't expect that to fail as we already did error checking
			unset($this->game->trading->trader->properties[$k]);
			$this->game->trading->trader->properties = array_values($this->game->trading->trader->properties);
			// give it to the tradee
			$p->owner = $this->game->players[$data->nick];
			$this->game->players[$data->nick]->properties[] = $p;
		}
		foreach($this->game->trading->takeproperties as $p) {
			// take it from the tradee
			$k = array_search($p,$this->game->players[$data->nick]->properties);
			// we don't expect that to fail as we already did error checking
			unset($this->game->players[$data->nick]->properties[$k]);
			$this->game->players[$data->nick]->properties = array_values($this->game->players[$data->nick]->properties);
			// give it to the trader
			$p->owner = $this->game->trading->trader;
			$this->game->trading->trader->properties[] = $p;
		}
		// and done!
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." accepted the trade proposed by ".CTRL_B.$this->game->trading->trader->username.CTRL_B.".");
		if ($this->game->turn != $this->game->trading->trader->username) {
			// this person has to raise money.
			$p = $this->game->trading->trader;
			if ($p->hastopay[0] != 0) {
				if ($p->lastpersonpaid != "") $p->payEveryone($p->hastopay[0],$this->game);
				elseif ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
				elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
				else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			}
			// if this isn't the case, >what the fuck?<
			// throw an exception, this is obviously an awkward bug that must be fixed
			else throw new Exception("it's not this user's turn but they don't have to pay anything?");
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif (($this->game->players[$this->game->turn]->lastpersonpaid == $this->game->trading->trader) && ($this->game->players[$this->game->turn]->hastopay[0] == 0)) {
			// the current player was in the middle of collecting from everyone else.
			$this->game->players[$this->game->turn]->getPaidByEveryone($this->game->trading->trader->hastopay[0],$this->game);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		} elseif ($this->game->trading->trader->hastopay[0] != 0) {
			$p = $this->game->trading->trader;
			if ($p->hastopay[1] == "Bank") $p->payToBank($p->hastopay[0]);
			elseif ($p->hastopay[1] == "Middle") $p->payToFreeParking($p->hastopay[0],$this->game);
			else $p->payMoney($p->hastopay[0],$p->hastopay[1]);
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		}
		$this->game->trading = false;
	}
	// -- trading : info --
	function tradeInfo(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->trading === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": There is no trade in progress!");
			return;
		}
		// given properties
		$msgs = array();
		$msgs[0] = "This trade is between ".$this->game->trading->trader->username." and ".$this->game->trading->tradewith->username.".";
		$key = 1;
		if ($this->game->trading->giveproperties != array()) {
			$msgs[$key] = "Giving properties:";
			foreach($this->game->trading->giveproperties as $p)
				$msgs[$key] .= " ".$p->getNameIRC();
			$key++;
		}
		if ($this->game->trading->givemoney > 0) {
			$msgs[$key] = "Giving ".DOGE.$this->game->trading->givemoney;
			$key++;
		}
		if ($this->game->trading->givecard) {
			$msgs[$key] = "Giving a Recover from Orphaned Block card.";
			$key++;
		}
		if ($this->game->trading->takeproperties != array()) {
			$msgs[$key] = "Taking properties:";
			foreach($this->game->trading->takeproperties as $p)
				$msgs[$key] .= " ".$p->getNameIRC();
			$key++;
		}
		if ($this->game->trading->takemoney > 0) {
			$msgs[$key] = "Taking ".DOGE.$this->game->trading->takemoney;
			$key++;
		}
		if ($this->game->trading->takecard) {
			$msgs[$key] = "Taking a Recover from Orphaned Block card.";
			$key++;
		}
		foreach ($msgs as $m)
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$m);
	}
	// -- game info --
	function gameInfo(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		$i = 1;
		$msgs[0] = "Number of players: ".count($this->game->players);
		$key = 1;
		foreach($this->game->players as $p) {
			$msgs[$key] = "Player ".$i.": ".CTRL_B.$p->getUsername().CTRL_B." - Currently on ".$this->game->spaces[$p->space]->getNameIRC().($p->jailturn !== false?" - Found Orphaned Block ":"")." - Money: ".DOGE.$p->money;
			if ($p->cardoutofjail)
				$msgs[$key] .= " - Has a Recover From Orphaned Block Free card.";
			$key++;
			$msgs[$key] = "Properties:";
			foreach($p->properties as $prop) {
				$msgs[$key] .= " ".$prop->getNameIRC();
				if ($prop->mortgaged)
					$msgs[$key] .= " ".CTRL_K."4".CTRL_B."[MORTGAGED]".CTRL_O;
				else {
					$rents = $prop->getRents();
					$payment = $rents[$prop->houses];
					// do we have the entire set?
					if (($prop->houses == 0) && ($p->hasThisSet($prop->getGroup()))) $payment *= 2;
					$msgs[$key] .= " (Rent: ".DOGE.$payment.")";
				}
				$msgs[$key] .= ",";
				if (strlen($msgs[$key]) > 402) {
					$split = strrpos($msgs[$key]," ");
					$msgs[$key +1] = substr($msgs[$key],$split);
					$msgs[$key] = substr($msgs[$key],0,$split);
					$key++;
				}
			}
			if ((array_key_exists($key,$msgs)) && ($msgs[$key] != "Properties:")) {
				$msgs[$key] = substr($msgs[$key],0,-1);
				$key++;
			}
			$i++;
		}
		if ((array_key_exists($key,$msgs)) && ($msgs[$key] == "Properties:"))
			unset($msgs[$key]);
		foreach ($msgs as $m)
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$m);
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Money in the Doge Walking pot: ".DOGE.$this->game->freeParking);
	}
	// -- start/stop game --
	function startGame(&$irc,&$data) {
		if ($this->game !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"A game is already started!");
			return;
		}
		// create a new game
		file_put_contents("/tmp/moon.info","");
		$this->game = new Game();
		// add this person to the player list
		$this->game->players[$data->nick] = new Player($data->nick);
		// advertise new game
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." started a new game! Type ".CTRL_B."!joingame".CTRL_B." within the next 2.5 minutes to join it!");
		$irc->message(SMARTIRC_TYPE_QUERY,"botserv","say #dogec0in A new game of Moonopoly has been started in the Moonopoly channel, ".CTRL_B."#dogec0in-moonopoly".CTRL_B." ! Join that channel and type ".CTRL_B."!joingame".CTRL_B." within the next 2.5 minutes to join the new game! Winner gets up to 99 DOGE!");
		$this->thid = $irc->registerTimeHandler(150000,$this,"joinTimeout");
	}
	function stopGame(&$irc,&$data) {
		if (!$irc->isOpped(GAMECHANNEL,$data->nick)) return;
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"The game isn't started!");
			return;
		}
		$reason = str_replace("!stopgame ","",$data->message);
		if ($reason == "") {
			$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"Usage: ".CTRL_B."!stopgame reason");
			return;
		}
		// just kill the game now!
		$this->game = false;
		$irc->unregisterTimeid($this->thid);
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." stopped the game: ".$reason);
	}
	// -- join the game --
	function joinGame(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->turn != "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"You cannot join this game now!");
			return;
		}
		if (count($this->game->players) == 8) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The maximum of 8 players have joined this game!");
			return;
		}
		// are we already in the player list?
		$players = array_keys($this->game->players);
		if (array_search($data->nick,$players) !== false) return;
		// add this person to the player list
		$this->game->players[$data->nick] = new Player($data->nick);
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." joined the game!");
		if (count($this->game->players) == 8) $this->joinTimeout($irc);
	}
	// -- join timeout --
	function joinTimeout(&$irc) {
		$irc->unregisterTimeid($this->thid);
		if ($this->game === false) return;
		// how many are playing?
		if (count($this->game->players) < 2) {
			// only one player?!
			$this->game = false;
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game only has one player and cannot start!");
			return;
		}
		if (file_get_contents("/tmp/moon.info") != "") return;
		$players = array_keys($this->game->players);
		$this->game->turn = $players[0];
		file_put_contents("/tmp/moon.info",json_encode($this->game));
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game has started!");
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B."!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
	}
	// -- bankrupt --
	function bankrupt(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if (!array_key_exists($data->nick,$this->game->players)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You are not playing this game!");
			return;
		}
		// so someone else can pay.
		if (($this->game->turn != $data->nick) || (($this->game->players[$this->game->turn]->lastpersonpaid->username == $data->nick) && ($this->game->players[$this->game->turn]->hastopay[0] != 0)) || ($this->game->players[$data->nick]->hastopay[1] == $this->game->turn)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": It is not your turn!");
			return;
		}
		if ($this->game->auction !== false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You cannot go bankrupt during an auction!");
			return;
		}
		// now we can go bankrupt!
		// do we have to pay someone?
		$p = $this->game->players[$data->nick];
		if ($p->hastopay[1] == "Bank") $p->hastopay[1] = "";
		if ($p->hastopay[1] == "Middle") $p->hastopay[1] = "";
		if ($p->hastopay[1] == "") {
			// nope. let's just put all our properties back up for sale.
			foreach ($p->properties as $prop) {
				$prop->owner = "";
				$prop->mortgaged = false;
				$prop->houses = 0;
			}
		} else {
			// ok. let's set all our properties houses to zero and give them to whomever we went out to.
			// this person also gets all our cash and our get out of jail card if we have one.
			foreach ($p->properties as $prop) {
				$prop->owner = $p->hastopay[1];
				$prop->houses = 0;
				$p->hastopay[1]->properties[] = $prop;
			}
			$p->hastopay[1]->addMoney($p->money);
			if ($p->cardoutofjail) $p->hastopay[1]->cardoutofjail = true;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just went bankrupt!");
		$players = array_keys($this->game->players);
		if ($this->game->turn == $data->nick) {
			$numPlayer = array_search($data->nick,$players);
			$numPlayer++;
			if ($numPlayer > (count($this->game->players) -1)) $numPlayer = 0;
			$this->game->turn = $players[$numPlayer];
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"It is now ".CTRL_B.$this->game->turn.CTRL_B."'s turn.");
			// is this player in jail?
			if ($this->game->players[$this->game->turn]->jailturn !== false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You found an orphaned block. ".CTRL_B."!payfine !taketurn".($this->game->players[$this->game->turn]->cardoutofjail?" !usecard":""));
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You can still do the usual actions, ".CTRL_B."!buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			} else
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B."!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			$this->game->players[$this->game->turn]->gotdouble = true;
		}
		unset($this->game->players[$data->nick]); // bai bai.
		// how many players are left?
		if (count($this->game->players) == 1) {
			// looks like we have a winner.
			$players = array_keys($this->game->players);
			$this->winner($this->game->players[$players[0]]);
			return;
		}
		// are we involved in a trade? if so, stop it
		if ($this->game->trading !== false) {
			if ($this->game->trading->trader == $p) $this->game->trading = false;
			elseif ($this->game->trading->tradewith == $p) $this->game->trading = false;
		}
		if (($this->game->players[$this->game->turn]->lastpersonpaid == $p) && ($this->game->players[$this->game->turn]->hastopay[0] != 0)) {
			// the current player was in the middle of collecting from everyone else.
			if ($numPlayer > count($players)) $this->game->players[$this->game->turn]->lastpersonpaid == "";
			else {
				$this->game->players[$this->game->turn]->lastpersonpaid == $this->game->players[$players[$numPlayer]];
				$this->game->players[$this->game->turn]->getPaidByEveryone($this->game->trading->trader->hastopay[0],$this->game);
			}
			if ($this->game->players[$this->game->turn]->canEndTurn())
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B.($this->game->players[$this->game->turn]->gotdouble?"!taketurn":"!endturn")." !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
		}
	}
	// -- quit/part, nick change --
	function onQuit(&$irc,&$data) {
		if ($this->game == false) return;
		if (!array_key_exists($data->nick,$this->game->players)) return;
		// bankrupt this player to the bank.
		$p = $this->game->players[$data->nick];
		foreach ($p->properties as $prop) {
			$prop->owner = "";
			$prop->mortgaged = false;
			$prop->houses = 0;
		}
		unset($this->game->players[$data->nick]); // bai bai.
		// are we involved in a trade? if so, stop it
		if ($this->game->trading !== false) {
			if ($this->game->trading->trader == $p) $this->game->trading = false;
			elseif ($this->game->trading->tradewith == $p) $this->game->trading = false;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just left the game and went bankrupt!");
		// how many players are left?
		if (count($this->game->players) == 1) {
			// looks like we have a winner.
			$players = array_keys($this->game->players);
			$this->winner($this->game->players[$players[0]]);
			$irc->unregisterTimeid($this->thid);
			return;
		}
		if ($this->game->turn == $data->nick) {
			$players = array_keys($this->game->players);
			$numPlayer = array_search($data->nick,$players);
			$numPlayer++;
			if ($numPlayer > (count($this->game->players) -1)) $numPlayer = 0;
			$this->game->turn = $players[$numPlayer];
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"It is now ".CTRL_B.$this->game->turn.CTRL_B."'s turn.");
			// is this player in jail?
			if ($this->game->players[$this->game->turn]->jailturn !== false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You found an orphaned block. ".CTRL_B."!payfine !taketurn".($this->game->players[$this->game->turn]->cardoutofjail?" !usecard":""));
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You can still do the usual actions, ".CTRL_B."!buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			} else
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B."!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			$this->game->players[$this->game->turn]->gotdouble = true;
		}
	}
	function onKick(&$irc,&$data) {
		if ($this->game == false) return;
		$data->nick = $data->rawmessageex[3];
		if (!array_key_exists($data->nick,$this->game->players)) return;
		// bankrupt this player to the bank.
		$p = $this->game->players[$data->nick];
		foreach ($p->properties as $prop) {
			$prop->owner = "";
			$prop->mortgaged = false;
			$prop->houses = 0;
		}
		unset($this->game->players[$data->nick]); // bai bai.
		// are we involved in a trade? if so, stop it
		if ($this->game->trading !== false) {
			if ($this->game->trading->trader == $p) $this->game->trading = false;
			elseif ($this->game->trading->tradewith == $p) $this->game->trading = false;
		}
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B." just left the game and went bankrupt!");
		// how many players are left?
		if (count($this->game->players) == 1) {
			// looks like we have a winner.
			$players = array_keys($this->game->players);
			$this->winner($this->game->players[$players[0]]);
			return;
		}
		if ($this->game->turn == $data->nick) {
			$players = array_keys($this->game->players);
			$numPlayer = array_search($data->nick,$players);
			$numPlayer++;
			if ($numPlayer > (count($this->game->players) -1)) $numPlayer = 0;
			$this->game->turn = $players[$numPlayer];
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"It is now ".CTRL_B.$this->game->turn.CTRL_B."'s turn.");
			// is this player in jail?
			if ($this->game->players[$this->game->turn]->jailturn !== false) {
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You found an orphaned block. ".CTRL_B."!payfine !taketurn".($this->game->players[$this->game->turn]->cardoutofjail?" !usecard":""));
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": You can still do the usual actions, ".CTRL_B."!buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			} else
				$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$this->game->turn.CTRL_B.": ".CTRL_B."!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt");
			$this->game->players[$this->game->turn]->gotdouble = true;
		}
	}
	function onNickChange(&$irc,&$data) {
		if ($this->game == false) return;
		if (!array_key_exists($data->nick,$this->game->players)) return;
		$newnick = $data->rawmessageex[2];
		// iterate through the array of players, creating a new array.
		$newarray = array();
		foreach ($this->game->players as $k=>$p) {
			if ($k == $data->nick) $newarray[$newnick] = $p;
			else $newarray[$k] = $p;
		}
		// change our username
		$newarray[$newnick]->username = $newnick;
		$this->game->players = $newarray;
		if ($this->game->turn == $data->nick) $this->game->turn = $newnick;
	}
	function onJoin(&$irc,&$data) {
		$msg = "Welcome! I am the Moonopoly bot. A game is ";
		if ($this->game !== false) $msg .= "currently";
		else $msg .= "not currently";
		$msg .= " in progress. To find out how to play Moonopoly, and to see the game board and other information about any game in progress, go to http://moonopoly.dogec0in.com/";
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,$msg);
	}
	function help(&$irc,&$data) {
		$irc->message(SMARTIRC_TYPE_NOTICE,$data->nick,"To find out how to play Moonopoly, and to see the game board and other information about any game in progress, go to http://moonopoly.dogec0in.com/");
	}
	function sqliteConnect() {
		$s = new SQLite3("../wowsuchbots.db");
		$s->busyTimeout(1000);
		return $s;
	}
	function winner($player) {
		$this->game->winner = $player;
		$this->game->turn = "";
		$this->game->players = array();
		$amount = (int)substr($player->money,-2);
		if ($amount == 0) {
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B.": Unlucky! You didn't win any Dogecoins!");
			$this->game = false;
			file_put_contents("/tmp/moon.info","");
			return;
		}
		if (!$this->game->passedgo) {
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B.": Unlucky! You didn't win any Dogecoins!");
			$this->game = false;
			file_put_contents("/tmp/moon.info","");
			return;
		}
		// is this person identified?
		$s = $this->sqliteConnect();
		$address = $s->querySingle("SELECT address from ident where nick='".$s->escapeString($player->username)."'");
		$s->close();
		if (($address === false) || (!Dogecoin::checkAddress($address))) {
			// no valid address.
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B.": You have won the game, but you are not identified! Use ".CTRL_B."!winneraddress address".CTRL_B." to send your Dogecoin address that your winnings can be sent to.");
			return;
		}
		// valid address, let's calculate the amount of doge this person should get.
		global $doge;
		try {
			$doge->sendtoaddress($address,$amount);
		} catch (Exception $e) {
			$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B.": An internal error occured trying to send your Dogecoins.. Unfortunately, you win nothing.. :(");
			$this->game = false;
			return;
		}
		// aaand kill the game finally
		$this->game = false;
		$GLOBALS['irc']->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$player->username.CTRL_B.": You won ".DOGE.$amount."!");
		file_put_contents("/tmp/moon.info","");
	}
	function winnerAddress(&$irc,&$data) {
		if ($this->game === false) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game isn't started!");
			return;
		}
		if ($this->game->winner == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,"The game is still being played!");
			return;
		}
		if ($this->game->winner->username != $data->nick) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You didn't win this game!");
			return;
		}
		$msg = str_replace("!winneraddress ","",$data->message);
		if ($msg == "") {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You didn't provide a Dogecoin address!");
			return;
		}
		if (!Dogecoin::checkAddress($msg)) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": That Dogecoin address isn't valid!");
			return;
		}
		$p = $this->game->winner;
		$amount = (int)substr($p->money,-2);
		global $doge;
		try {
			$doge->sendtoaddress($msg,$amount);
		} catch (Exception $e) {
			$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": An internal error occured trying to send your Dogecoins.. Unfortunately, you win nothing.. :(");
			$this->game = false;
			return;
		}
		// aaand kill the game finally
		$this->game = false;
		$irc->message(SMARTIRC_TYPE_CHANNEL,GAMECHANNEL,CTRL_B.$data->nick.CTRL_B.": You won ".DOGE.$amount."!");
		file_put_contents("/tmp/moon.info","");
	}
}

$bot = new monopoly();
$irc = new Net_SmartIRC();
$irc->setDebug(SMARTIRC_DEBUG_NONE);
//$irc->setUseSockets(true);
$irc->setChannelSyncing(true);
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!taketurn$/', $bot, "takeTurn");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!endturn$/', $bot, "endTurn");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!payfine$/', $bot, "payFine");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!usecard$/', $bot, "useCard");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!buy$/', $bot, "buyProperty");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!auction$/', $bot, "auctionProperty");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!bid\s/', $bot, "bidProperty");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!buildhouse/', $bot, "buildHouse");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!sellhouse/', $bot, "sellHouse");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!mortgage/', $bot, "mortgageProperty");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!unmortgage/', $bot, "unmortgageProperty");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!starttrade/', $bot, "startTrade");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!stoptrade$/', $bot, "stopTrade");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!addprop.*/', $bot, "addProp");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!delprop.*/', $bot, "delProp");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!addcash.*/', $bot, "addCash");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!delcash.*/', $bot, "delCash");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!addcard.*/', $bot, "addCard");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!delcard.*/', $bot, "delCard");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!dotrade$/', $bot, "doTrade");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!denytrade$/', $bot, "denyTrade");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!accepttrade$/', $bot, "acceptTrade");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!tradeinfo$/', $bot, "tradeInfo");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!gameinfo$/', $bot, "gameInfo");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!startgame$/', $bot, "startGame");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!stopgame\s/', $bot, "stopGame");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!joingame$/', $bot, "joinGame");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!bankrupt$/', $bot, "bankrupt");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!help$/', $bot, "help");
$irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '/^!winneraddress/', $bot, "winnerAddress");
$irc->registerActionHandler(SMARTIRC_TYPE_JOIN, '.*', $bot, "onJoin");
$irc->registerActionHandler(SMARTIRC_TYPE_PART, '.*', $bot, "onQuit");
$irc->registerActionHandler(SMARTIRC_TYPE_KICK, '.*', $bot, "onKick");
$irc->registerActionHandler(SMARTIRC_TYPE_QUIT, '.*', $bot, "onQuit");
$irc->registerActionHandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $bot, "onNickChange");
while(1) {
$irc->connect("localhost",7000,true);
$irc->login("moonopoly","The Dogecoin Property Trading Game",8,"tothemoon",'');
$irc->send("oper username password"); // oline
$irc->send("part #services :");
$irc->message(SMARTIRC_TYPE_QUERY,"nickserv","identify password");
$irc->join(array(GAMECHANNEL));
$irc->send("samode ".GAMECHANNEL." +Y moonopoly");
$irc->listen();
$irc->disconnect();
}
