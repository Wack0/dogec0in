<?php
// wowsuchdoge cronjob.

$running = `ps ux | grep cronjob | grep -v sh | grep -v grep | wc -l`;
if ($running > 1) die();

chdir($_SERVER['HOME']);
if (file_exists("dogeinfo")) unlink("dogeinfo");

$balance = exec("./dogecoind getbalance");
$given = exec("./dogecoind getreceivedbyaddress DCE55iF3wTpAZjqdtddSvaaA2PgixJjSUG");

file_put_contents("dogeinfo","ok|".$balance."|".$given);
