<?php

$game = file_get_contents("/tmp/moon.info");
if ($game != "") $game = json_decode($game);

function spaceMetadata($space) {
	playersOn($space);
	playerOwns($space);
	numHouses($space);
}

function playersOn($space) {
	global $game;
	if (!is_object($game)) return;
	foreach ($game->players as $p)
		if ($p->space == $space) {
			?><br /><i><?php echo $p->username; ?></i><?php
		}
}

function playerOwns($space) {
	global $game;
	if (!is_object($game)) return;
	if (!array_key_exists($space,$game->spaces)) return;
	if (!property_exists($game->spaces[$space],"owner")) return;
	if ($game->spaces[$space]->owner == "") return;
	?><br /><b>Owned by: <?php echo $game->spaces[$space]->owner->username;
		if ($game->spaces[$space]->mortgaged)
			echo " <font color=\"red\">[MORTGAGED]</font>";
	?></b><?php
}

function numHouses($space) {
	global $game;
	if (!is_object($game)) return;
	if (!array_key_exists($space,$game->spaces)) return;
	if (!property_exists($game->spaces[$space],"houses")) return;
	if ($game->spaces[$space]->houses == 0) return;
	?><br /><u>Has <?php echo ($game->spaces[$space]->houses == 5?"a hotel":$game->spaces[$space]->houses." house".($game->spaces[$space]->houses > 1?"s":"")); ?></u><?php
}

