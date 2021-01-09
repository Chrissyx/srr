<?php
/**
 * SAGE Replay Reader (SRR)
 * 
 * A vBulletin 4 replay parser for C&C games based on the XML SAGE engine.
 * Supporting Tiberium Wars, Kane's Wrath, Red Alert 3 + Beta, Uprising and Tiberian Twilight + Beta >Rev 8
 * 
 * Thanks to...
 *           ...Quicksilver for GRR source code and inspiration
 *           ...MerlinSt for helpful hints and code fragments
 *           ...Lepidosteus for PHP Replay Parser code and algorithms
 * 
 * @author Chrissyx <chris@chrissyx.com>
 * @copyright Script written exclusively for CnC Foren (http://www.cncforen.de/)
 * @link http://www.chrissyx.de.vu/
 * @package SRR
 * @version 2.0 (2010-10-22)
 */
class SAGEReplayReader
{
	/**
	 * Folder to use for saving in replays while parsing them.
	 *
	 * @var string Directory for temporary files
	 */
	private $configTempDir = 'tmp';

	/**
	 * Use an own table in the vBulletin database to store information from parsed replays.
	 *
	 * @var bool Use "replay" table in the DB
	 */
	private $configUseDB = false;

	/**
	 * File pointer used to crawl through a replay file.
	 *
	 * @var resource Handle to use
	 */
	private $fp;

	/**
	 * Instance of this class for singleton design pattern.
	 *
	 * @var SAGEReplayReader Instance of this class
	 */
	private static $instance;

	/**
	 * Parsed information of current replay are stored here.
	 *
	 * @var array Parsed replay contents
	 */
	private $replay = array();

	/**
	 * Gametype enum of current replay.
	 *
	 * @var int One of the supported game types
	 */
	private $replayType;

	/**
	 * Defines supported games which can be parsed from this script.
	 *
	 * @var mixed Game type identifiers as part of the file extension
	 */
	private static $supportedTypes = array('CNC3', 'KW', 'RA3', 'RA3U', 'CnC4Beta', 'CnC4');

	/**
	 * Reference to the vBulletin 4 database object.
	 *
	 * @var vB_Database vB4 DB reference
	 */
	private $vB4DB;

	/**
	 * Sets link to vBulletin 4 database object and defines needed constants.
	 *
	 * @return SAGEReplayReader New instance of this class
	 */
	private function __construct()
	{
		//Provide DB link
		global $db;
		$this->vB4DB = &$db;
		//Define needed constants
		if(!defined('SRR_TYPE_' . self::$supportedTypes[0]))
			foreach(self::$supportedTypes as $i => $curReplayType)
				define('SRR_TYPE_' . $curReplayType, $i);
	}

	/**
	 * Converts a binary string of numbers with the given length to a natural number.
	 *
	 * @param mixed $var File pointer or binary string of numbers
	 * @param int $cycle Needed iterations / Length of number
	 * @return int Converted natural number
	 */
	private function convert($var, $cycle)
	{
		$curNum = 0;
		if(!empty($var))
			for($i=0; $i<$cycle; $i++)
				$curNum += ord((is_string($var)) ? substr($var, $i, 1) : fread($var, 1))*pow(256, $i);
		return $curNum;
	}

