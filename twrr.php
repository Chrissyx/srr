<?php
/**
 * SAGE Replay Reader (SRR)
 * 
 * A replay parser for C&C games based on the XML SAGE engine.<br />
 * Supporting Tiberium Wars, Kane's Wrath, Red Alert 3 + Beta, Uprising and Tiberian Twilight + Beta >Rev 8
 * 
 * Thanks to...<br />
 *           ...Quicksilver for GRR source code and inspiration<br />
 *           ...MerlinSt for helpful hints and code fragments<br />
 *           ...Lepidosteus for PHP Replay Parser code and algorithms
 * 
 * @author Chrissyx <chris@chrissyx.com>
 * @copyright Script written exclusively for CnC Foren (http://www.cncforen.de/)
 * @link http://www.chrissyx.de.vu/
 * @package SRR
 * @version 1.2 Beta (2010-04-10)
 */

/**
 * Gibt die Partei eines Spielers anhand des gegebenen Wertes zurück.<br />
 * (Returns the faction of a player by the given value.)
 * 
 * @param string $faction Fraktion ID (Faction ID)
 * @param string $type C&C Spiel Abk. (C&C game abbr.)
 * @return string Partei (Faction)
 */
function getFaction($faction, $type)
{
 switch($type)
 {
  case 'CnC4':
  case 'CnC4Beta':
  switch($faction)
  {
   case '-1': return 'Zufall';
   case  '1': return 'Beobachter';
   case  '8': return 'GDI';
   case  '9': return 'Nod';
   default: return '<b>UNBEKANNT!</b>';
  }

  case 'RA3U':
  switch($faction)
  {
   case '-1':
   case  '8': return 'Zufall';
   case  '1': return 'Zuschauer';        //(Observer)
   case  '3': return 'Aufgehende Sonne'; //(Rising Sun)
   //Commentator??
   case  '5': return 'Alliierte';        //(Allies)
   case  '9': return 'Sowjets';          //(Soviets)
   default: return '<b>UNBEKANNT!</b>';
  }

  case 'RA3':
  switch($faction)
  {
   case '-1':
   case  '7': return 'Zufall';
   case  '1': return 'Zuschauer';        //(Observer)
   case  '2': return 'Aufgehende Sonne'; //(Rising Sun)
   case  '3': return 'Kommentator';      //(Commentator)
   case  '4': return 'Alliierte';        //(Allies)
   case  '8': return 'Sowjets';          //(Soviets)
   default: return '<b>UNBEKANNT!</b>';
  }

  case 'KW':
  switch($faction)
  {
   case '-1':                         //Menschlicher Spieler mit zufälliger Partei (Human player with random faction)
   case  '1': return 'Zufall';        //CPU Spieler mit zufälliger Partei (AI player with random faction)
   case  '2': return 'Zuschauer';     //(Observer)
   case  '3': return 'Kommentator';   //(Commentator)
   case  '6': return 'GDI';
   case  '7': return 'Steel Talons';
   case  '8': return 'ZOCOM';
   case  '9': return 'Nod';
   case '10': return 'Schwarze Hand'; //(Black Hand)
   case '11': return 'Kanes Juenger';  //(The Marked of Kane)
   case '12': return 'Scrin';
   case '13': return 'Reaper-17';
   case '14': return 'Traveler-59';
   default: return '<b>UNBEKANNT!</b>';
  }

  case 'CNC3':
  switch($faction)
  {
   case '-1':                       //Menschlicher Spieler mit zufälliger Partei (Human player with random faction)
   case  '1': return 'Zufall';      //CPU Spieler mit zufälliger Partei (AI player with random faction)
   case  '2': return 'Zuschauer';   //(Observer)
   case  '3': return 'Kommentator'; //(Commentator)
   case  '6': return 'GDI';
   case  '7': return 'Nod';
   case  '8': return 'Scrin';
   default: return '<b>UNBEKANNT!</b>';
  }
 }
}