function gameBoard() {
	global $game;
	?><table border="3" cellspacing="0" cellpadding="3" style="text-align:center;font:normal 8pt/9pt 'Comic Sans MS', cursive, sans-serif;width:400px;border-collapse:separate; background-color:#F0FFF0;">
<tr>
<td style="height:60px;">Doge Walking
<div style="width:60px;height:0px;"></div><?php spaceMetadata(20); ?>
</td>
<td style="border-bottom:solid 12px Red">Doges.org<br />
Ð220<?php spaceMetadata(21); ?></td>
<td style="">Much Chance
<div style="font:bold 16pt times new roman,serif;color:#1e55d5;">?</div><?php spaceMetadata(22); ?>
</td>
<td style="border-bottom:solid 12px Red">Bitcointalk<br />
Ð220<?php spaceMetadata(23); ?></td>
<td style="border-bottom:solid 12px Red">Doge Road<br />
Ð240<?php spaceMetadata(24); ?></td>
<td style="">Ranger<br />
Ð200<?php spaceMetadata(25); ?></td>
<td style="border-bottom:solid 12px Yellow">Amazon EC2<br />
Ð260<?php spaceMetadata(26); ?></td>
<td style="border-bottom:solid 12px Yellow">Windows Azure<br />
Ð260<?php spaceMetadata(27); ?></td>
<td style=""><?php
	if (is_object($game)) echo $game->spaces[28]->name;
	else {
		$utilstwo = array("CGMiner","CUDAMiner","Minerd");
		echo $utilstwo[array_rand($utilstwo)];
	}
?><br />
Ð150<?php spaceMetadata(28); ?></td>
<td style="border-bottom:solid 12px Yellow">Dogecoin Foundation<br />
Ð280<?php spaceMetadata(29); ?></td>
<td>Found Orphaned Block
<div style="width:60px;height:0px;"></div><?php // no need for space metadata here, whoever lands on this is in jail anyway. ?>
</td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px Orange">Doge Dice<br />
Ð200<?php spaceMetadata(19); ?></td>
<td rowspan="9" colspan="9" align="center">
<table cellpadding="0" cellspacing="2" style="border:solid 2px #ff0000;">
<tr>
<td>
<div style="background-color:#ff0000;color:#ffffff;font:normal 22px 'Comic Sans MS', cursive, sans-serif;letter-spacing:+5px;padding:1px 12px 1px 12px;">MOONOPOLY</div>
</td>
</tr>
</table>
</td>
<td style="border-left:solid 12px Green">Dogechain<br />
Ð300<?php spaceMetadata(31); ?></td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px Orange">Doge Wheel<br />
Ð180<?php spaceMetadata(18); ?></td>
<td style="border-left:solid 12px Green">/u/dogetipbot<br />
Ð300<?php spaceMetadata(32); ?></td>
</tr>
<tr>
<td style="height:34px;">Comoonity Chest<?php spaceMetadata(17); ?></td>
<td style="">Comoonity Chest<?php spaceMetadata(33); ?></td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px Orange">/r/dogebetting<br />
Ð180<?php spaceMetadata(16); ?></td>
<td style="border-left:solid 12px Green">Dogec0in<br />
Ð320<?php spaceMetadata(34); ?></td>
</tr>
<tr>
<td style="height:34px;">Luna<br />
Ð200<?php spaceMetadata(15); ?></td>
<td style="">Surveyor<br />
Ð200<?php spaceMetadata(35); ?></td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px DeepPink">/r/dogecoinbeg<br />
Ð160<?php spaceMetadata(14); ?></td>
<td style="">Very Chance
<div style="font:bold 16pt times new roman,serif;color:#c00;">?</div><?php spaceMetadata(36); ?>
</td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px DeepPink">freenode #dogebeg<br />
Ð140<?php spaceMetadata(13); ?></td>
<td style="border-left:solid 12px Blue">/r/dogecoin<br />
Ð350<?php spaceMetadata(37); ?></td>
</tr>
<tr>
<td style="height:34px;"><?php
	if (is_object($game)) echo $game->spaces[12]->name;
	else {
		$utilsone = array("Dogehouse","Dogecoinpool","Netcodepool","Rapidhash","Teamdoge");
		echo $utilsone[array_rand($utilsone)];
	}
?><br />
Ð150<?php spaceMetadata(12); ?></td>
<td style="">Trading Fees<br />
(pay Ð100)<?php spaceMetadata(38); ?></td>
</tr>
<tr>
<td style="height:34px;border-right:solid 12px DeepPink">RoL #dogec0in-tip<br />
Ð140<?php spaceMetadata(11); ?></td>
<td style="border-left:solid 12px Blue">The Moon<br />
Ð400<?php spaceMetadata(39); ?></td>
</tr>
<tr>
<td style="height:60px;">Orphaned Block/Just Visiting<?php spaceMetadata(10); ?></td>
<td style="border-top:solid 12px SkyBlue">SaveDogemas<br />
Ð120<?php spaceMetadata(9); ?></td>
<td style="border-top:solid 12px SkyBlue">/r/dogecoinpif<br />
Ð100<?php spaceMetadata(8); ?></td>
<td style="">Such Chance
<div style="font:bold 16pt times new roman,serif;color:#c00;">?</div>
<?php spaceMetadata(7); ?></td>
<td style="border-top:solid 12px SkyBlue">/r/dogecoincharity<br />
Ð100<?php spaceMetadata(6); ?></td>
<td style="">Apollo<br />
Ð200<?php spaceMetadata(5); ?></td>
<td style="">Pool Fees<br />
(pay Ð200)<?php spaceMetadata(4); ?></td>
<td style="border-top:solid 12px SaddleBrown">9gag<br />
Ð60<?php spaceMetadata(3); ?></td>
<td style="">Comoonity Chest<?php spaceMetadata(2); ?></td>
<td style="border-top:solid 12px SaddleBrown">4chan<br />
Ð60<?php spaceMetadata(1); ?></td>
<td>Collect Ð200 salary as you pass<br />
<b>LAUNCHPAD</b><br />
<img alt="Go Arrow" src="goarrow.png" width="44" height="8" /><?php spaceMetadata(0); ?></td>
</tr>
</table><?php
}

function playerInfo() {
	global $game;
	if (!is_object($game)) return;
	$i = 1;
	$msgs = array();
	foreach ($game->players as $p) {
		$msgs[] = "Player ".$i.": <b>".$p->username."</b>";
		if ($p->jailturn !== false) $msgs[] = "  Found Orphaned Block";
		$msgs[] = "  Money: Ð".$p->money;
		if ($p->cardoutofjail) $msgs[] = "  Has a Recovered from Orphaned Block Free card.";
		$i++;
	}
	echo "Number of players: ".($i - 1)."<br />";
	echo implode("<br />",$msgs)."<br />";
}