	/**
	 * Returns the AI type by the given values.
	 *
	 * @param string $aiType AI type ID
	 * @param string $faction Faction ID
	 * @return string AI type name
	 * @since 0.10
	 */
	private function getAIType($aiType, $faction)
	{
		switch($this->replayType)
		{
			case SRR_TYPE_CnC4: //No AIs in CnC4Beta
			switch($aiType)
			{
				case '0': return 'Offensive';
				case '1': return 'Unterstützung';
				case '2': return 'Defensive';
				default: return '<b>UNKNOWN!</b>';
			}

			case SRR_TYPE_RA3U:
			switch($faction . $aiType)
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

			case SRR_TYPE_RA3:
			switch($faction . $aiType)
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

			case SRR_TYPE_KW:
			case SRR_TYPE_CNC3:
			switch($aiType)
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
	 * Returns the color of a player by the given value.
	 *
	 * @param string $color Color ID
	 * @return string Color name
	 */
	private function getColor($color)
	{
		if($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta)
			//No colors in CnC4
			return 'n/a';
		switch($color)
		{
			case '-1': return 'Zufall';   //Keine oder zufällige Farbe (No color or random one)
			case  '0': return 'Blau';      //(Blue)
			case  '1': return 'Gelb';      //(Yellow)
			case  '2': return 'Gruen';      //(Green)
			case  '3': return 'Orange';    //(Orange)
			//Diffrent color codes in RA3 and no pink
			case  '4': return ($this->replayType == SRR_TYPE_RA3) ? 'Lila' : 'Rosa';    //(Purple / Pink)
			case  '5': return ($this->replayType == SRR_TYPE_RA3) ? 'Rot' : 'Lila';     //(Red / Purple)
			case  '6': return ($this->replayType == SRR_TYPE_RA3) ? 'Hellblau' : 'Rot'; //(Light blue / Red)
			case  '7': return 'Hellblau';  //(Light blue)
			default: return '<b>UNKNOWN!</b>';
		}
	}

	/**
	 * Returns the faction of a player by the given value.
	 *
	 * @param string $faction Faction ID
	 * @return string Faction name
	 */
	private function getFaction($faction)
	{
		switch($this->replayType)
		{
			case SRR_TYPE_CnC4:
			case SRR_TYPE_CnC4Beta:
			switch($faction)
			{
				case '-1': return 'Zufall';
				case  '1': return 'Beobachter';
				case  '8': return 'GDI';
				case  '9': return 'Nod';
				default: return '<b>UNKNOWN!</b>';
			}

			case SRR_TYPE_RA3U:
			switch($faction)
			{
				case '-1':
				case  '8': return 'Zufall';
				case  '1': return 'Zuschauer';        //(Observer)
				case  '3': return 'Aufgehende Sonne'; //(Rising Sun)
				//Commentator??
				case  '5': return 'Alliierte';        //(Allies)
				case  '9': return 'Sowjets';          //(Soviets)
				default: return '<b>UNKNOWN!</b>';
			}

			case SRR_TYPE_RA3:
			switch($faction)
			{
				case '-1':
				case  '7': return 'Zufall';
				case  '1': return 'Zuschauer';        //(Observer)
				case  '2': return 'Aufgehende Sonne'; //(Rising Sun)
				case  '3': return 'Kommentator';      //(Commentator)
				case  '4': return 'Alliierte';        //(Allies)
				case  '8': return 'Sowjets';          //(Soviets)
				default: return '<b>UNKNOWN!</b>';
			}

			case SRR_TYPE_KW:
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
				default: return '<b>UNKNOWN!</b>';
			}

			case SRR_TYPE_CNC3:
			switch($faction)
			{
				case '-1':                       //Menschlicher Spieler mit zufälliger Partei (Human player with random faction)
				case  '1': return 'Zufall';      //CPU Spieler mit zufälliger Partei (AI player with random faction)
				case  '2': return 'Zuschauer';   //(Observer)
				case  '3': return 'Kommentator'; //(Commentator)
				case  '6': return 'GDI';
				case  '7': return 'Nod';
				case  '8': return 'Scrin';
				default: return '<b>UNKNOWN!</b>';
			}
		}
	}

	/**
	 * Returns an instance of this class.
	 *
	 * @return SAGEReplayReader Instance of this class
	 */
	public static function &getInstance()
	{
		if(!isset(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Format certain values of the parsed replay information.
	 * Some localization can be done here, e.g. adjusting date formats.
	 */
	private function formatValues()
	{
		//CnC Foren specific
		$this->replay['picname'] = str_replace("'", '', $this->replay['mapname']);
		$this->replay['exists'] = file_exists('images/mappics/' . $this->replay['picname'] . '.png');
		//Round filesize
		$this->replay['size'] = round($this->replay['size']/1024) . ' KiB';
		//Date and time
		$this->replay['date'] = @date('d.m.Y, H:i:s', $this->replay['date']);
		$this->replay['length'] = @date('i:s', $this->replay['length']);
		if($this->replay['length'] == '00:00')
			$this->replay['length'] = 'n/a';
		if(isset($this->replay['ini']['cnc4']['time']))
			$this->replay['ini']['cnc4']['time'] = substr(@gmdate('H:i:s', $this->replay['ini']['cnc4']['time']), 1);
	}

	/**
	 * Opens a replay file and parses the contained informations.
	 *
	 * @param string $replayFile The replay file
	 * @return bool If parsing was successful
	 */
	private function openReplay($replayFile)
	{
		//Open file
		if(!$this->fp = fopen($replayFile, 'rb'))
			return false;
		//Read and check header and adjust position
		$temp = fread($this->fp, 18);
		if(strcmp($temp, 'C&C3 REPLAY HEADER') == 0)
			fseek($this->fp, 19, SEEK_CUR);
		elseif(strncmp($temp, 'RA3 REPLAY HEADER', 17) == 0)
			fseek($this->fp, 18, SEEK_CUR);
		elseif(strncmp($temp, "\xB\x0\x0\x0" . 'CnC4Beta', 12) == 0)
			fseek($this->fp, 5, SEEK_CUR);
		elseif(strncmp($temp, "\x7\x0\x0\x0" . 'CnC4', 8) == 0)
			fseek($this->fp, 9, SEEK_CUR);
		else
			return false;
		//Read game name
		$this->replay['name'] = $this->readBinaryString();
		//Read match description
		fseek($this->fp, 1, SEEK_CUR);
		$this->replay['desc'] = $this->readBinaryString();
		//Read map name
		fseek($this->fp, 1, SEEK_CUR);
		$this->replay['mapname'] = $this->readBinaryString();
		//Skip "FakeMapID", player names and "CNC3RPL")
		while($temp != "\x0\x0\x0\x0")
			$temp = fread($this->fp, 4);
		//Seek "M="
		do
		{
			//...if not, skip the current "=" and search the next one
			$temp = fread($this->fp, 4);
			while($temp != '=')
				$temp = fread($this->fp, 1);
			//Two bytes back and check char before "="
			fseek($this->fp, -2, SEEK_CUR);
		}
		//Make sure, "M=" is indeed reached...
		while(fread($this->fp, 1) != 'M');
		//Jump back to date, because it has a fixed position before the "M="
		fseek($this->fp, $this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta ? -46 : ($this->replayType == SRR_TYPE_RA3 || $this->replayType == SRR_TYPE_RA3U ? -40 : -42), SEEK_CUR);
		//Read and convert Unix timestamp
		$this->replay['date'] = $this->convert($this->fp, 4);
		//Jump back to "M="
		fseek($this->fp, $this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta ? 43 : ($this->replayType == SRR_TYPE_RA3 || $this->replayType == SRR_TYPE_RA3U ? 37 : 39), SEEK_CUR);
		//Read and parse INI file chunk
		$temp = fread($this->fp, 1);
		$ini = '';
		while($temp != "\x0")
		{
			$ini .= $temp;
			$temp = fread($this->fp, 1);
		}
		$this->parseINIString($ini);
		$this->replay['official'] = stristr($this->replay['ini']['mapfilename'], 'official') !== false;
		//Skip some things and search version number
		do
		{
			//...if not, skip the current dot and search the next one
			$temp = fread($this->fp, 4);
			while($temp != '.')
				$temp = fread($this->fp, 1);
			//Three bytes back and check char before major version number
			fseek($this->fp, -3, SEEK_CUR);
		}
		//Make sure, dot from the version number is indeed reached...
		while(fread($this->fp, 1) != "\x0");
		//Read version
		$this->replay['version'] = fread($this->fp, 3);
		//In case version is longer than 1.x
		$temp = '';
		while($temp != '.')
		{
			$this->replay['version'] .= $temp;
			$temp = fread($this->fp, 1);
		}
		if($this->replayType == SRR_TYPE_CnC4Beta)
			$this->replay['version'] = str_replace('1.', 'Rev ', $this->replay['version']);
		//Jump to end of file, a few bytes before the footer
		fseek($this->fp, -70, SEEK_END);
		//Convert and calculate length timestamp
		$this->replay['length'] = $this->convert(substr(strstr(fread($this->fp, 70), '3 REPLAY FOOTER'), 15, 4), 4)/15;
		//Close replay and done :)
		fclose($this->fp);
		return true;
	}

	/**
	 * Parses the INI string from the replay and saves the values.
	 *
	 * @param string $ini INI file chunk string
	 */
	private function parseINIString($ini)
	{
		//Trim and split string
		$ini = explode(';', substr($ini, 3, strlen($ini)-4));
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
		$this->replay['ini']['mapfilename'] = $ini[0];
		//Process rules
		$rules = explode(' ', trim($ini[7]));
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
		//Get game type
		switch(substr($rules[0], $this->replayType == SRR_TYPE_CnC4 ? 4 : 3))
		{
			case  '1': $this->replay['ini']['gametype'] = 'Offline, Gefecht'; break;
			case  '2': $this->replay['ini']['gametype'] = 'Offline, LAN'; break;
			case  '3': $this->replay['ini']['gametype'] = 'Online, Unranked'; break;
			case  '4': $this->replay['ini']['gametype'] = 'Online, Ranked, 1 vs. 1'; break;
			case  '5': $this->replay['ini']['gametype'] = 'Online, Ranked, 2 vs. 2'; break;
			case  '6': $this->replay['ini']['gametype'] = 'Online, Clan, 1 vs. 1'; break;
			case  '7': $this->replay['ini']['gametype'] = 'Online, Clan, 2 vs. 2'; break;
			case '18':
			case '23':
			$this->replay['ini']['gametype'] = 'Online, Herrschaft';
			//Kick first element of CnC4 to fit rules format again
			array_shift($rules);
			break;
			default: $this->replay['ini']['gametype'] = '<b>UNKNOWN!</b>'; break;
		}
		//Extract game speed and starting cash
		$this->replay['ini']['gamespeed'] = intval($rules[1]); //in %
		$this->replay['ini']['startcash'] = intval($rules[2]); //in $
		//Detect BattleCast and VoIP
		$this->replay['ini']['bc'] = $rules[3] == '1';
		$this->replay['ini']['voip'] = $rules[4] == '1';
		//BattleCast delay
		$this->replay['ini']['delay'] = intval($rules[5]); //in minutes
		//Detect random crates
		$this->replay['ini']['crates'] = $rules[6] != '0';
		//CnC4 1.02 or higher only stuff
		if($this->replayType == SRR_TYPE_CnC4 && count($rules) > 18)
		{
			//Time limit
			$this->replay['ini']['cnc4']['time'] = intval($rules[10]);
			//Effectiveness
			switch($rules[17])
			{                            
				case '1': $this->replay['ini']['cnc4']['effect'] = 'Niedrig'; break;
				case '2': $this->replay['ini']['cnc4']['effect'] = 'Mittel'; break;
				case '3': $this->replay['ini']['cnc4']['effect'] = 'Hoch'; break;
				case '4': $this->replay['ini']['cnc4']['effect'] = 'Sehr hoch'; break;
				default: $this->replay['ini']['cnc4']['effect'] = '<b>UNKNOWN!</b>'; break;
			}
			//Winning points
			$this->replay['ini']['cnc4']['winpoints'] = intval($rules[18]);
			//Reveal map
			$this->replay['ini']['cnc4']['revealmap'] = $rules[20] == '1';
			//Zielpunkte-Multiplikator (aim? point multiplier)
			$this->replay['ini']['cnc4']['aimpoints'] = intval($rules[21]); //in 'x'
			//Einheitenkill-Multiplikator (unit kill multiplier)
			$this->replay['ini']['cnc4']['killpoints'] = intval($rules[22]); //in 'x'
		}
		//Proceed with INI entry 8 or 10
		$ini = explode(':', substr($ini[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) && count($ini) > 10 ? 10 : 8], 2, strlen($ini[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) && count($ini) > 10 ? 10 : 8])-3));
		//Process each player
		$size = count($ini)+1;
		for($i=1; $i<$size; $i++) //8 iterations needed?!
		{
			//Extract player info
			$player = explode(',', $ini[$i-1]);
			//Skip "Commentator-corpse"
			if($player[0] == 'Hpost Commentator')
				continue;
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
				//Get player type
				$this->replay['ini']['players'][$i]['playertype'] = ($player[5] == '1' && ($this->replayType == SRR_TYPE_RA3 || $this->replayType == SRR_TYPE_RA3U)) || ($player[5] == '2' && $this->replayType != SRR_TYPE_RA3) || ($player[5] == '3') ? $this->getFaction($player[5]) : 'Spieler';
				//Extract player name
				$this->replay['ini']['players'][$i]['playername'] = substr($player[0], 1);
				//Get IP address
				if($player[1] != '0')
				{
					$this->replay['ini']['players'][$i]['ip'] = '';
					for($j=0; $j<8; $j+=2)
						$this->replay['ini']['players'][$i]['ip'] .= hexdec(substr($player[1], $j, 2)) . '.';
					$this->replay['ini']['players'][$i]['ip'] = substr($this->replay['ini']['players'][$i]['ip'], 0, -1);
				}
				//Save match type for later use
				$ft = $player[3] == 'FT';
				//Get color, faction, map position, team, handicap and clan tag
				$this->replay['ini']['players'][$i]['color'] = $this->getColor($player[4]);
				$this->replay['ini']['players'][$i]['faction'] = $this->getFaction($player[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 6 : 5]);
				$this->replay['ini']['players'][$i]['mappos'] = ($player[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 5 : 6] == '-1') ? 'Zufällig' : ($player[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 5 : 6]+1);
				$this->replay['ini']['players'][$i]['team'] = ($player[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 8 : 7] != '-1') ? ($player[($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 8 : 7]+1) : '-';
				$this->replay['ini']['players'][$i]['handicap'] = ($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) ? 'n/a' : (($player[8] == '-1' ? '0' : $player[8]) . '%');
				$this->replay['ini']['players'][$i]['clan'] = ($this->replayType == SRR_TYPE_CnC4 || $this->replayType == SRR_TYPE_CnC4Beta) && $player[11] == '0' ? '' : $player[11];
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
				$this->replay['ini']['players'][$i]['playertype'] = 'Computer';
				//Get difficulty
				switch($player[0])
				{
					case 'CE': $this->replay['ini']['players'][$i]['diff'] = ($this->replayType == SRR_TYPE_KW || $this->replayType == SRR_TYPE_CnC4) ? 'Einfache KI' : 'Leicht'; break;
					case 'CM': $this->replay['ini']['players'][$i]['diff'] = ($this->replayType == SRR_TYPE_KW || $this->replayType == SRR_TYPE_CnC4) ? 'Mittlere KI' : 'Mittel'; break;
					case 'CH': $this->replay['ini']['players'][$i]['diff'] = ($this->replayType == SRR_TYPE_KW || $this->replayType == SRR_TYPE_CnC4) ? 'Schwierige KI' : 'Schwer'; break;
					case 'CB': $this->replay['ini']['players'][$i]['diff'] = $this->replayType == SRR_TYPE_CnC4 ? 'Brutale KI' : ($this->replayType == SRR_TYPE_KW ? 'Erbarmungslose KI' : 'Brutal'); break;
					default: $this->replay['ini']['players'][$i]['diff'] = '<b>UNKNOWN!</b>'; break;
				}
				//Get color, faction, map position, team, handicap and AI type
				$this->replay['ini']['players'][$i]['color'] = $this->getColor($player[1]);
				$this->replay['ini']['players'][$i]['faction'] = $this->getFaction($player[$this->replayType == SRR_TYPE_CnC4 ? 3 : 2]);
				$this->replay['ini']['players'][$i]['mappos'] = ($player[$this->replayType == SRR_TYPE_CnC4 ? 2 : 3] == '-1') ? 'Zufällig' : ($player[3]+1);
				$this->replay['ini']['players'][$i]['team'] = ($player[$this->replayType == SRR_TYPE_CnC4 ? 5 : 4] != -1) ? ($player[$this->replayType == SRR_TYPE_CnC4 ? 5 : 4]+1) : '-';
				$this->replay['ini']['players'][$i]['handicap'] = $this->replayType == SRR_TYPE_CnC4 ? 'n/a' : $player[5] . '%';
				$this->replay['ini']['players'][$i]['aitype'] = $this->getAIType($player[6], $player[$this->replayType == SRR_TYPE_CnC4 ? 3 : 2]);
			}
			// ----- X = No player -----
			elseif($player[0][0] == 'X')
				continue; //nothing to do, go on
			// ----- Unbekannter Typ (Unknown player type) -----
			else
				$this->replay['ini']['players'][$i]['playertype'] = '<b>UNKNOWN!</b>';
		}
		//Match type
		$this->replay['ini']['matchtype'] = $ft ? 'Automatch' : 'Eigenes Match';
	}

	/**
	 * Parses a replay from a vBulletin 4 attachment array.
	 * Loads and stores parsed information to an own table in the vB database, if enabled.
	 *
	 * @param mixed $vB4Attachment A vB4 attachment array part of the current post
	 */
	public function parseReplay(&$vB4Attachment)
	{
		//Check attachment
		if(!preg_match("/.*?\.(\w+?)Replay$/i", $vB4Attachment['filename'], $type) || !in_array($type[1], self::$supportedTypes))
			return false;
		//Set replay type
		$this->replayType = constant('SRR_TYPE_' . $type[1]);
		//Is this replay already parsed?
		if($this->configUseDB)
		{
			$this->replay = $this->vB4DB->query_first('SELECT content FROM ' . TABLE_PREFIX . 'replays WHERE replayid = ' . intval($vB4Attachment['attachmentid']));
			if(!empty($this->replay))
			{
				$this->replay = unserialize(array_shift($this->replay));
				$this->formatValues();
				return $this->replay;
			}
		}
		//Get replay data to save into file and parse
		$replayFile = tempnam($this->configTempDir, $vB4Attachment['filename']);
		file_put_contents($replayFile, array_shift($this->vB4DB->query_first('SELECT filedata.filedata FROM ' . TABLE_PREFIX . 'filedata, ' . TABLE_PREFIX . 'attachment WHERE filedata.filedataid = attachment.filedataid AND attachment.attachmentid = ' . intval($vB4Attachment['attachmentid']))));
		//Parse its contents
		$this->replay = array('file' => $vB4Attachment['filename'],
			'size' => filesize($replayFile));
		if(!$this->openReplay($replayFile))
			return false;
		//Delete file
		unlink($replayFile);
		//Save information to DB?
		if($this->configUseDB)
		{
			$toInsert = array("('" . $vB4Attachment['attachmentid'] . "'", "'" . serialize($this->replay) . "')");
			$this->vB4DB->query_insert(TABLE_PREFIX . 'replays', '(replayID, content)', $toInsert);
		}
		//Return information
		$this->formatValues();
		return $this->replay;
	}

	/**
	 * Reads a binary string until termination character.
	 *
	 * @return string Read in string
	 */
	private function readBinaryString()
	{
		$curChar = fgetc($this->fp);
		$string = '';
		while($curChar != "\x0")
		{
			$string .= $curChar;
			fseek($this->fp, 1, SEEK_CUR);
			$curChar = fgetc($this->fp);
		}
		return $string;
	}
}
?>