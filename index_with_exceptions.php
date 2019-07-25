<?php
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');
include("settings.php");
include("functions.php");
date_default_timezone_set(USER_TIMEZONE);

//RESET WAR
if ((isset($_GET["reset"]) && ($_GET["reset"] == 1))||(isset($_GET["new"]) && ($_GET["new"] == 1))){
    unlink("names.json");
	unlink("exceptions.json");
	unlink("log.txt");
	unlink("failed.txt");
	array_map('unlink', glob("images/*"));
	rmdir("images");
	if ($_GET["reset"] == 1){
		die("La batalla ha sido reiniciada, si quieres inicializarla haz clic <a href='index.php?new=1'>aquí</a>, si quieres empezarla haz clic <a href='index.php'>aquí</a>");
	}
}

//GETTING NAMES
if (file_exists("names.json")){ 
    $names = json_decode(file_get_contents("names.json"),true);
	if (count($names)==1){
		die("La batalla ha terminado, si quieres reiniciarla elimina el archivo names.json o haz clic <a href='index.php?reset=1'>aquí</a>");
	}
}else{ //First execution
    include("names.php");
	if (isset($_GET["new"]) && ($_GET["new"] == 1)){
		$image = getimage();
		if ($image != ""){
			$string = "Va a dar comienzo la batalla entre empresas de transporte o seguimiento de @MiTrackingBot.\n\nHay algunas sorpresas preparadas pero recordad que es un juego/parodia.\n\nLas compañías enfrentadas son:";
			//SEND TO TWITTER
			if ((API_KEY != "")&&(API_SECRET_KEY != "")){
				sendTwitter($string,$image);
			}
			//SEND TO TELEGRAM
			if ((TELEGRAM_TOKEN != "")&&(CHANNEL_ID != "")){
				sendTelegram($string,$image);
			}
		}
		die("La batalla ha sido inicializada, si quieres empezarla haz clic <a href='index.php'>aquí</a>");
	}else{
		include("exceptions.php");
		savejsonexceptions($excuses);
	}
}

//BATTLE
$killer = $names[mt_rand(0, count($names) - 1)];
$victim = $names[mt_rand(0, count($names) - 1)];
if ($killer == $victim){ //making sure no one kills itself
    while($killer == $victim){ 
        $victim = $names[mt_rand(0, count($names) - 1)];
    }
}
$killer_key = array_search($killer,$names);
$victim_key = array_search($victim,$names);
$killer_twitter = $killer[key($killer)];
$victim_twitter = $victim[key($victim)];
file_put_contents("log.txt",date("Y-m-d H:i")." - ".count($names)." - ".key($killer)." - ".key($victim)."\n", FILE_APPEND);

//MESSAGE
if ($killer_twitter!=""){
    $killer_twitter = " (@".$killer_twitter.")";
}
if ($victim_twitter!=""){
    $victim_twitter = " (@".$victim_twitter.")";
}
$string = key($killer).$killer_twitter." ha eliminado a ".key($victim).$victim_twitter."\n\n";

/* EXCEPTIONS */
$exception = 0;
$exceptions = json_decode(file_get_contents("exceptions.json"),true);
if ((array_key_exists(key($killer),$exceptions))||(array_key_exists(key($victim),$exceptions))&&(count($names>2))){
    if (array_key_exists(key($killer),$exceptions)){
        $string = sprintf($exceptions[key($killer)],key($killer).$killer_twitter,key($victim).$victim_twitter)."\n\n";
        unset($exceptions[key($killer)]);
    }else{
        $string = sprintf($exceptions[key($victim)],key($killer).$killer_twitter,key($victim).$victim_twitter)."\n\n";
        unset($exceptions[key($victim)]);
    }
    savejsonexceptions($exceptions);
    $exception = 1;
    if ($string == ""){
        file_put_contents("failed.txt",date("Y-m-d H:i")." ".key($killer)." - ".key($victim), FILE_APPEND);
    }
}

if ($exception != 1){
    //REMOVE FROM ARRAY
    unset($names[$victim_key]);

    //SAVE UPDATED JSON
    savejson($names);
}

if (count($names)<2){
    $string .= "Todos los enemigos han sido eliminados.\n".key($killer).$killer_twitter." es el ganador\n\n";    
}else{
    $string .= count($names)." compañías restantes.\n\n";
}
$string .= HASHTAG;

//SAVE IMAGE
$image = getimage();
$success = 0;
if ($image != ""){
    //SEND TO TWITTER
    if ((API_KEY != "")&&(API_SECRET_KEY != "")){
        if (sendTwitter($string,$image)){
            $success = 1;
        }else{
            $success = 0;
        }
    }

    //SEND TO TELEGRAM
    if ((TELEGRAM_TOKEN != "")&&(CHANNEL_ID != "")){
        if (sendTelegram($string,$image)){
            $success = 1;
        }else{
            $success = 0;
        }
    }
}
if ($image == "" || $success == 0){ //SOMETHING HAS FAILED
    file_put_contents("failed.txt",date("Y-m-d H:i")." ".$string, FILE_APPEND);
    //ADD AGAIN TO ARRAY
    $names[] = array(key($victim) => $victim[key($victim)]);

    //SAVE UPDATED JSON
    savejson($names);
}
?>