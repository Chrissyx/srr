<?php
#######################################################################
# Script written by Chrissyx (chris@chrissyx.com)                     #
# Exclusive for CnC Foren (http://www.cncforen.de/)                   #
# http://www.chrissyx.de.vu/                                          #
#######################################################################

# Tiberium Wars Replay Reader (TWRR)
# Version: 0.9.2 (2008-03-08)
# Thanks to...
#           ...Quicksilver for GRR source code and inspiration
#           ...MerlinSt for helpful hints and code fragments
#           ...Lepidosteus for PHP Replay Parser code and algorithms


#
# Gibt die Partei eines Spielers anhand des gegebenen Wertes zurück.
# (Returns the faction of a player by the given value.)
#
function getFaction($faction)
{
 switch ($faction)
 {
  case -1:                      //Menschlicher Spieler mit zufälliger Partei (Human player with random faction)
  case 1: return "Zufall";      //CPU Spieler mit zufälliger Partei (AI player with random faction)
  case 2: return "Zuschauer";   //(Observer)
  case 3: return "Kommentator"; //(Commentator)
  case 6: return "GDI";
  case 7: return "Nod";
  case 8: return "Scrin";
  default: return "<b>UNBEKANNT!</b>";
 }
}

#
# Gibt die Farbe eines Spielers anhand des gegebenen Wertes zurück.
# (Returns the color of a player by a given value.)
#
function getColor($color)
{
 switch ($color)
 {
  case -1: return "Zufall";   //Keine oder zufällige Farbe (No color or random one)
  case 0: return "Blau";      //(Blue)
  case 1: return "Gelb";      //(Yellow)
  case 2: return "Grün";      //(Green)
  case 3: return "Orange";    //(Orange)
  case 4: return "Rosa";      //(Pink)
  case 5: return "Lila";      //(Purple)
  case 6: return "Rot";       //(Red)
  case 7: return "Hellblau";  //(Light blue)
  default: return "<b>UNBEKANNT!</b>";
 }
}