/**
 * Gibt die Farbe eines Spielers anhand des gegebenen Wertes zurück.<br />
 * (Returns the color of a player by the given value.)
 * 
 * @param string $color Farb ID (Color ID)
 * @param string $type C&C Spiel Abk. (C&C game abbr.)
 * @return string Farbe (Color)
 */
function getColor($color, $type)
{
 if(substr($type, 0, 4) == 'CnC4') return 'n/a'; //No colors in CnC4
 switch($color)
 {
  case '-1': return 'Zufall';   //Keine oder zufällige Farbe (No color or random one)
  case  '0': return 'Blau';      //(Blue)
  case  '1': return 'Gelb';      //(Yellow)
  case  '2': return 'Gruen';      //(Green)
  case  '3': return 'Orange';    //(Orange)
  //Diffrent color codes in RA3 and no pink
  case  '4': return ($type == 'RA3') ? 'Lila' : 'Rosa';    //(Purple / Pink)
  case  '5': return ($type == 'RA3') ? 'Rot' : 'Lila';     //(Red / Purple)
  case  '6': return ($type == 'RA3') ? 'Hellblau' : 'Rot'; //(Light blue / Red)
  case  '7': return 'Hellblau';  //(Light blue)
  default: return '<b>UNBEKANNT!</b>';
 }
}

/**
 * Gibt die KI-Persönlichkeit anhand der gegebenen Werte zurück.<br />
 * (Returns the AI type by the given values.)
 * 
 * @param string $aitype KI Persönlichkeit ID (AI type ID)
 * @param string $faction Fraktion ID (Faction ID)
 * @param string $type C&C Spiel Abk. (C&C game abbr.)
 * @return string KI Persönlichkeit (AI type)
 * @since 0.10
 */
function getAIType($aitype, $faction, $type)
{
 switch($type)
 {
  case 'CnC4':
  switch($aitype)
  {
   case '0': return 'Offensive';
   case '1': return 'Unterstützung';
   case '2': return 'Defensive';
   default: return '<b>UNBEKANNT!</b>';
  }

  case 'RA3U':
  switch($faction . $aitype)
  {
   //Aufgehende Sonne (Rising Sun)
   case '30': return 'Hinterhaltsexperte';
   case '31': return 'Mecha-Kriegführung';
   case '32': return 'Flottenkommandant';
   //Alliierte (Allies)
   case '50': return 'Direkter Angriff';
   case '51': return 'Geschwaderführer';
   case '52': return 'Special Forces';
   //Sowjets (Soviets)
   case '90': return 'Schwere Panzer';
   case '91': return 'Schockspezialist';
   case '92': return 'Luftwaffenheldin';
   //Zufall (Random)
   default: return 'Zufällig';
  }

  case 'RA3':
  switch($faction . $aitype)
  {
   //Aufgehende Sonne (Rising Sun)
   case '20': return 'Shinzo';
   case '21': return 'Kenji';
   case '22': return 'Naomi';
   //Alliierte (Allies)
   case '40': return 'Warren';
   case '41': return 'Giles';
   case '42': return 'Lissette';
   //Sowjets (Soviets)
   case '80': return 'Oleg';
   case '81': return 'Moskvin';
   case '82': return 'Zhana';
   //Zufall (Random)
   default: return 'Zufällig';
  }

  case 'KW':
  case 'CNC3':
  switch($aitype)
  {
   case '0': return 'Ausgewogen';
   case '1': return 'Rushen';
   case '2': return 'Einigeln';
   case '3': return 'Guerilla';
   case '4': return 'Dampfwalze';
   default: return 'Zufällig';
  }
 }
}

/**
 * Parst den INI String des Replays und gibt die Werte zurück.<br />
 * (Parse the INI string from the replay and returns the values.)
 * 
 * @param string $ini INI File Chunk (INI file chunk)
 * @param string $type C&C Spiel Abk. (C&C game abbr.)
 * @return mixed Array mit den INI Daten (Array with INI data)
 */
