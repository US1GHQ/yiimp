<?php

require('misc.php');
echo <<<END

<!doctype html>
<!--[if IE 7 ]>		 <html class="no-js ie ie7 lte7 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 8 ]>		 <html class="no-js ie ie8 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 9 ]>		 <html class="no-js ie ie9 lte9>" lang="en-US"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html class="no-js" lang="en-US"> <!--<![endif]-->

<head>

<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
<meta name="description" content="yet another anonymous mining pool for bitcoin and altcoin with auto profit switch and auto exchange">
<meta name="keywords" content="anonymous,mining,pool,maxcoin,bitcoin,altcoin,auto,switch,exchange,profit,scrypt,x11,x13,x14,x15,lyra2,lyra2re,neoscrypt,sha256,quark">

<title>yiimp</title>

END;

echo CHtml::cssFile("/extensions/jquery/themes/ui-lightness/jquery-ui.css");
echo CHtml::cssFile('/yaamp/ui/css/main.css');
echo CHtml::cssFile('/yaamp/ui/css/table.css');

echo CHtml::scriptFile('/extensions/jquery/js/jquery-1.8.3-dev.js');
echo CHtml::scriptFile('/extensions/jquery/js/jquery-ui-1.9.1.custom.min.js');
echo CHtml::scriptFile('/yaamp/ui/js/jquery.tablesorter.js');

// if(!controller()->admin)
// echo <<<end
// <script>
// (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
// (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
// m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
// })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

// ga('create', 'UA-58136019-1', 'auto');
// ga('send', 'pageview');

// $(document).ajaxSuccess(function(){ga('send', 'pageview');});

// </script>
// end;

echo "</head>";

///////////////////////////////////////////////////////////////

echo "<body class='page'>";
echo "<a href='/site/mainbtc' style='display: none;'>main</a>";

showPageHeader();
showPageContent($content);
showPageFooter();

echo "</body></html>";
return;

/////////////////////////////////////////////////////////////////////

function showItemHeader($selected, $url, $name)
{
	if($selected) $selected_text = "class='selected'";
	else $selected_text = '';
	
	echo "<span><a $selected_text href='$url'>$name</a></span>";
	echo "&nbsp;";
}

function showPageHeader()
{
	echo "<div class='tabmenu-out'>";
	echo "<div class='tabmenu-inner'>";
	
//	echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='/'>Yet Another Anonymous Mining Pool</a>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	
	$action = controller()->action->id;
	$wallet = user()->getState('yaamp-wallet');
	$ad = isset($_GET['address']);

	showItemHeader(controller()->id=='site' && $action=='index' && !$ad, '/', 'Home');
	showItemHeader($action=='mining', '/site/mining', 'Pool');
	showItemHeader(controller()->id=='site'&&($action=='index' || $action=='wallet') && $ad, "/?address=$wallet", 'Wallet');
	showItemHeader(controller()->id=='stats', '/stats', 'Graphs');
	showItemHeader($action=='miners', '/site/miners', 'Miners');
	showItemHeader(controller()->id=='renting', '/renting', 'Rental');
	
	if(controller()->admin)
	{
//		debuglog("admin {$_SERVER['REMOTE_ADDR']}");
//		$algo = user()->getState('yaamp-algo');
				
		showItemHeader(controller()->id=='explorer', '/explorer', 'Explorers');
//		showItemHeader(controller()->id=='coin', '/coin', 'Coins');
		showItemHeader($action=='common', '/site/common', 'Admin');
		showItemHeader(controller()->id=='site'&&$action=='admin', "/site/admin", 'List');
//		showItemHeader(controller()->id=='renting' && $action=='admin', '/renting/admin', 'Jobs');
		
//		showItemHeader(controller()->id=='trading', '/trading', 'Trading');
//		showItemHeader(controller()->id=='nicehash', '/nicehash', 'Nicehash');
	}
	
	echo "<span style='float: right;'>";
	
	$mining = getdbosql('db_mining');
	$nextpayment = date('H:i', $mining->last_payout+YAAMP_PAYMENTS_FREQ);
	
	echo "<span style='font-size: .8em;'>Next Payout: $nextpayment EUST</span>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&copy; yiimp.ccminer.org</span>";
	
	echo "</div>";
	echo "</div>";
}

function showPageFooter()
{
	echo "<div class='footer'>";
	$year = date("Y", time());
	
	echo "<p>&copy; $year. All Rights Reserved. yiimp -
		<a href='/site/terms'>Terms and conditions</a></p>";

	echo "</div><!-- footer -->";
}