#
# Parst den INI String des Replays und gibt die Werte zurück.
# (Parse the INI string from the replay and returns the values.)
#
function parseINIString($ini)
{
 //String kürzen und auflösen (Trim and split string)
 $ini = explode(";", substr($ini, 3, strlen($ini)-4));                   //print_r($ini); //debug
 //Array map format:
 #0 -> mapfilename
 #1 -> MC
 #2 -> MS
 #3 -> SD => Seed?
 #4 -> GSID
 #5 -> GT
 #6 -> PC => Player Counter? Post Commentator? //$pc = substr($ini[6], 3);
 #7 -> RU => Rules (Starting cash, game speed, random crates, etc.)
 #8 -> S => All participants, seperated by ":"
 $iniarray['mapfilename'] = $ini[0];
 //Spielregeln verarbeiten (Process rules)
 $rules = explode(" ", trim($ini[7]));                                   //print_r($rules); //debug
 //Rules format:
  #0 -> Game type: 1=offline, 2=?, 3=unranked, 4=1v1, 5=2v2, 6=1v1 clan, 7=2v2 clan (4-7 all ranked)
  #1 -> Game speed (max 100)
  #2 -> Starting cash
  #3 -> BattleCasted?
  #4 -> VoIP?
  #5 -> BattleCast delay, minutes
  #6 -> Random crates
  #7 -> unknown number?
  #8 -> unknown number? -1
  #9 -> unknown number? -1
 #10 -> unknown number? -1
 #11 -> unknown number? -1
 #12 -> unknown number? -1
 //Spielart bestimmen (Get game type)
 switch (substr($rules[0], 3))
 {
  case 1: $iniarray['gametype'] = "Offline"; break;
  //case 2: TODO! (unknown, maybe offline LAN games?)
  case 3: $iniarray['gametype'] = "Online, Unranked"; break;
  case 4: $iniarray['gametype'] = "Online, Ranked, 1 vs. 1"; break;
  case 5: $iniarray['gametype'] = "Online, Ranked, 2 vs. 2"; break;
  case 6: $iniarray['gametype'] = "Online, Clan, 1 vs. 1"; break;
  case 7: $iniarray['gametype'] = "Online, Clan, 2 vs. 2"; break;
  default: $iniarray['gametype'] = "<b>UNBEKANNT!</b>"; break;
 }
 //Spielgeschwindigkeit auslesen (Extract game speed)
 $iniarray['gamespeed'] = $rules[1] . "%";
 //Startgeld auslesen (Extract starting cash)
 $iniarray['startcash'] = $rules[2] . "\$";
 //BattleCast bestimmen (Detect BattleCast)
 $iniarray['bc'] = ($rules[3] == "1") ? "Ja" : "Nein";
 //VoIP
 $iniarray['voip'] = ($rules[4] == "1") ? "Ja" : "Nein";
 //BattleCast Aufnahmeverzögerung (BattleCast delay)
 $iniarray['delay'] = $rules[5] . " Minuten";
 //Zufallskisten bestimmen (Detect random crates)
 $iniarray['crates'] = ($rules[6] == "0") ? "Nein" : "Ja";
 //Weiter mit INI Eintrag 8 (Proceed with INI entry 8)
 $ini = explode(":", substr($ini[8], 2, strlen($ini[8])-3));
 //Jeden Spieler verarbeiten (Process each player)
 for ($i=1; $i<9; $i++) //8 iterations needed!
 {
  //Spielerinfos ermitteln (Extract player infos)
  $player = explode(",", $ini[$i-1]);                                      //print_r($player); //debug
  //"Kommentator-Leiche" überspringen (Skip "Commentator-corpse")
  if ($player[0] == "Hpost Commentator") continue;
  // ----- H = Human -----
  if (strstr(substr($player[0], 0, 1), "H"))
  {
   //Human player format:
    #0 -> Tag + Player name
    #1 -> UID: IP in hex, 0=offline game
    #2 -> unknown number?
    #3 -> Match type (FT=Automatch, TT=Custom match)
    #4 -> Color: -1=random
    #5 -> Faction: 6=GDI, 7=Nod, 8=Scrin, -1=random
    #6 -> Map position
    #7 -> Team number
    #8 -> Handicap?
    #9 -> unknown number?
   #10 -> unknown number?
   #11 -> Clan tag
   //Spielertyp bestimmen (Get player type)
   switch ($player[5])
   {
    case 2: $playerarray[$i]['playertype'] = "Zuschauer"; break;
    case 3: $playerarray[$i]['playertype'] = "Kommentator"; break;
    default: $playerarray[$i]['playertype'] = "Spieler"; break;
   }
   //Spielername extrahieren (Extract player name)
   $playerarray[$i]['playername'] = substr($player[0], 1, strlen($player[0])-1);
   //IP Adresse bestimmen (Get IP adress)
   if ($player[1] > 0)
   {
    for ($j=0; $j<8; $j+=2) $playerarray[$i]['ip'] .= hexdec(substr($player[1], $j, 2)) . ".";
    $playerarray[$i]['ip'] = substr($playerarray[$i]['ip'], 0, -1);
   }
   //Spieltyp sichern für später (Save match type for later use)
   $ft = ($player[3] == "FT") ? true : false;
   //Farbe bestimmen (Get color)
   $playerarray[$i]['color'] = getColor($player[4]);
   //Partei bestimmen (Get faction)
   $playerarray[$i]['faction'] = getFaction($player[5]);
   //Spielposition bestimmen (Get map position)
   $playerarray[$i]['mappos'] = ($player[6] == "-1") ? "Zufällig" : ($player[6]+1);
   //Team bestimmen (Get team)
   $playerarray[$i]['team'] = ($player[7] != "-1") ? ($player[7]+1) : "-";
   //Handicap
   $playerarray[$i]['handicap'] = (($player[8] == "-1") ? "0" : $player[8]) . "%";
   //Clan tag
   $playerarray[$i]['clan'] = $player[11];
  }
  // ----- C = Computer -----
  elseif (strstr($player[0], "C"))
  {
   //CPU player format:
   #0 -> Difficulty: CE = easy, CM = medium, CH = hard, CB = brutal
   #1 -> Color
   #2 -> Faction: 6=GDI, 7=Nod, 8=Scrin, 1=random
   #3 -> Map position
   #4 -> Team number
   #5 -> Handicap
   #6 -> AI type
   $playerarray[$i]['playertype'] = "Computer";
   //Schwierigkeit bestimmen (Get difficulty)
   switch ($player[0])
   {
    case "CE": $playerarray[$i]['diff'] = "Leicht"; break;
    case "CM": $playerarray[$i]['diff'] = "Mittel"; break;
    case "CH": $playerarray[$i]['diff'] = "Schwer"; break;
    case "CB": $playerarray[$i]['diff'] = "Brutal"; break;
    default: $playerarray[$i]['diff'] = "<b>UNBEKANNT!</b>"; break;
   }
   //Farbe bestimmen (Get color)
   $playerarray[$i]['color'] = getColor($player[1]);
   //Partei bestimmen (Get faction)
   $playerarray[$i]['faction'] = getFaction($player[2]);
   //Spielposition bestimmen (Get map position)
   $playerarray[$i]['mappos'] = ($player[3] == "-1") ? "Zufällig" : ($player[3]+1);
   //Team bestimmen (Get team)
   $playerarray[$i]['team'] = ($player[4] != -1) ? ($player[4]+1) : "-";
   //Handicap
   $playerarray[$i]['handicap'] = $player[5] . "%";
   //KI-Persönlichkeit bestimmen (Get AI type)
   switch ($player[6])
   {
    case 0: $playerarray[$i]['aitype'] = "Ausgewogen"; break;
    case 1: $playerarray[$i]['aitype'] = "Rusher"; break;
    case 2: $playerarray[$i]['aitype'] = "Einigeln"; break;
    case 3: $playerarray[$i]['aitype'] = "Guerilla"; break;
    case 4: $playerarray[$i]['aitype'] = "Dampfwalze"; break;
    default: $playerarray[$i]['aitype'] = "Zufällig"; break;
   }
  }
  // ----- X = No player -----
  elseif (strstr($player[0], "X"))
   continue; //nothing to do, go on
  // ----- Unbekannter Typ (Unknown player type) -----
  else
   $playerarray[$i]['playertype'] = "<b>UNBEKANNT!</b>";
 }
 $iniarray['players'] = $playerarray;
 //Spieltyp (Match type)
 $iniarray['matchtype'] = ($ft) ? "Automatch" : "Eigenes Match";
 return $iniarray;
}