function parseINIString($ini, $type)
{
 //String kürzen und auflösen (Trim and split string)
 $ini = explode(';', substr($ini, 3, strlen($ini)-4));                   //print_r($ini); //debug
 //Array map format:
  #0 -> mapfilename
  #1 -> MC => Map CRC?
  #2 -> MS
  #3 -> SD => Seed?
  #4 -> GSID
  #5 -> GT (Not in CnC4 or CnC4Beta)
  #6 -> PC => Player Counter? Post Commentator? //$pc = substr($ini[6], 3);
  #6 -> T (CnC4 and CnC4Beta >Rev 8 only)
  #7 -> [N]RU => [New?] Rules (Starting cash, game speed, random crates, etc.)
  #8 -> S => All participants, seperated by ":"
  #8 -> AD (CnC4 and CnC4Beta >Rev 8 only)
  #9 -> AO (CnC4 and CnC4Beta >Rev 8 only)
 #10 -> S (CnC4 and CnC4Beta >Rev 8 only, see #8)
 $iniarray['mapfilename'] = $ini[0];
 //Spielregeln verarbeiten (Process rules)
 $rules = explode(' ', trim($ini[7]));                                   //print_r($rules); //debug
 //Rules format:
  #0 -> CnC4 added gametype? number on top, shifting whole rules positions plus one
  #0 -> Game type: 1=offline, 2=LAN, 3=unranked, 4=1v1, 5=2v2, 6=1v1 clan, 7=2v2 clan (4-7 all ranked)
  #1 -> Game speed (max 100)
  #2 -> Starting cash
  #3 -> BattleCasted
  #4 -> VoIP
  #5 -> BattleCast delay, minutes
  #6 -> Random crates
  #7 -> unknown number?
  #8 -> unknown number? -1
  #9 -> unknown number? -1
 #10 -> unknown number? -1
 #11 -> time limit in seconds? (CnC4 only)
 #12 -> unknown number? -1
 #13 -> RA3 only?
 #14 -> CnC4 and CnC4Beta only?
 #15-#17 -> CnC4 only?
 #18 -> Effektivität (CnC4 >1.0 only)
 #19 -> Siegpunkte (CnC4 >1.0 only)
 #20 -> CnC4 >1.0 only?
 #21 -> Karte enthüllen (CnC4 >1.0 only)
 #22 -> Zielpunkte-Multiplikator (CnC4 >1.0 only)
 #23 -> Einheitenkill-Multiplikator (CnC4 >1.0 only)
 //Spielart bestimmen (Get game type)
 switch(substr($rules[0], $type == 'CnC4' ? 4 : 3))
 {
  case  '1': $iniarray['gametype'] = 'Offline, Gefecht'; break;
  case  '2': $iniarray['gametype'] = 'Offline, LAN'; break;
  case  '3': $iniarray['gametype'] = 'Online, Unranked'; break;
  case  '4': $iniarray['gametype'] = 'Online, Ranked, 1 vs. 1'; break;
  case  '5': $iniarray['gametype'] = 'Online, Ranked, 2 vs. 2'; break;
  case  '6': $iniarray['gametype'] = 'Online, Clan, 1 vs. 1'; break;
  case  '7': $iniarray['gametype'] = 'Online, Clan, 2 vs. 2'; break;
  case '18':
  case '23':
  $iniarray['gametype'] = 'Online, Herrschaft';
  //Kick first element of CnC4 to fit rules format again
  array_shift($rules);
  break;
  default: $iniarray['gametype'] = '<b>UNBEKANNT!</b>'; break;
 }
 //Spielgeschwindigkeit auslesen (Extract game speed)
 $iniarray['gamespeed'] = $rules[1] . '%';
 //Startgeld auslesen (Extract starting cash)
 $iniarray['startcash'] = $rules[2] . '$';
 //BattleCast bestimmen (Detect BattleCast)
 $iniarray['bc'] = ($rules[3] == '1') ? 'Ja' : 'Nein';
 //VoIP
 $iniarray['voip'] = ($rules[4] == '1') ? 'Ja' : 'Nein';
 //BattleCast Aufnahmeverzögerung (BattleCast delay)
 $iniarray['delay'] = $rules[5] . ' Minuten';
 //Zufallskisten bestimmen (Detect random crates)
 $iniarray['crates'] = ($rules[6] == '0') ? 'Nein' : 'Ja';
 //CnC4 1.02 oder höher exklusiver Krams (CnC4 1.02 or higher only stuff)
 if($type == 'CnC4' && count($rules) > 18)
 {
  //Zeitlimit (time limit)
  $iniarray['cnc4']['time'] = substr(gmdate('H:i:s', $rules[10]), 1);
  //Effektivität (effectiveness)
  switch($rules[17])
  {                            
   case '1': $iniarray['cnc4']['effect'] = 'Niedrig'; break;
   case '2': $iniarray['cnc4']['effect'] = 'Mittel'; break;
   case '3': $iniarray['cnc4']['effect'] = 'Hoch'; break;
   case '4': $iniarray['cnc4']['effect'] = 'Sehr hoch'; break;
   default: $iniarray['cnc4']['effect'] = '<b>UNBEKANNT!</b>'; break;
  }
  //Siegpunkte (winning points)
  $iniarray['cnc4']['winpoints'] = $rules[18];
  //Karte enthüllen (reveal map)
  $iniarray['cnc4']['revealmap'] = $rules[20] == '1' ? 'Ja' : 'Nein';
  //Zielpunkte-Multiplikator (aim? point multiplier)
  $iniarray['cnc4']['aimpoints'] = $rules[21] . 'x';
  //Einheitenkill-Multiplikator (unit kill multiplier)
  $iniarray['cnc4']['killpoints'] = $rules[22] . 'x';
 }
 //Weiter mit INI Eintrag 8 oder 10 (Proceed with INI entry 8 or 10)
 $ini = explode(':', substr($ini[substr($type, 0, 4) == 'CnC4' && count($ini) > 10 ? 10 : 8], 2, strlen($ini[substr($type, 0, 4) == 'CnC4' && count($ini) > 10 ? 10 : 8])-3));
 //Jeden Spieler verarbeiten (Process each player)
 $size = count($ini)+1;
 for($i=1; $i<$size; $i++) //8 iterations needed?!
 {
  //Spielerinfos ermitteln (Extract player infos)
  $player = explode(',', $ini[$i-1]);                                      //print_r($player); //debug
  //"Kommentator-Leiche" überspringen (Skip "Commentator-corpse")
  if($player[0] == 'Hpost Commentator') continue;
  // ----- H = Human -----
  if($player[0][0] == 'H')
  {
   //Human player format:
    #0 -> Tag + Player name
    #1 -> UID: IP in hex, 0=offline game
    #2 -> unknown number?
    #3 -> Match type (FT=Automatch / TT, TF=Custom match)
    #4 -> Color: -1=random
    #5 -> Faction: 6=GDI, 7=Nod, 8=Scrin, -1=random / KW: 6=GDI, 7=Steel Talons, 8=ZOCOM, 9=Nod, 13=Reaper-17, 14=Traveler-59 / RA3: 2=Empire 4=Allies, 8=Soviets, 7=random
    #6 -> Map position (CnC4: Faction: 8=GDI, 9=Nod)
    #7 -> Team number
    #8 -> Handicap (CnC4: Team number)
    #9 -> unknown number?
   #10 -> unknown number?
   #11 -> Clan tag
   //Spielertyp bestimmen (Get player type)
   $playerarray[$i]['playertype'] = ($player[5] == '1' && substr($type, 0, 3) == 'RA3') || ($player[5] == '2' && $type != 'RA3') || ($player[5] == '3') ? getFaction($player[5], $type) : 'Spieler';
   /*switch($player[5])
   {
    case '1': $playerarray[$i]['playertype'] = (($type == 'RA3') ? 'Zuschauer' : 'Spieler'); break;
    case '2': $playerarray[$i]['playertype'] = (($type == 'RA3') ? 'Spieler' : 'Zuschauer'); break; #TODO! RA3 getFaction & default: 'Spieler'!!
    case '3': $playerarray[$i]['playertype'] = 'Kommentator'; break;
    default: $playerarray[$i]['playertype'] = 'Spieler'; break;
   }*/
   //Spielername extrahieren (Extract player name)
   $playerarray[$i]['playername'] = substr($player[0], 1);
   //IP Adresse bestimmen (Get IP address)
   if($player[1] != '0')
   {
    $playerarray[$i]['ip'] = '';
    for($j=0; $j<8; $j+=2) $playerarray[$i]['ip'] .= hexdec(substr($player[1], $j, 2)) . '.';
    $playerarray[$i]['ip'] = substr($playerarray[$i]['ip'], 0, -1);
   }
   //Spieltyp sichern für später (Save match type for later use)
   $ft = ($player[3] == 'FT') ? true : false;
   //Farbe bestimmen (Get color)
   $playerarray[$i]['color'] = getColor($player[4], $type);
   //Partei bestimmen (Get faction)
   $playerarray[$i]['faction'] = getFaction($player[substr($type, 0, 4) == 'CnC4' ? 6 : 5], $type);
   //Spielposition bestimmen (Get map position)
   $playerarray[$i]['mappos'] = ($player[substr($type, 0, 4) == 'CnC4' ? 5 : 6] == '-1') ? 'Zufällig' : ($player[substr($type, 0, 4) == 'CnC4' ? 5 : 6]+1);
   //Team bestimmen (Get team)
   $playerarray[$i]['team'] = ($player[substr($type, 0, 4) == 'CnC4' ? 8 : 7] != '-1') ? ($player[substr($type, 0, 4) == 'CnC4' ? 8 : 7]+1) : '-';
   //Handicap
   $playerarray[$i]['handicap'] = substr($type, 0, 4) == 'CnC4' ? 'n/a' : (($player[8] == '-1' ? '0' : $player[8]) . '%');
   //Clan tag
   $playerarray[$i]['clan'] = substr($type, 0, 4) == 'CnC4' && $player[11] == '0' ? '' : $player[11];
  }
  // ----- C = Computer -----
  elseif($player[0][0] == 'C')
  {
   //CPU player format:
   #0 -> Difficulty: CE = easy, CM = medium, CH = hard, CB = brutal
   #1 -> Color
   #2 -> Faction: 6=GDI, 7=Nod, 8=Scrin, 1=random
   #3 -> Map position (CnC4: Faction: 8=GDI, 9=Nod)
   #4 -> Team number
   #5 -> Handicap (CnC4: Team number)
   #6 -> AI type
   #7 -> CnC4 only?
   $playerarray[$i]['playertype'] = 'Computer';
   //Schwierigkeit bestimmen (Get difficulty)
   switch($player[0])
   {
    case 'CE': $playerarray[$i]['diff'] = ($type == 'KW' || $type == 'CnC4') ? 'Einfache KI' : 'Leicht'; break;
    case 'CM': $playerarray[$i]['diff'] = ($type == 'KW' || $type == 'CnC4') ? 'Mittlere KI' : 'Mittel'; break;
    case 'CH': $playerarray[$i]['diff'] = ($type == 'KW' || $type == 'CnC4') ? 'Schwierige KI' : 'Schwer'; break;
    case 'CB': $playerarray[$i]['diff'] = $type == 'CnC4' ? 'Brutale KI' : ($type == 'KW' ? 'Erbarmungslose KI' : 'Brutal'); break;
    default: $playerarray[$i]['diff'] = '<b>UNBEKANNT!</b>'; break;
   }
   //Farbe bestimmen (Get color)
   $playerarray[$i]['color'] = getColor($player[1], $type);
   //Partei bestimmen (Get faction)
   $playerarray[$i]['faction'] = getFaction($player[$type == 'CnC4' ? 3 : 2], $type);
   //Spielposition bestimmen (Get map position)
   $playerarray[$i]['mappos'] = ($player[$type == 'CnC4' ? 2 : 3] == '-1') ? 'Zufällig' : ($player[3]+1);
   //Team bestimmen (Get team)
   $playerarray[$i]['team'] = ($player[$type == 'CnC4' ? 5 : 4] != -1) ? ($player[$type == 'CnC4' ? 5 : 4]+1) : '-';
   //Handicap
   $playerarray[$i]['handicap'] = $type == 'CnC4' ? 'n/a' : $player[5] . '%';
   //KI-Persönlichkeit bestimmen (Get AI type)
   $playerarray[$i]['aitype'] = getAIType($player[6], $player[$type == 'CnC4' ? 3 : 2], $type);
  }
  // ----- X = No player -----
  elseif($player[0][0] == 'X')
   continue; //nothing to do, go on
  // ----- Unbekannter Typ (Unknown player type) -----
  else
   $playerarray[$i]['playertype'] = '<b>UNBEKANNT!</b>';
 }
 $iniarray['players'] = $playerarray;
 //Spieltyp (Match type)
 $iniarray['matchtype'] = ($ft) ? 'Automatch' : 'Eigenes Match';
 return $iniarray;
}