function tradeInfo() {
	global $game;
	if (!is_object($game)) return;
	if (!is_object($game->trading)) return;
	$msgs = array();
	$msgs[0] = "This trade is between ".$game->trading->trader->username." and ".$game->trading->tradewith->username.".";
	$key = 1;
	if ($game->trading->giveproperties != array()) {
		$msgs[$key] = "Giving properties:";
		foreach($game->trading->giveproperties as $p)
			$msgs[$key] .= " ".$p->getNameIRC();
		$key++;
	}
	if ($game->trading->givemoney > 0) {
		$msgs[$key] = "Giving ".DOGE.$game->trading->givemoney;
		$key++;
	}
	if ($game->trading->givecard) {
		$msgs[$key] = "Giving a Recover from Orphaned Block card.";
		$key++;
	}
	if ($game->trading->takeproperties != array()) {
		$msgs[$key] = "Taking properties:";
		foreach($game->trading->takeproperties as $p)
			$msgs[$key] .= " ".$p->getNameIRC();
		$key++;
	}
	if ($game->trading->takemoney > 0) {
		$msgs[$key] = "Taking ".DOGE.$game->trading->takemoney;
		$key++;
	}
	if ($game->trading->takecard) {
		$msgs[$key] = "Taking a Recover from Orphaned Block card.";
		$key++;
	}
	echo implode("<br />",$msgs)."<br />";
}

?><html><head><title>Moonopoly - Game <?php if (!is_object($game)) echo "Not "; ?>Started</title></head><body style="font-family:'Comic Sans MS', cursive, sans-serif;" <?php if (is_object($game)) echo 'onLoad="setTimeout(function(){window.location = '."'".'http://moonopoly.dogec0in.com/'."'".'}, 15000)"'; ?>><?php
gameBoard();
playerInfo();
?><br />
Moonopoly is essentially a version of <a href="http://en.wikipedia.org/wiki/Monopoly_(game)">Monopoly</a> played on <a href="http://dogec0in.com">dogec0in chat</a> where the game board and cards are Dogecoin related, you play with <b>fake</b> Dogecoins (Ð), and the winner gets <b>real</b> Dogecoins.<br />
To start a game, join the Moonopoly channel, <b>#dogec0in-moonopoly</b> and type <b>!startgame</b> - the new game will be announced in the main channel and others can join it by typing <b>!joingame</b>.<br />
<br />
On your turn, you'll be given a list of triggers you can use.<br />
These triggers are: <b>!taketurn !buildhouse !sellhouse !mortgage !unmortgage !starttrade !bankrupt</b><br />
<b>!startturn</b> is the trigger you use to roll the dice. At the end of your turn you won't be able to use this trigger; instead you'll use <b>!endturn</b> to end your turn and give control to the next player.<br />
<b>!buildhouse</b> lets you build a house on a property you have the entire colour set of. It takes one argument: the name of the property you want to build a house on. The name of the property is case sensitive and 
must be the <b>exact</b> name of the property as used in the game, including spaces. As in the original Monopoly, you must even build houses.<br />
<b>!sellhouse</b> removes a house on a property you have at least one house on. It takes the same argument as !buildhouse.</br>
<b>!mortgage</b> lets you mortgage a property. As in the original Monopoly, you gain money for mortgaging a property, you cannot mortgage a property with houses, and anyone who lands on a mortgaged property does not pay rent.
It takes the same argument as !buildhouse and !sellhouse. <br />
<b>!unmortgage</b> is how you unmortgage a mortgaged property (if you have enough money to). It takes the same argument as !buildhouse, !sellhouse and !mortgage.<br />
<b>!starttrade</b> is how you start a trade; trading is covered below.<br />
<b>!bankrupt</b> takes you out of the game; your properties will be put up for sale.<br /><br />

Trading is essentially the same as Monopoly. To start a trade, use <b>!starttrade</b> on your turn.<br />
When you start a trade, you'll be shown a list of triggers that you can use to add and remove the assets you wish to give or take. Properties without houses, money, and Recover From Orphaned Block Free cards can all be traded.<br />
Once you have completed your trade preparations, use the <b>!dotrade</b> trigger, and the person you are trading with can accept or deny the trade.<br />
If you decide you do not wish to trade, type <b>!stoptrade</b> - after you have proposed your trade using !dotrade, you cannot stop it.

</body></html>