#
# Konvertiert einen binären Zahlenstring mit der gegebenen Länge zu einer natürlichen Zahl.
# (Converts a binary string of numbers with the given length to a natural number.)
#
function conv($var, $anz) //$var kann filepointer oder string sein!
{
 for ($i=0; $i<$anz; $i++) $erg += ord((is_string($var)) ? substr($var, $i, 1) : fread($var, 1))*pow(256, $i);
 return $erg;
}

#
# Liest einen binären String bis zum Terminationszeichen.
# (Reads a binary string until termination character.)
#
function readBinString($fp)
{
 $temp = fgetc($fp);
 while ($temp != "\x0")
 {
  $name .= $temp;
  fseek($fp, 1, SEEK_CUR);
  $temp = fgetc($fp);
 }
 return $name;
}

#
# Öffnet eine Replaydatei und gibt die enthaltenen Informationen als Array zurück.
# (Opens a replay file and returns the contained informations as an array.)
#
function openReplay($file)
{
 //Dateiname (ohne Ordner) und Größe in Kilobytes (Replay filename (without folder) and size in kilobytes)
 $replay = array('file' => substr($file, strpos($file, "/")+1), 'size' => round(filesize($file)/1024) . " KB");
 //Replay öffnen (Open replay)
 if (!$fp = fopen($file, "r")) return false;
 //"C&C3 REPLAY HEADER" lesen (Read header)
 if (strcmp(fread($fp, 18), "C&C3 REPLAY HEADER") != 0) return false;
 //19 Bytes überspringen (Skip 19 Bytes)
 fseek($fp, 19, SEEK_CUR);
 //Spielname lesen (Read game name)
 $replay['name'] = readBinString($fp);
 //1 Byte überspringen (Skip 1 Byte)
 fseek($fp, 1, SEEK_CUR);
 //Spielbeschreibung lesen (Read match description)
 $replay['desc'] = readBinString($fp);
 //1 Byte überspringen (Skip 1 Byte)
 fseek($fp, 1, SEEK_CUR);
 //Kartenname lesen (Read mapname)
 $replay['mapname'] = readBinString($fp);
 $replay['picname'] = str_replace("'", "", $replay['mapname']);
 //"FakeMapID", Spielernamen und "CNC3RPL" überspringen (Skip "FakeMapID", player names and "CNC3RPL")
 while ($temp != "\x0\x0\x0\x0") $temp = fread($fp, 4);
 //"M=" suchen (Seek "M=")
 do
 {
  //...wenn nicht, das jetztige "=" überspringen und das nächste suchen (...if not, skip the current "=" and search the next one)
  $temp = fread($fp, 4);
  while ($temp != "=") $temp = fread($fp, 1);
  //Zwei Bytes zurück und das Zeichen vor "=" überprüfen (Two bytes back and check char before "=")
  fseek($fp, -2, SEEK_CUR);
 }
 //Absichern, dass auch wirklich "M=" erreicht wurde... (Make sure, "M=" is indeed reached...)
 while (fread($fp, 1) != "M");
 //Zum Datum zurückspringen, da dies eine fixe Position vor dem "M=" hat (Jump back to date, because it has a fixed position before the "M=")
 fseek($fp, -42, SEEK_CUR);
 //Unix Zeitstempel lesen, konvertieren und formatieren (Read, convert and format Unix timestamp)
 $replay['date'] = date("d.m.Y, H:i:s", conv($fp, 4));
 //Wieder zum "M=" springen (Jump back to "M=")
 fseek($fp, 39, SEEK_CUR);
 //INI File Chunk lesen und parsen (Read and parse INI file chunk)
 $temp = fread($fp, 1);
 while ($temp != "\x0")
 {
  $ini .= $temp;
  $temp = fread($fp, 1);
 }
 $replay['ini'] = parseINIString($ini);
 $replay['official'] = (stristr($replay['ini']['mapfilename'], "official")) ? true : false;
 //Weitere Sachen überspringen und Versionsnummer suchen (Skip some things and search version number)
 do
 {
  //...wenn nicht, den jetzigen Punkt überspringen und den nächsten suchen (...if not, skip the current dot and search the next one)
  $temp = fread($fp, 4);
  while ($temp != ".") $temp = fread($fp, 1);
  //Drei Bytes zurück und das Zeichen vor der Zahl und dem Punkt überprüfen (Three bytes back and check char before major version number)
  fseek($fp, -3, SEEK_CUR);
 }
 //Absichern, dass auch wirklich der Punkt der Versionsnummer erreicht wurde... (Make sure, dot from the version number is indeed reached...)
 while (fread($fp, 1) != "\x0");
 //Version lesen (Read version)
 $version = fread($fp, 3);
 //Falls die Nummer länger ist als 1.x (In case version is longer than 1.x)
 $temp = "";
 while ($temp != ".")
 {
  $version .= $temp;
  $temp = fread($fp, 1);
 }
 $replay['version'] = $version;
 //Zum Ende der Datei, ein paar Bytes vor dem Footer (Jump to end of file, a few bytes before the footer)
 fseek($fp, -40, SEEK_END);
 //Dauer konvertieren, berechnen und formatieren (Convert, calculate and format length)
 $replay['length'] = date("i:s", (conv(substr(strstr(fread($fp, 40), "C&C3 REPLAY FOOTER"), 18, 4), 4)/15));
 //Replay schließen (Close replay)
 fclose($fp);
 //Fertig, Information zurück geben :) (Done, return informations :) )
 return $replay;
}