/**
 * Konvertiert einen binären Zahlenstring mit der gegebenen Länge zu einer natürlichen Zahl.<br />
 * (Converts a binary string of numbers with the given length to a natural number.)
 * 
 * @param mixed $var Filepointer oder binärer Zahlenstring (Filepointer or binary string of numbers)
 * @param int $anz Benötigte Iterationen / Zahlenlänge (Needed iterations / Length of number)
 * @return int Natürliche Zahl (Natural number)
 */
function conv($var, $anz) //$var kann filepointer oder string sein!
{
 if(!$var) return 0;
 $erg = 0;
 for($i=0; $i<$anz; $i++) $erg += ord((is_string($var)) ? substr($var, $i, 1) : fread($var, 1))*pow(256, $i);
 return $erg;
}

/**
 * Liest einen binären String bis zum Terminationszeichen.<br />
 * (Reads a binary string until termination character.)
 * 
 * @param mixed $fp Filepointer
 * @return string Zeichenkette (String)
 */
function readBinString($fp)
{
 $temp = fgetc($fp);
 $name = '';
 while($temp != "\x0")
 {
  $name .= $temp;
  fseek($fp, 1, SEEK_CUR);
  $temp = fgetc($fp);
 }
 return $name;
}

/**
 * Öffnet eine Replaydatei und gibt die enthaltenen Informationen als Array zurück.<br />
 * (Opens a replay file and returns the contained informations as an array.)
 * 
 * @param string $file Die Replaydatei (The replayfile)
 * @param string $type Spieltyp aus der Endung des Replays (Gametype from the ending of the replay)
 * @return mixed Array mit Informationen zum Replay (Array with informations from the replay)
 */
