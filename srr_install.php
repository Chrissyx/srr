<?php
/**
 * Creates "replays" table for the SAGE Replay Reader.
 *
 * @author Chrissyx <chris@chrissyx.com>
 * @copyright Script written exclusively for CnC Foren (http://www.cncforen.de/)
 * @link http://www.chrissyx.de.vu/
 * @package SRR
 */
require('./global.php');
global $db;
$ok = $db->query('CREATE TABLE IF NOT EXISTS replays
(
	`replayID` INT(10) UNSIGNED NOT NULL,
	`content` TEXT NOT NULL
)');
echo($ok ? 'Table created!' : 'Table creation FAILED :(');
?>