#
# Speichert temporär ein Replay von einer externen Quelle lokal ab, während die Infos ausgelesen werden. Ordner tmp muss existieren!
# (Saves temporary a replay from an extarnal source locally, while the informations are retrieved. Folder tmp has to exist!)
#
function streamReplay($replay, $repfile="")
{
 //Bombensicherer Name (100% not assigned name)
 $repfile = "tmp/" . (($repfile && !file_exists("tmp/" . $repfile)) ? $repfile : strtr(microtime(), array(" " => "", "." => ""))  . ".CNC3Replay");
 //Falls eine ältere PHP Version als 5.0 vorliegt, dürfen nur PHP4 Funktionen benutzt werden (In case of an older PHP version than 5.0, only use PHP4 functions)
 if (substr(phpversion(), 0, 1) < 5)
 {
  //Datei zum binär-schreiben öffnen (Open file for binary write)
  $fp = fopen($repfile, "wb");
  //Datei holen und lokal speichern [PHP4] (Get file and save local [PHP4])
  fwrite($fp, file_get_contents($replay));
  fclose($fp);
 }
 //Datei holen und lokal speichern [PHP5] (Get file and save local [PHP5])
 else file_put_contents($repfile, file_get_contents($replay));
 //Infos auslesen (Read infos)
 $replay = openReplay($repfile);
 //Replay wieder löschen (Finally delete replay)
 unlink($repfile);
 //Infos zurückgeben (Return infos)
 return $replay;
}
?>