function openReplay($file, $type)
{
 //Dateiname (ohne Ordner) und Größe in Kilobytes (Replay filename (without folder) and size in kilobytes)
 $replay = array('file' => basename($file), 'size' => round(filesize($file)/1024) . ' kB');
 //Replay öffnen (Open replay)
 if(!$fp = fopen($file, 'rb')) return false;
 //"[C&C|RA]3 REPLAY HEADER" lesen (Read header)
 $temp = fread($fp, 18);
 if(strcmp($temp, 'C&C3 REPLAY HEADER') == 0) fseek($fp, 19, SEEK_CUR);
 elseif(strncmp($temp, 'RA3 REPLAY HEADER', 17) == 0) fseek($fp, 18, SEEK_CUR);
 elseif(strncmp($temp, "\xB\x0\x0\x0" . 'CnC4Beta', 12) == 0) fseek($fp, 5, SEEK_CUR);
 elseif(strncmp($temp, "\x7\x0\x0\x0" . 'CnC4', 8) == 0) fseek($fp, 9, SEEK_CUR);
 else return false;
 //Spielname lesen (Read game name)
 $replay['name'] = readBinString($fp);
 fseek($fp, 1, SEEK_CUR);
 //Spielbeschreibung lesen (Read match description)
 $replay['desc'] = readBinString($fp);
 fseek($fp, 1, SEEK_CUR);
 //Kartenname lesen (Read mapname)
 $replay['mapname'] = readBinString($fp);
 $replay['picname'] = str_replace("'", '', $replay['mapname']);
 $replay['exists'] = file_exists('images/mappics/' . $replay['picname'] . '.png');
 //"FakeMapID", Spielernamen und "CNC3RPL" überspringen (Skip "FakeMapID", player names and "CNC3RPL")
 while($temp != "\x0\x0\x0\x0") $temp = fread($fp, 4);
 //"M=" suchen (Seek "M=")
 do
 {
  //...wenn nicht, das jetztige "=" überspringen und das nächste suchen (...if not, skip the current "=" and search the next one)
  $temp = fread($fp, 4);
  while($temp != '=') $temp = fread($fp, 1);
  //Zwei Bytes zurück und das Zeichen vor "=" überprüfen (Two bytes back and check char before "=")
  fseek($fp, -2, SEEK_CUR);
 }
 //Absichern, dass auch wirklich "M=" erreicht wurde... (Make sure, "M=" is indeed reached...)
 while(fread($fp, 1) != 'M');
 //Zum Datum zurückspringen, da dies eine feste Position vor dem "M=" hat (Jump back to date, because it has a fixed position before the "M=")
 fseek($fp, substr($type, 0, 4) == 'CnC4' ? -46 : (substr($type, 0, 3) == 'RA3' ? -40 : -42), SEEK_CUR);
 //Unix Zeitstempel lesen, konvertieren und formatieren (Read, convert and format Unix timestamp)
 $replay['date'] = date('d.m.Y, H:i:s', conv($fp, 4));
 //Wieder zum "M=" springen (Jump back to "M=")
 fseek($fp, substr($type, 0, 4) == 'CnC4' ? 43 : (substr($type, 0, 3) == 'RA3' ? 37 : 39), SEEK_CUR);
 //INI File Chunk lesen und parsen (Read and parse INI file chunk)
 $temp = fread($fp, 1);
 $ini = '';
 while($temp != "\x0")
 {
  $ini .= $temp;
  $temp = fread($fp, 1);
 }
 $replay['ini'] = parseINIString($ini, $type);
 $replay['official'] = (stristr($replay['ini']['mapfilename'], 'official')) ? true : false;
 //Weitere Sachen überspringen und Versionsnummer suchen (Skip some things and search version number)
 do
 {
  //...wenn nicht, den jetzigen Punkt überspringen und den nächsten suchen (...if not, skip the current dot and search the next one)
  $temp = fread($fp, 4);
  while($temp != '.') $temp = fread($fp, 1);
  //Drei Bytes zurück und das Zeichen vor der Zahl und dem Punkt überprüfen (Three bytes back and check char before major version number)
  fseek($fp, -3, SEEK_CUR);
 }
 //Absichern, dass auch wirklich der Punkt der Versionsnummer erreicht wurde... (Make sure, dot from the version number is indeed reached...)
 while(fread($fp, 1) != "\x0");
 //Version lesen (Read version)
 $version = fread($fp, 3);
 //Falls die Nummer länger ist als 1.x (In case version is longer than 1.x)
 $temp = '';
 while($temp != '.')
 {
  $version .= $temp;
  $temp = fread($fp, 1);
 }
 $replay['version'] = $type == 'CnC4Beta' ? str_replace('1.', 'Rev ', $version) : $version;
 //Zum Ende der Datei, ein paar Bytes vor dem Footer (Jump to end of file, a few bytes before the footer)
 fseek($fp, -70, SEEK_END);
 //Dauer konvertieren, berechnen und formatieren (Convert, calculate and format length)
 $replay['length'] = date('i:s', (conv(substr(strstr(fread($fp, 70), '3 REPLAY FOOTER'), 15, 4), 4)/15));
 if($replay['length'] == '00:00') $replay['length'] = 'n/a';
 //Replay schließen (Close replay)
 fclose($fp);
 //Fertig, Information zurück geben :) (Done, return informations :))
 return $replay;
}

