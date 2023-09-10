<!DOCTYPE html>
<html lang="en"> 
<head>

<title>Dolan pls! - Ylilauta</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link rel="shortcut icon" href="http://ylilauta.org/favicon.png" type="image/png" />
<link rel="stylesheet" type="text/css" href="http://ylilauta.org/css/ylilauta.css" />

<style type="text/css">
#RecaptchaWidget { margin: auto; }
.small { font-size: 10px; font-style: oblique; }
.color, #RecaptchaButtonContainer li { background-color: #800000 !important; }
</style>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(["_setAccount", "UA-12345678-9"]);
  _gaq.push(["_setDomainName", "ylilauta.org"]);
  _gaq.push(["_trackPageview"]);

  (function() {
	var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
	ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
	var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<body class="center">

<div id="front">
	<p><img src="img/dolan.png" alt="Paskapuhetta enkä usko t. aku ankka" /></p>
	<div id="title" class="bottommargin">
		<h1>Ylilauta</h1>
		<h2>Dolan pls!</h2>
	</div>
	<?php // FINNISH ?>
	<?php if( isset($_GET['basic']) OR isset($_GET['advanced']) ) : ?>
	<p>Erittäin kehittynyt automaattinen valvontajärjestelmämme havaitsi, että sinua seurataan KRP:n toimesta.</p>	
	<?php elseif( isset($_GET['country']) ) : ?>
	<p>IP-osoitteesi kuuluu maahan, alueeseen tai verkkoon joka on ylläpidon toimesta mustalistattu haitallisen toiminnan ehkäisemiseksi.</p>
	<p>Olet luultavasti porkkana tai jopa tomaatti. Ihminen tuskin olet. Suosittelemme että aloitat dieetin.</p>
	<?php endif; ?>
	<p>Todistaaksesi ettet ole droidi jota etsimme, täytä ja lähetä allaoleva CAPTCHA.</p>
	<p class="small">Tämä rajoitus poistuu automaattisesti kun verkostasi ei enää havaita haitallista toimintaa.</p>
	
	<br />
	<?php // ENGLISH ?>
	<?php if( isset($_GET['basic']) OR isset($_GET['advanced']) ) : ?>
	<p>
		Our highly sophisticated, automated surveillance system has noticed that you're being traced by the Finnish Keskusrikospoliisi.
	</p>	
	<?php elseif( isset($_GET['country']) ) : ?>
	<p>
		Your IP-address belongs to a country, area or a network which has been blacklisted by the administration to prevent malicious actions.
		We think that you might be a carrot or even a tomato. Human you hardly are. We recommend that you start a diet.
	<?php endif; ?>
	<p>To prove that you're not the droid we're looking for, please fill and submit the CAPTCHA below.</p>
	<p class="small">This restriction will automatically go away when no more harmful behavior is detected from your network.</p>
	
	<br />
	::CAPTCHA_BOX::
	<p class="small">Kuumotushuomautus: Toimiasi tarkkaillaan.</p>
	<p class="small">Heatenings notice: Your actions are being monitored.</p>
</div>
</body>
</html>
