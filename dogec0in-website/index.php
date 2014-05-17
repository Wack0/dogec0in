<?php
if (!isset($_GET['debugbug'])) error_reporting(0);
include("PasswordHash.php");
include("Dogecoin.php");
function randkey() {
	$rand = '';
	for ($i=1;$i<=32;$i++) $rand .= sprintf("%02x",mt_rand(0,255));
	return $rand;
}
function doMail($from,$subject,$body) {
	// This function was a quick hackjob, done near the end of dogec0in's life, so I could pass email sending to another box to stop verification and password reset emails going to spam.
	// There's no need for it now when open sourcing, so just pass the parameters to mail()...
	return mail($from,$subject,$body,"From: dogec0in <noreply@dogec0in.com>","-f noreply@dogec0in.com");
}
$sql = explode(":","localhost:user:pass:database");
$hasdb = false;
$error = array();
if ((array_key_exists('r',$_GET)) && ($_GET['r'] != "")) {
	define("SQUEEZE",1);
	session_start();
	$_SESSION['ref'] = (int)$_GET['r'];
	include("s.php");
	exit();
}
$de = array("0-mail.com","0815.ru","0clickemail.com","0wnd.net","0wnd.org","10minutemail.com","20minutemail.com","2prong.com","30minutemail.com","3d-painting.com","4warding.com","4warding.net","4warding.org","60minutemail.com","675hosting.com","675hosting.net","675hosting.org","6url.com","75hosting.com","75hosting.net","75hosting.org","7tags.com","9ox.net","a-bc.net","afrobacon.com","ajaxapp.net","amilegit.com","amiri.net","amiriindustries.com","anonbox.net","anonymbox.com","antichef.com","antichef.net","antispam.de","baxomale.ht.cx","beefmilk.com","binkmail.com","bio-muesli.net","bobmail.info","bodhi.lawlita.com","bofthew.com","brefmail.com","broadbandninja.com","bsnow.net","bugmenot.com","bumpymail.com","casualdx.com","centermail.com","centermail.net","chogmail.com","choicemail1.com","cool.fr.nf","correo.blogos.net","cosmorph.com","courriel.fr.nf","courrieltemporaire.com","cubiclink.com","curryworld.de","cust.in","dacoolest.com","dandikmail.com","dayrep.com","deadaddress.com","deadspam.com","despam.it","despammed.com","devnullmail.com","dfgh.net","digitalsanctuary.com","discardmail.com","discardmail.de","Disposableemailaddresses:emailmiser.com","disposableaddress.com","disposeamail.com","disposemail.com","dispostable.com","dm.w3internet.co.ukexample.com","dodgeit.com","dodgit.com","dodgit.org","donemail.ru","dontreg.com","dontsendmespam.de","dump-email.info","dumpandjunk.com","dumpmail.de","dumpyemail.com","e4ward.com","email60.com","emaildienst.de","emailias.com","emailigo.de","emailinfive.com","emailmiser.com","emailsensei.com","emailtemporario.com.br","emailto.de","emailwarden.com","emailx.at.hm","emailxfer.com","emz.net","enterto.com","ephemail.net","etranquil.com","etranquil.net","etranquil.org","explodemail.com","fakeinbox.com","fakeinformation.com","fastacura.com","fastchevy.com","fastchrysler.com","fastkawasaki.com","fastmazda.com","fastmitsubishi.com","fastnissan.com","fastsubaru.com","fastsuzuki.com","fasttoyota.com","fastyamaha.com","filzmail.com","fizmail.com","fr33mail.info","frapmail.com","front14.org","fux0ringduh.com","garliclife.com","get1mail.com","get2mail.fr","getonemail.com","getonemail.net","ghosttexter.de","girlsundertheinfluence.com","gishpuppy.com","gowikibooks.com","gowikicampus.com","gowikicars.com","gowikifilms.com","gowikigames.com","gowikimusic.com","gowikinetwork.com","gowikitravel.com","gowikitv.com","great-host.in","greensloth.com","gsrv.co.uk","guerillamail.biz","guerillamail.com","guerillamail.net","guerillamail.org","guerrillamail.biz","guerrillamail.com","guerrillamail.de","guerrillamail.net","guerrillamail.org","guerrillamailblock.com","h.mintemail.com","h8s.org","haltospam.com","hatespam.org","hidemail.de","hochsitze.com","hotpop.com","hulapla.de","ieatspam.eu","ieatspam.info","ihateyoualot.info","iheartspam.org","imails.info","inboxclean.com","inboxclean.org","incognitomail.com","incognitomail.net","incognitomail.org","insorg-mail.info","ipoo.org","irish2me.com","iwi.net","jetable.com","jetable.fr.nf","jetable.net","jetable.org","jnxjn.com","junk1e.com","kasmail.com","kaspop.com","keepmymail.com","killmail.com","killmail.net","kir.ch.tc","klassmaster.com","klassmaster.net","klzlk.com","kulturbetrieb.info","kurzepost.de","letthemeatspam.com","lhsdv.com","lifebyfood.com","link2mail.net","litedrop.com","lol.ovpn.to","lookugly.com","lopl.co.cc","lortemail.dk","lr78.com","m4ilweb.info","maboard.com","mail-temporaire.fr","mail.by","mail.mezimages.net","mail2rss.org","mail333.com","mail4trash.com","mailbidon.com","mailblocks.com","mailcatch.com","maileater.com","mailexpire.com","mailfreeonline.com","mailin8r.com","mailinater.com","mailinator.com","mailinator.net","mailinator2.com","mailincubator.com","mailme.ir","mailme.lv","mailmetrash.com","mailmoat.com","mailnator.com","mailnesia.com","mailnull.com","mailshell.com","mailsiphon.com","mailslite.com","mailzilla.com","mailzilla.org","mbx.cc","mega.zik.dj","meinspamschutz.de","meltmail.com","messagebeamer.de","mierdamail.com","mintemail.com","moburl.com","moncourrier.fr.nf","monemail.fr.nf","monmail.fr.nf","msa.minsmail.com","mt2009.com","mx0.wwwnew.eu","mycleaninbox.net","mypartyclip.de","myphantomemail.com","myspaceinc.com","myspaceinc.net","myspaceinc.org","myspacepimpedup.com","myspamless.com","mytrashmail.com","neomailbox.com","nepwk.com","nervmich.net","nervtmich.net","netmails.com","netmails.net","netzidiot.de","neverbox.com","no-spam.ws","nobulk.com","noclickemail.com","nogmailspam.info","nomail.xl.cx","nomail2me.com","nomorespamemails.com","nospam.ze.tc","nospam4.us","nospamfor.us","nospamthanks.info","notmailinator.com","nowmymail.com","nurfuerspam.de","nus.edu.sg","nwldx.com","objectmail.com","obobbo.com","oneoffemail.com","onewaymail.com","online.ms","oopi.org","ordinaryamerican.net","otherinbox.com","ourklips.com","outlawspam.com","ovpn.to","owlpic.com","pancakemail.com","pimpedupmyspace.com","pjjkp.com","politikerclub.de","poofy.org","pookmail.com","privacy.net","proxymail.eu","prtnx.com","punkass.com","PutThisInYourSpamDatabase.com","qq.com","quickinbox.com","rcpt.at","recode.me","recursor.net","regbypass.com","regbypass.comsafe-mail.net","rejectmail.com","rklips.com","rmqkr.net","rppkn.com","rtrtr.com","s0ny.net","safe-mail.net","safersignup.de","safetymail.info","safetypost.de","sandelf.de","saynotospams.com","selfdestructingmail.com","SendSpamHere.com","sharklasers.com","shiftmail.com","shitmail.me","shortmail.net","sibmail.com","skeefmail.com","slaskpost.se","slopsbox.com","smellfear.com","snakemail.com","sneakemail.com","sofimail.com","sofort-mail.de","sogetthis.com","soodonims.com","spam.la","spam.su","spamavert.com","spambob.com","spambob.net","spambob.org","spambog.com","spambog.de","spambog.ru","spambox.info","spambox.irishspringrealty.com","spambox.us","spamcannon.com","spamcannon.net","spamcero.com","spamcon.org","spamcorptastic.com","spamcowboy.com","spamcowboy.net","spamcowboy.org","spamday.com","spamex.com","spamfree24.com","spamfree24.de","spamfree24.eu","spamfree24.info","spamfree24.net","spamfree24.org","spamgourmet.com","spamgourmet.net","spamgourmet.org","SpamHereLots.com","SpamHerePlease.com","spamhole.com","spamify.com","spaminator.de","spamkill.info","spaml.com","spaml.de","spammotel.com","spamobox.com","spamoff.de","spamslicer.com","spamspot.com","spamthis.co.uk","spamthisplease.com","spamtrail.com","speed.1s.fr","supergreatmail.com","supermailer.jp","suremail.info","teewars.org","teleworm.com","tempalias.com","tempe-mail.com","tempemail.biz","tempemail.com","TempEMail.net","tempinbox.co.uk","tempinbox.com","tempmail.it","tempmail2.com","tempomail.fr","temporarily.de","temporarioemail.com.br","temporaryemail.net","temporaryforwarding.com","temporaryinbox.com","thanksnospam.info","thankyou2010.com","thisisnotmyrealemail.com","throwawayemailaddress.com","tilien.com","tmailinator.com","tradermail.info","trash-amil.com","trash-mail.at","trash-mail.com","trash-mail.de","trash2009.com","trashemail.de","trashmail.at","trashmail.com","trashmail.de","trashmail.me","trashmail.net","trashmail.org","trashmail.ws","trashmailer.com","trashymail.com","trashymail.net","trillianpro.com","turual.com","twinmail.de","tyldd.com","uggsrock.com","upliftnow.com","uplipht.com","venompen.com","veryrealemail.com","viditag.com","viewcastmedia.com","viewcastmedia.net","viewcastmedia.org","webm4il.info","wegwerfadresse.de","wegwerfemail.de","wegwerfmail.de","wegwerfmail.net","wegwerfmail.org","wetrainbayarea.com","wetrainbayarea.org","wh4f.org","whyspam.me","willselfdestruct.com","winemaven.info","wronghead.com","wuzup.net","wuzupmail.net","www.e4ward.com","www.gishpuppy.com","www.mailinator.com","wwwnew.eu","xagloo.com","xemaps.com","xents.com","xmaily.com","xoxy.net","yep.it","yogamaven.com","yopmail.com","yopmail.fr","yopmail.net","ypmail.webarnak.fr.eu.org","yuurok.com","zehnminutenmail.de","zippymail.info","zoaxe.com","zoemail.org","33mail.com","maildrop.cc","inboxalias.com","spam4.me","koszmail.pl","tagyourself.com","whatpaas.com","drdrb.com","mailismagic.com","gustr.com","einrot.com","fleckens.hu","jourrapide.com","rhyta.com","superrito.com","teleworm.us","drdrb.net","grr.la","spamgoes.in","monumentmail.com","mailismagic.com","mailtothis.com","sogetthis.com");
if (array_key_exists('rs',$_GET)) {
	if ((!isset($_POST['e'])) || ($_POST['e'] == ""))
		die("Please enter your email.");
	if (!strstr(str_replace('@','',strstr($_POST['e'],'@')),'.'))
		die("The entered email is not valid.");
	$domain = str_replace('@','',strstr($_POST['e'],'@'));
	$isDispos = false;
	foreach ($de as $dedom)
		if(strstr($domain,$dedom) != false) $isDispos = true;
	if ($isDispos)
		die("The entered email is a disposable email.");
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	if ($s->query("select email from users where email='".$s->real_escape_string($_POST['e'])."'")->num_rows != 0)
		die("Someone has already registered with that email.");
	if ($error == array()) {
		// no errors, let's go!
		$p = new PasswordHash(8,false);
		$id = randkey();
		while ($s->query("select key from users where key='".$s->real_escape_string($id)."'")->num_rows != 0)
			$id = randkey();
		$username = explode("@",$_POST['e']);
		$username = $username[0];
		foreach (array('!','\$','&','*','-','=','^','`','|','~','#','%',"'",'+','/','?','{','}','.') as $char)
			$username = str_replace($char,"_",$username);
		$username = explode(" ",$username);
		$username = $username[0];
		if (is_numeric(substr($username,0,1))) $username = "|".$username;
		$uc = $s->query("select count(*) as count from users where username like '".$s->real_escape_string($username."%")."'")->fetch_object()->count;
		if ($uc > 0) $username .= $uc;
		// generate new password..
		$ps = '';
		for ($i = 0; $i < 10; $i++) {
			$nextchar = substr(sha1(microtime().(mt_rand(0,1)?microtime():time())),0,1);
			if (mt_rand(0,1)) $nextchar = strtoupper($nextchar);
			$ps .= $nextchar;
		}
		$s->query("insert into users (username,password,email,address,webkey,verified) values ('".$s->real_escape_string($username)."','".$s->real_escape_string($p->HashPassword($ps))."','".$s->real_escape_string($_POST['e'])."','','".$s->real_escape_string($id)."',0)");
		// now generate the tipbot address.
		include("jsonRPCClient.php");
		$dogetip = new jsonRPCClient("https://username:password@127.0.0.1:22557/");
		$id = $s->insert_id;
		if (!$id) $id = $s->query("select id from users where username='".$s->real_escape_string($username)."'")->fetch_object()->id;
		if (!$id) die("Something failed. Go back and try again.");
		try {
			$tipaddress = $dogetip->getaccountaddress("dogec0in_".$id);
		} catch (Exception $e) {
			$tipaddress = '';
		}
		$s->query("update users set address='".$s->real_escape_string($tipaddress)."' where id=".$id);
		session_start();
		$rid = $_SESSION['ref'];
		if ($rid == "") $rid = 0;
		$s->query("insert into referrals (uid,rid) values (".$id.",".$rid.")");
		$vu = "http://".$_SERVER['HTTP_HOST']."/?a=v&v=".urlencode($id."|".sha1('dogec0inisthebestwaterbowlever!'.$username.$_POST['e'].$tipaddress));
		doMail($_POST['u']." <".$_POST['e'].">","[dogec0in] Account Verification",$username.",\n\nYou have (or someone using your email address has) just registered at dogec0in.\nTo verify your registration and start earning free Dogecoins by chatting, visit this link: ".$vu."\n\nYour username is: ".$username."\nYour password is: ".$ps."\n\nIf you did not intend to receive this email, just ignore it and nothing will happen.\nEnjoy dogec0in!");
		?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>dogec0in: the original chat waterbowl for Dogecoin</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <style>
	body {margin-top: 60px;}
    </style>

  </head>

  <body>
  <div class="container">
	<div class="col-lg-4">
        <div class="alert alert-dismissable alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
              You have received an account verification email, that includes your username and password. Please check your emails at <?php echo $_POST['e']; ?>. Remember to also check your spam folder!
            </div>
        </div>
		<div class="row">
        <div class="col-lg-12">
			<h1>Welcome to dogec0in - the chat waterbowl!</h1>
			<p>Now you can get money just for chatting in our chatrooms! But before you rush to get your verification email, you might want to read this first.</p>
			<p>When you enter the chatroom, some helpful people will end up directing you to read the rules. These are very important, as not following them means you might get banned, and you will no longer earn money.<br>
			When you earn money, you'll get a special message, a "notice". The money you earn is in <b>Dogecoins, the friendly internet money</b>. Right now, &#208;1000 is roughly equal to $1.<br>
			The money you earn goes to your tipbot account on dogec0in. You can tip people with it by typing in the chat <b>!tip username amount</b> - where username is a person's chat username and amount is the amount you want to give them.<br>
			You can see how much money you have in your tipbot account by typing <b>/msg wowsuchtips info</b> - don't worry if it's a little hard to remember, helpful <b>shibes</b> (members of the Dogecoin community) in the chat can remind you!<br>
			And finally, you can withdraw your money to somewhere else by typing <b>/msg wowsuchtips withdraw address amount</b> - where address is the Dogecoin address you wish to withdraw it to, and amount is the amount of Dogecoins you wish to send (you can send all of them by using <b>all</b> in place of the amount)</p>
			<p>You might want to secure your dogecoins. Right now, anyone with access to your account will be able to withdraw your dogecoins. You will probably want to download a wallet program like <a href="http://www.wowdoge.org/">WowDoge</a> (available for Windows, Mac and Linux), and then modify your settings in the dogec0in website to change your payment address to your WowDoge wallet.</p>
			<p>And yes, you can exchange your dogecoins for US dollars or your local currency; for that, other help, and Dogecoin-related news, you'll want to subscribe to <a href="http://www.reddit.com/r/dogecoin">the Dogecoin subreddit on Reddit</a>.</p>
			<p>If you want even more dogecoins, start advertising using your referral link, you'll get 10% of whatever your downline earns! Your referral link is below.</p>
			<p>Welcome, new shibe, and happy dogecoining! Be sure to donate back to the dogec0in waterbowl if you like it, by buying ads or VIP, or just out of the goodness of your heart!</p>
			<p>Your personal referral link: <input type="text" size="40" value="http://dogec0in.com/?r=<?php echo $id; ?>"></p>
    </div>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

  </body>
</html><?php
		die();
	}
}
if (!isset($_POST['a'])) $_POST['a'] = '';
if ($_POST['a'] == 'l') {
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	$r = $s->query("select id,password from users where verified=1 and username='".$s->real_escape_string($_POST['u'])."'");
	$p = new PasswordHash(8,false);
	if (($r === false) || (!$r->num_rows))
		$error[] = "Username or password incorrect.";
	elseif ($r->num_rows == 0)
		$error[] = "Username or password incorrect.";
	elseif (strlen($_POST['p']) > 72)
		$error[] = "Username or password incorrect.";
	else {
		// get the results
		$res = $r->fetch_object();
		// is password correct?
		if (!$p->CheckPassword($_POST['p'],$res->password))
			$error[] = "Username or password incorrect.";
		else {
			// password correct, is this user b&?
			$r = $s->query("select reason from bans where uid=".((int)$res->id));
			$res = $r->fetch_object();
			if (!$r->num_rows) {
				// nope
				session_start();
				$_SESSION['l'] = $_POST['u'].":".$p->HashPassword($_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT']);
				die("<script type=\"text/javascript\">window.location = \"http://".$_SERVER['HTTP_HOST']."/\"</script>");
			} else {
				$error[] = "You are banned. ".$res->reason;
				$_GET['a'] = '';
			}
		}
	}
} elseif ($_POST['a'] == 'r') {
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	// check ALL the things!
	if ((!isset($_POST['u'])) || ($_POST['u'] == ""))
		$error[] = "Please enter a username.";
	if (!preg_match("/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{2,30}\z/i",$_POST['u']))
		$error[] = "That username is not valid. Valid usernames are between two and thirty characters in length, and can contain letters, numbers and the following characters (the first character cannot be a number): [ ] { } ^ ` \ | _ -";
	if ($s->query("select username from users where username='".$s->real_escape_string($_POST['u'])."'")->num_rows != 0)
		$error[] = "Someone has already registered with that username.";
	if ((!isset($_POST['e'])) || ($_POST['e'] == ""))
		$error[] = "Please enter an email.";
	if ((!isset($_POST['e2'])) || ($_POST['e2'] == ""))
		$error[] = "Please confirm your email.";
	if ($_POST['e'] != $_POST['e2'])
		$error[] = "The entered emails do not match.";
	if (!strstr(str_replace('@','',strstr($_POST['e'],'@')),'.'))
		$error[] = "The entered email is not valid.";
	$domain = str_replace('@','',strstr($_POST['e'],'@'));
	$isDispos = false;
	foreach ($de as $dedom)
		if(strstr($domain,$dedom) != false) $isDispos = true;
	if ($isDispos)
		$error[] = "The entered email is a disposable email.";
	if ($s->query("select email from users where email='".$s->real_escape_string($_POST['e'])."'")->num_rows != 0)
		$error[] = "Someone has already registered with that email.";
	if ((!isset($_POST['p'])) || ($_POST['p'] == ""))
		$error[] = "Please enter a password.";
	if ((!isset($_POST['p2'])) || ($_POST['p2'] == ""))
		$error[] = "Please confirm your password.";
	if ($_POST['p'] != $_POST['p2'])
		$error[] = "The entered passwords do not match.";
	if (strlen($_POST['p']) > 72)
		$error[] = "The entered password is too long.";
	if ((!isset($_POST['d'])) || ($_POST['d'] == ""))
		$error[] = "Please enter a Dogecoin address.";
	if (!Dogecoin::checkAddress($_POST['d']))
		$error[] = "The entered Dogecoin address is not valid.";
	if ($s->query("select address from users where address='".$s->real_escape_string($_POST['d'])."'")->num_rows != 0)
		$error[] = "Someone has already registered with that Dogecoin address.";
	// -- checked ALL the things! --
	if ($error == array()) {
		// no errors, let's go!
		$p = new PasswordHash(8,false);
		$id = randkey();
		while ($s->query("select key from users where key='".$s->real_escape_string($id)."'")->num_rows != 0)
			$id = randkey();
		$s->query("insert into users (username,password,email,address,webkey,verified) values ('".$s->real_escape_string($_POST['u'])."','".$s->real_escape_string($p->HashPassword($_POST['p']))."','".$s->real_escape_string($_POST['e'])."','".$s->real_escape_string($_POST['d'])."','".$s->real_escape_string($id)."',0)");
		$vu = "http://".$_SERVER['HTTP_HOST']."/?a=v&v=".urlencode($s->insert_id."|".sha1('dogec0inisthebestwaterbowlever!'.$_POST['u'].$_POST['e'].$_POST['d']));
		doMail($_POST['u']." <".$_POST['e'].">","[dogec0in] Account Verification",$_POST['u'].",\n\nYou have (or someone using your email address has) just registered at dogec0in.\nTo verify your registration and start earning free Dogecoins by chatting, visit this link: ".$vu."\nIf you did not intend to receive this email, just ignore it and nothing will happen.\nEnjoy dogec0in!");
		$error[] = "You have received an account verification email. Please check your emails at ".$_POST['e'].". Remember to also check your spam folder!";
	} else {
		$_POST['a'] = '';
		$_GET['a'] = 'r';
	}
} elseif ($_REQUEST['a'] == 'f') {
	if ((isset($_GET['v'])) && ($_GET['v'] != "")) {
		session_start();
		if ((array_key_exists("v",$_SESSION)) && ($_SESSION['v'] != "")) {
			$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
			$hasdb = true;
			$v = explode("|",urldecode($_GET['v']));
			if (count($v) != 2)
				$error[] = "Invalid verification key.";
			else {
				$v[0] = (int)$v[0];
				$r = $s->query("select username,email,address,password,webkey from users where verified=1 and id=".$v[0]);
				if (($r === false) || (!$r->num_rows)) $error[] = "Invalid verification key.";
				else {
					$d = $r->fetch_object();
					if (($v[1] == sha1('dogec0inisthebestwaterbowlever!'.$_SESSION['v'].$d->username.$d->email.$d->address.$d->password.$d->key)) || ($v[1] == sha1('dogec0inisthebestwaterbowlever!'.$d->username.$d->email.$d->address.$d->password.$d->key))) {
						// generate new password..
						$p = '';
						for ($i = 0; $i < 10; $i++) {
							$nextchar = substr(sha1(microtime().(mt_rand(0,1)?microtime():time())),0,1);
							if (mt_rand(0,1)) $nextchar = strtoupper($nextchar);
							$p .= $nextchar;
						}
						$ph = new PasswordHash(8,false);
						$s->query("update users set password='".$s->real_escape_string($ph->HashPassword($p))."' where id=".$v[0]);
						// email the user!
						doMail($d->username." <".$d->email.">","[dogec0in] New Password",$d->username.",\n\nYour password reset at dogec0in was successful.\nYour password has been reset to: ".$p."\nYou can now log into dogec0in using this password, and change your password to something that you find easier to remember if you want, or just continue chatting!\nIf you did not intend to receive this email, then there has been a security breach and you must contact dogec0in staff as soon as possible.\nEnjoy dogec0in!");
						$error[] = "Your password has been reset. Your new password has been emailed to you at ".$d->email." - remember to also check your spam folder!";
						$_SESSION['v'] = "";
					} else $error[] = "Invalid verification key.";
				}
			}
		}
	} else {
		if ((!isset($_POST['e'])) || ($_POST['e'] == "")) {
			$error[] = "Please enter your registered email.";
			$_POST['a'] = '';
			$_GET['a'] = 'l';
			$_GET['f'] = 1;
		} else {
			$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
			$hasdb = true;
			$r = $s->query("select id,username,email,address,password,webkey from users where verified=1 and email='".$s->real_escape_string($_POST['e'])."'");
			if (($r === false) || (!$r->num_rows)) {
				$error[] = "Please enter your registered email.";
				$_POST['a'] = '';
				$_GET['a'] = 'l';
				$_GET['f'] = 1;
			} else {
				session_start();
				$p = '';
				for ($i = 0; $i < 10; $i++) {
					$nextchar = substr(sha1(microtime().(mt_rand(0,1)?microtime():time())),0,1);
					if (mt_rand(0,1)) $nextchar = strtoupper($nextchar);
					$p .= $nextchar;
				}
				$_SESSION['v'] = sha1($p);
				$d = $r->fetch_object();
				$vu = "http://".$_SERVER['HTTP_HOST']."/?a=f&v=".urlencode($d->id."|".sha1("dogec0inisthebestwaterbowlever!".$d->username.$d->email.$d->address.$d->password.$d->key));
				// and mail the user!
				doMail($d->username." <".$_POST['e'].">","[dogec0in] Password Reset",$d->username.",\n\nYou have (or someone using your email address has) requested a password reset at dogec0in.\nTo reset your password, click the link below and a new password will be emailed to you.\nIf you did not intend to receive this email, just ignore it and nothing will happen.\nEnjoy dogec0in!\n\nPassword Reset Link: ".$vu);
				$error[] = "A password reset link has been emailed to you at ".$_POST['e']." - remember to also check your spam folder!";
			}
		}
	}
} elseif ($_POST['a'] == 'd') {
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	$p = new PasswordHash(8,false);
	session_start();
	if ($_SESSION['l'] != "") {
		$ses = explode(":",$_SESSION['l']);
		if (count($ses) != 2) {
			$ses = explode("|",$_SESSION['l']);
			if (count($ses) != 2) {
				$_SESSION['l'] = "";
			}
		}
		if (!$p->CheckPassword($_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT'],$ses[1])) $_SESSION['l'] = "";
		if ($_SESSION['l'] == "") $ses = array();
	} else $ses = array();
	$r = $s->query("select address,password from users where username='".$s->real_escape_string($ses[0])."'");
	if (($r === false) || (!$r->num_rows)) {
		$_SESSION['l'] = "";
		$ses = "";
		die("<script type=\"text/javascript\">window.location = \"http://".$_SERVER['HTTP_HOST']."/\"</script>");
	}
	$res = $r->fetch_object();
	if (!$p->CheckPassword($_POST['p'],$res->password))
		$error[] = "Password incorrect.";
	else {
		if ($res->address != $_POST['d']) {
			if (!Dogecoin::checkAddress($_POST['d']))
				$error[] = "The entered Dogecoin address is not valid.";
			else {
				$s->query("update users set address='".$s->real_escape_string($_POST['d'])."' where username='".$s->real_escape_string($ses[0])."'");
				$error[] = "Dogecoin address updated.";
				$_GET['a'] = '';
			}
		}
		if ((isset($_POST['np'])) && ($_POST['np'] != "")) {
			if ($_POST['np'] == $_POST['p'])
				$error[] = "New password is the same as the old one!";
			else {
				$s->query("update users set password='".$s->real_escape_string($p->HashPassword($_POST['np']))."' where username='".$s->real_escape_string($ses[0])."'");
				$error[] = "Password changed.";
				$_GET['a'] = '';
			}
		}
	}
} elseif (isset($_GET['a']) && $_GET['a'] == 'v') {
	// doing this here so the page loads all at once.
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	$p = new PasswordHash(8,false);
	$v = explode("|",str_replace("%7c","|",urldecode($_GET['v'])));
	if (count($v) != 2) {
		$error[] = "Invalid verification key.";
	} else {
		$v[0] = (int)$v[0];
		$r = $s->query("select username,email,address from users where verified=0 and id=".$v[0]);
		if (($r === false) || (!$r->num_rows)) $error[] = "Invalid verification key. You may have already verified your registration.";
		else {
			$d = $r->fetch_object();
			if (($v[1] != sha1('dogec0inisthebestwaterbowlever!'.$d->username.$d->email.$d->address)) && (!$p->CheckPassword('dogec0inisthebestwaterbowlever!'.$d->username.$d->email.$d->address,$v[1])) && (!$p->CheckPassword('dogec0inisthebestwaterbowlever!'.$d->username.$d->email.$d->address,$v[1]."."))) $error[] = "Invalid verification key.";
			else {
				$s->query("update users set verified=1 where id=".$v[0]);
				session_start();
				$_SESSION['l'] = $d->username.":".$p->HashPassword($_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT']);
				$error[] = "You have now verified your registration. Welcome to dogec0in, ".$d->username."!";
			}
		}
	}
	$_GET['a'] = '';
} elseif (isset($_GET['a']) && $_GET['a'] == 'o') {
	session_start();
	$_SESSION['l'] = "";
	$error[] = "You are now logged out.";
	$_GET['a'] = '';
} elseif ((isset($_GET['a'])) && ($_GET['a'] == 'c')) {
	// lightirc config
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	$p = new PasswordHash(8,false);
	session_start();
	$ses = explode(":",$_SESSION['l']);
	$ret = new stdclass();
	if (count($ses) != 2) {
		$ses = explode("|",$_SESSION['l']);
		if (count($ses) != 2) {
			$ret->loggedin = false;
			die(json_encode($ret));
		}
	}
	if (!$p->CheckPassword($_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT'],$ses[1])) {
		$_SESSION['l'] = '';
		$ret->loggedin = false;
		die(json_encode($ret));
	}
	$r = $s->query("select id,webkey from users where username='".$s->real_escape_string($ses[0])."'");
	if ($r === false) {
		$_SESSION['l'] = '';
		$ret->loggedin = false;
		die(json_encode($ret));
	}
	if (!$r->num_rows) {
		$_SESSION['l'] = '';
		$ret->loggedin = false;
		die(json_encode($ret));
	}
	$res = $r->fetch_object();
	$key = $res->webkey;
	if ($key == "") {
		$_SESSION['l'] = '';
		$ret->loggedin = false;
		die(json_encode($ret));
	}
	$id = $res->id;
	$ret->loggedin = true;
	$r = $s->query("select id from voice where uid=".$id." and time > ".time());
	if ($r->num_rows) $vip = true;
	else $vip = false;
	$ret->vip = $vip;
	// get working IP
	/*$dnsr = dns_get_record("irc.ringoflightning.net",DNS_A);
	$workingips = array();
	ini_set("default_socket_timeout",1);
	foreach ($dnsr as $record) {
		$conn = @fsockopen($record['ip'], 843);
		if (is_resource($conn)) {
			fclose($conn);
			$workingips[] = $record['ip'];
		}
	}*/
	$ret->nick = $ses[0];
	$ret->key = $key;
	die(json_encode($ret));
	header('Content-Type: application/javascript');
	?>
var params = {};

params.host                         = "irc.ringoflightning.net";
params.port                         = 6667;
params.policyPort                   = 843;

params.language                     = "en";
params.styleURL                     = "css/black.css";
params.nick                         = "<?php echo $ses[0]; ?>";
params.realname                     = "dogec0in";
params.quitMessage                  = "http://dogec0in.com/ chat waterbowl";
params.perform                      = "/join #dogec0in,/msg wowsuchdoge identify <?php echo $key; ?>";

<?php if ($vip) {
	?>params.emoticonList = ":)->ukc_smile.png,:D->ukc_grin.png,:content->ukc_content.png,:P->ukc_tongue.png,:content->ukc_content.png,:|->ukc_straight.png,:O->ukc_shocked.png,:'(->ukc_cry.png,:inlove->ukc_inlove.png,:whistle->ukc_whistle.png,:sly->ukc_sly.png,;)->ukc_wink.png,:(->ukc_sad.png,:blowkiss->ukc_blowkiss.png,:@->ukc_angry.png,:confused->ukc_confused.png,:cool->ukc_cool.png,:devil->ukc_devil.png,:dizzy->ukc_dizzy.png,:erm->ukc_erm.png,:green->ukc_green.png,:sick->ukc_sick.png,:silly->ukc_silly.png,:waa->ukc_waa.png,:woohoo->ukc_w00t.png,(AWE)->ukc_awe.png,:alien->ukc_alien.png,:angel->ukc_angel.png,:beer->ukc_beer.png,:bike->ukc_bike.png,:book->ukc_brokenheart.png,:bullseye->ukc_bullseye.png,:burger->ukc_burger.png,:cake->ukc_cake.png,:camping->ukc_camping.png,:cheers->ukc_cheers.png,:chicken->ukc_chicken.png,:chips->ukc_chips.png,:chocolates->ukc_chocolates.png,:clap->ukc_clap.png,:clover->ukc_clover.png,:cocktail->ukc_cocktail.png,:coffee->ukc_coffee.png,:couple->ukc_couple.png,:cow->ukc_cow.png,:crown->ukc_crown.png,:diamond->ukc_diamond.png,:dog->ukc_dog.png,:egg->ukc_egg.png,:eyes->ukc_eyes.png,:embarrassed->ukc_embarrassed.png,:fart->ukc_fart.png,:flowers->ukc_flowers.png,:frog->ukc_frog.png,:ghost->ukc_ghost.png,:gift->ukc_gift.png,:guitar->ukc_guitar.png,:heart->ukc_heart.png,:heartarrow->ukc_heartarrow.png,:lips->ukc_lips.png,:massage->ukc_massage.png,:moon->ukc_moon.png,:music->ukc_music.png,:nails->ukc_nails.png,:ok->ukc_ok.png,:party->ukc_party.png,:peace->ukc_penguin.png,:phone->ukc_phone.png,:pig->ukc_pig.png,:pinkheart->ukc_pinkheart.png,:poo->ukc_poo.png,:pray->ukc_pray.png,:pumpkin->ukc_pumpkin.png,:rainbow->ukc_rainbow.png,:ribbon->ukc_ribbon.png,:ring->ukc_ring.png,:rocket->ukc_rocket.png,:rose1->ukc_rose1.png,:rose->ukc_rose.png,:santa->ukc_santa.png,:skull->ukc_skull.png,:snowman->ukc_snowman.png,:spaghetti->ukc_spaghetti.png,:squid->ukc_squid.png,:star->ukc_star.png,:sun->ukc_sun.png,:television->ukc_television.png,:thumbsdown->ukc_thumbsdown.png,:thumbsup->ukc_thumbsup.png,:ukflag->ukc_ukflag.png,:zzz->ukc_zzz.png";<?php
} ?>
params.showRichTextControlsBackgroundColor = false;
params.showServerWindow             = false;
params.showNickSelection            = false;
params.showIdentifySelection        = false;
params.showRegisterNicknameButton   = false;
params.showRegisterChannelButton    = false;
params.showNewQueriesInBackground   = false;
params.showNavigation = true;
params.navigationPosition           = "bottom";
params.loopServerCommands           = true;
params.loopClientCommands			= true;

params.showChannelCentral = "false";
params.showMenuButton = "false";
params.showChannelHeader = "true";

params.customSecurityErrorMessage = "Flash policy problem. Make sure you have the latest version of Flash Player installed. If you do, there may be a server issue, go tell slipstream about it!";

function joinchan(channel) {
	sendCommand("/join #" + channel);
}
function sendCommand(command) {
  swfobject.getObjectById('lightIRC').sendCommand(command);
}
function sendMessageToActiveWindow(message) {
  swfobject.getObjectById('lightIRC').sendMessageToActiveWindow(message);
}
function setTextInputContent(content) {
  swfobject.getObjectById('lightIRC').setTextInputContent(content);
}
function onChatAreaClick(nick, ident, realname, channel, host) {
}
function onContextMenuSelect(type, nick, ident, realname, channel, host) {
}
function onServerCommand(command) {
  <?php if ($vip) {
  ?>if ((command.search("NOTICE") != -1) && (command.search(":wowsuchdoge!manyshibe@") != -1)) {
	if (command.search("Much identified! Such gifts, many channel messages!") != -1) sendCommand("JOIN #dogec0in-vip");
  }<?php } ?>
  var res = command.split(" ");
  if (res[1] == "PRIVMSG") {
	if (res[2].substr(1,1) == "#") return null;
  }
  if ((command.search("NOTICE #dogec0in :"+String.fromCharCode(62)+"Global Notice"+String.fromCharCode(60)+" ") != -1) && (command.search(":wowsuchdoge!manyshibe@") != -1)) {
	setTimeout(alert,100,res.slice(5).join(" "));
	return null;
  }
  return command;
}
function onClientCommand(command) {
  var res = command.split(" ");
  if (res[0] == "PRIVMSG") {
    if (res[1].substr(1,1) == "#") return null;
  }
  return command;
}
window.onbeforeunload = function() {
  swfobject.getObjectById('lightIRC').sendQuit();
}
for(var key in params) {
  params[key] = params[key].toString().replace(/%/g, "%25");
}<?php die();
}
// is the user logged in?
session_start();
if ($_SESSION['l'] != "") {
	$ses = explode(":",$_SESSION['l']);
	$p = new PasswordHash(8,false);
	if (count($ses) != 2) {
		$ses = explode("|",$_SESSION['l']);
		if (count($ses) != 2) {
			$_SESSION['l'] = "";
		}
	}
	if (!$p->CheckPassword($_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_USER_AGENT'],$ses[1])) $_SESSION['l'] = "";
	if ($_SESSION['l'] == "") $ses = array();
} else $ses = array();
if ($ses != array()) {
	$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
	$hasdb = true;
	$id = $s->query("select id from users where username='".$s->real_escape_string($ses[0])."'")->fetch_object()->id;
	if (!$id) $ses = array();
}
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>dogec0in: the original chat waterbowl for Dogecoin</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <style>
	body {margin-top: 60px;}
    </style>

  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/">dogec0in</a>
        </div>
        <div class="collapse navbar-collapse navbar-ex1-collapse">
          <ul class="nav navbar-nav">
			<?php if ($ses == array()) { ?>
            <li><a href="?a=l">login</a></li>
            <li><a href="?a=r">register</a></li>
			<?php } else { ?>
			<li><a href="?a=d">settings</a></li>
			<li><a href="?a=o">logout (<?php echo $ses[0]; ?>)</a></li>
			<?php } ?>
          </ul>
        </div><!-- /.navbar-collapse -->
      </div><!-- /.container -->
    </nav>

    <div class="container">
<?php if ($error != array()) {
		  ?><div class="col-lg-4">
            <div class="alert alert-dismissable alert-danger">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <?php foreach ($error as $e) echo($e."<br>"); ?>
            </div>
          </div>
		  <?php
			}?>
      <div class="row">
        <div class="col-lg-12">
		  <p><?php include("head.php"); ?></p>
		  <?php if ((!isset($_GET['a'])) || ($_GET['a'] == '')) {
		  if ($ses == array()) {?>
		  <h1>dogec0in: the original chat waterbowl for &#240;ogecoin</h1>
          <p>register/login, chat, earn dogecoin. it's that simple.</p>
          <p><b>DUE TO A SECURITY ISSUE (5-May-2014), YOU MUST RESET YOUR PASSWORD BEFORE YOU CAN LOGIN, USE THE FORGOT PASSWORD LINK</b></p><?php }
		  else {?><h1>logged into dogec0in as <?php echo $ses[0]; ?></h1>
		  <p>get 10% of the dogecoins your referrals earn! your personal referral link is: <input type="text" size="40" value="http://dogec0in.com/?r=<?php echo $id; ?>"></p>
		  <p>to connect via any irc client: irc.dogec0in.com #dogec0in <b>(not working properly right now, but the webchat can be accessed via mobile now)</b></p>
		  <script src="http://codef.santo.fr/codef/codef_music.js"></script>
		  <script>var player = new music("MK");
		  player.LoadAndRun("wotw105.mod");
		  var playing = true;
		  function ToggleMusic() {
		    if (typeof playing == 'undefined') return;
			if (playing == true) {
				player.loader.player.stop();
				playing = false;
			}
			else {
				player.loader.player.play();
				playing = true;
			}
		  }
                function joinchan(channel) {
                  document.getElementById('ircframe').contentWindow.kiwi.components.ControlInput().run('/join #'+channel);
                }
		  setTimeout(ToggleMusic,1000);</script>
		  <input type="button" value="Toggle Music!" onclick="ToggleMusic();">
                <input type="button" style="width: 150px" value="Gambling Channel" onclick="joinchan('dogec0in-gamble')">
                <input type="button" style="width: 150px" value="Tip Fun Channel" onclick="joinchan('dogec0in-tip')">
                <input type="button" style="width: 150px" value="Trading Channel" onclick="joinchan('dogec0in-trade')">
                <input type="button" style="width: 150px" value="Moonopoly Channel" onclick="joinchan('dogec0in-moonopoly')">
		  <iframe id="ircframe" src="/kiwi/?<?php echo mt_rand(10000,10000000)."=".mt_rand(10000,10000000); ?>" style="border:0; width:100%; height:500px;" /><?php }
		  } elseif ($_GET['a'] == 'r') {
		  ?>
            <div class="well">
              <form class="bs-example form-horizontal" method="POST">
			    <input type="hidden" name="a" value="r">
                <fieldset>
                  <legend>register</legend>
				  <div class="form-group">
                    <label for="u" class="col-lg-2 control-label">username</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="u" name="u" placeholder="username">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="e" class="col-lg-2 control-label">email</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="e" name="e" placeholder="email">
                    </div>
                  </div>
				  <div class="form-group">
                    <label for="e2" class="col-lg-2 control-label">confirm email</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="e2" name="e2" placeholder="confirm email">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="p" class="col-lg-2 control-label">password</label>
                    <div class="col-lg-10">
                      <input type="password" class="form-control" id="p" name="p" placeholder="password">
				    </div>
				  </div>
				  <div class="form-group">
                    <label for="p2" class="col-lg-2 control-label">confirm password</label>
                    <div class="col-lg-10">
                      <input type="password" class="form-control" id="p2" name="p2" placeholder="confirm password">
				    </div>
				  </div>
				  <div class="form-group">
                    <label for="d" class="col-lg-2 control-label">dogecoin address</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="d" name="d" placeholder="dogecoin address">
				    </div>
				  </div>
				  <div class="form-group">
                    <div class="col-lg-10 col-lg-offset-2">
                      <button type="submit" class="btn btn-primary">submit</button> 
                    </div>
                  </div>
				</fieldset>
			  </form>
			</div><?php } elseif ($_GET['a'] == 'l') {
				if (array_key_exists('f',$_GET)) { ?>
			<div class="well">
				<form class="bs-example form-horizontal" method="POST">
			    <input type="hidden" name="a" value="f">
				<fieldset>
                  <legend>forgot password</legend>
				  <div class="form-group">
                    <label for="e" class="col-lg-2 control-label">email</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="e" name="e" placeholder="email">
                    </div>
                  </div>
				  <div class="form-group">
                    <div class="col-lg-10 col-lg-offset-2">
                      <button type="submit" class="btn btn-primary">submit</button>
                    </div>
                  </div>
				</fieldset>
			  </form>
			</div><?php } else { ?>
            <div class="well">
              <form class="bs-example form-horizontal" method="POST">
			    <input type="hidden" name="a" value="l">
                <fieldset>
                  <legend>login</legend>
				  <div class="form-group">
                    <label for="u" class="col-lg-2 control-label">username</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="u" name="u" placeholder="username">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="p" class="col-lg-2 control-label">password <a href="?a=l&f">forgot?</a></label>
                    <div class="col-lg-10">
                      <input type="password" class="form-control" id="p" name="p" placeholder="password">
				    </div>
				  </div>
				  <div class="form-group">
                    <div class="col-lg-10 col-lg-offset-2">
                      <button type="submit" class="btn btn-primary">submit</button>
                    </div>
                  </div>
				</fieldset>
			  </form>
			</div><?php }
			} elseif (($ses != array()) && ($_GET['a'] == 'd')) {
				$s = new mysqli($sql[0],$sql[1],$sql[2],$sql[3]);
				$hasdb = true;
				$r = $s->query("select address from users where username='".$s->real_escape_string($ses[0])."'");
				if ($r->num_rows != 1) {
					// something's fucked up! kill the session and redirect to homepage
					$_SESSION['l'] = '';
					echo("<script type=\"text/javascript\">window.location = \"http://".$_SERVER['HTTP_HOST']."/\"</script>");
				} else {
					$addy = $r->fetch_object()->address; ?>
			<div class="well">
              <form class="bs-example form-horizontal" method="POST">
			    <input type="hidden" name="a" value="d">
                <fieldset>
                  <legend>settings</legend>
				  <div class="form-group">
                    <label for="d" class="col-lg-2 control-label">dogecoin address</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="d" name="d" placeholder="dogecoin address" value="<?php echo $addy; ?>">
                    </div>
                  </div>
				  <div class="form-group">
                    <label for="np" class="col-lg-2 control-label">new password</label>
                    <div class="col-lg-10">
                      <input type="password" class="form-control" id="np" name="np" placeholder="password">
				    </div>
				  </div>
                  <div class="form-group">
                    <label for="p" class="col-lg-2 control-label">current password (required to change settings)</label>
                    <div class="col-lg-10">
                      <input type="password" class="form-control" id="p" name="p" placeholder="password" required>
				    </div>
				  </div>
				  <div class="form-group">
                    <div class="col-lg-10 col-lg-offset-2">
                      <button type="submit" class="btn btn-primary">submit</button> 
                    </div>
                  </div>
				</fieldset>
			  </form>
			</div><?php }
			} else {
		  if ($ses == array()) {?>
		  <h1>dogec0in: the original chat waterbowl for &#240;ogecoin</h1>
          <p>register/login, chat, earn dogecoin. it's that simple.</p>
          <p><b>DUE TO A SECURITY ISSUE (5-May-2014), YOU MUST RESET YOUR PASSWORD BEFORE YOU CAN LOGIN, USE THE FORGOT PASSWORD LINK</b></p><?php }
		  else {?><h1>logged into dogec0in as <?php echo $ses[0]; ?></h1>
		  <p>get 10% of the dogecoins your referrals earn! your personal referral link is: <input type="text" size="40" value="http://dogec0in.com/?r=<?php echo $id; ?>"></p>
		  <p>to connect via any irc client: irc.dogec0in.com #dogec0in <b>(not working properly right now, but the webchat can be accessed via mobile now)</b></p>
		  <script src="http://codef.santo.fr/codef/codef_music.js"></script>
		  <script>var player = new music("MK");
		  player.LoadAndRun("wotw105.mod");
		  var playing = true;
		  function ToggleMusic() {
		    if (typeof playing == 'undefined') return;
			if (playing == true) {
				player.loader.player.stop();
				playing = false;
			}
			else {
				player.loader.player.play();
				playing = true;
			}
		  }
                function joinchan(channel) {
                  document.getElementById('ircframe').contentWindow.kiwi.components.ControlInput().run('/join #'+channel);
                }
		  setTimeout(ToggleMusic,1000);</script>
		  <input type="button" value="Toggle Music!" onclick="ToggleMusic();">
                <input type="button" style="width: 150px" value="Gambling Channel" onclick="joinchan('dogec0in-gamble')">
                <input type="button" style="width: 150px" value="Tip Fun Channel" onclick="joinchan('dogec0in-tip')">
                <input type="button" style="width: 150px" value="Trading Channel" onclick="joinchan('dogec0in-trade')">
                <input type="button" style="width: 150px" value="Moonopoly Channel" onclick="joinchan('dogec0in-moonopoly')">
		  <iframe id="ircframe" src="/kiwi/?<?php echo mt_rand(10000,10000000)."=".mt_rand(10000,10000000); ?>" style="border:0; width:100%; height:500px;" /><?php } } ?>

    </div>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

  </body>
</html>