/**
 * Speichert temporär ein Replay von einer externen Quelle lokal ab, während die Infos ausgelesen werden.<br />
 * (Saves temporary a replay from an extarnal source locally, while the informations are retrieved.)
 * 
 * @param string $replay URL des Replays (URL of the replay)
 * @param string $repfile Dateiname des Replays (Filename of the replay)
 * @return mixed Array mit Informationen zum Replay (Array with informations from the replay)
 * @since 0.8.7
 */
function streamReplay($replay, $repfile=null)
{
 //Spiel bestimmen (Detect game)
 if(!preg_match("/.*?\.(\w+?)Replay$/", $repfile, $type)) return false;
 //Temporäre Datei zum Schreiben anlegen - Ordner 'tmp' muss exisitieren! (Create temp file for writing - Folder 'tmp' has to exist!)
 $repfile = tempnam('tmp', $repfile);
 //Falls eine ältere PHP Version als 5.0 vorliegt, dürfen nur PHP4 Funktionen benutzt werden (In case of an older PHP version than 5.0, only use PHP4 functions)
 if(substr(phpversion(), 0, 1) < '5')
 {
  //Datei zum binär-schreiben öffnen (Open file for binary write)
  $fp = fopen($repfile, 'wb');
  //Datei holen und lokal speichern [PHP4] (Get file and save local [PHP4])
  fwrite($fp, file_get_contents($replay));
  fclose($fp);
 }
 //Datei holen und lokal speichern [PHP5] (Get file and save local [PHP5])
 else file_put_contents($repfile, file_get_contents($replay));
 //Infos auslesen (Read infos)
 $replay = openReplay($repfile, $type[1]);
 //Replay wieder löschen (Finally delete replay)
 unlink($repfile);
 //Infos zurückgeben (Return infos)
 return $replay;
}

/**
 * Liest ein Replay auf der vBulletin3-Datenbank aus und speichert es temporär ab, während die Infos ausgelesen werden.<br />
 * (Reads a replay from the vBulletin3-database and saves it temporary, while the informations are retrieved.)
 * 
 * @param string $id ID des vBulletin3-Attachments (ID of the vBulletin3-attachment)
 * @param string $repfile Dateiname des Replays (Filename of the replay)
 * @return mixed Array mit Informationen zum Replay (Array with informations from the replay)
 * @since 0.9.10
 */
function queryReplay($id, $repfile)
{
 //Spiel bestimmen (Detect game)
 if(!preg_match("/.*?\.(\w+?)Replay$/i", $repfile, $type)) return false;
 //Temporäre Datei zum Schreiben anlegen (Create temp file for writing)
 $repfile = tempnam('tmp', $repfile);
 //vBulletin3-DB Objekt holen (Provide vBulletin3-DB object)
 global $db;
 //Falls eine ältere PHP Version als 5.0 vorliegt, dürfen nur PHP4 Funktionen benutzt werden (In case of an older PHP version than 5.0, only use PHP4 functions)
 if(substr(phpversion(), 0, 1) < '5')
 {
  //Datei zum binär-schreiben öffnen (Open file for binary write)
  $fp = fopen($repfile, 'wb');
  //Replay aus vBulletin3-Datenbank lesen und lokal speichern [PHP4] (Get replay from vBulletin3-database and save local [PHP4])
  fwrite($fp, array_shift($db->query_first('SELECT filedata FROM ' . TABLE_PREFIX . 'attachment WHERE attachmentid = ' . intval($id))));
  fclose($fp);
 }
 //Replay aus vBulletin3-Datenbank lesen und lokal speichern [PHP5] (Get replay from vBulletin3-database and save local [PHP5])
 else file_put_contents($repfile, array_shift($db->query_first('SELECT filedata FROM ' . TABLE_PREFIX . 'attachment WHERE attachmentid = ' . intval($id))));
 //Infos auslesen (Read infos)
 $replay = openReplay($repfile, strtoupper($type[1]));
 //Replay wieder löschen (Finally delete replay)
 unlink($repfile);
 //Infos zurückgeben (Return infos)
 return $replay;
}
?>