<?php
// TODO: bunch of if/elses need converting to switches
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nntmux\Categorize;
use nntmux\ColorCLI;
use nntmux\ConsoleTools;
use nntmux\Groups;
use nntmux\NameFixer;
use nntmux\ReleaseCleaning;
use nntmux\ReleaseFiles;
use nntmux\db\DB;

/*
 *
 * This was added because I starting writing this before
 * all of the regexes were converted to by group in ReleaseCleaning.php
 * and I do not want to convert these regexes to run per group.
 * ReleaseCleaning.php is where the regexes should go
 * so that all new releases can be effected by them
 * instead of having to run this script to rename after the
 * release has been created
 *
 */
$pdo = new DB();

if (!(isset($argv[1]) && ($argv[1] == "all" || $argv[1] == "full" || $argv[1] == "predb_id" || is_numeric($argv[1])))) {
	exit($pdo->log->error(
		"\nThis script will attempt to rename releases using regexes first from ReleaseCleaning.php and then from this file.\n"
		. "An optional last argument, show, will display the release name changes.\n\n"
		. "php $argv[0] full                    ...: To process all releases not previously renamed.\n"
		. "php $argv[0] 2                       ...: To process all releases added in the previous 2 hours not previously renamed.\n"
		. "php $argv[0] all                     ...: To process all releases.\n"
		. "php $argv[0] full 155                ...: To process all releases in groupid 155 not previously renamed.\n"
		. "php $argv[0] all 155                 ...: To process all releases in groupid 155.\n"
		. "php $argv[0] all '(155, 140)'        ...: To process all releases in group_ids 155 and 140.\n"
		. "php $argv[0] predb_id                   ...: To process all releases where not matched to predb.\n"
	));
}
preName($argv, $argc);

function preName($argv, $argc)
{
	global $pdo;
	$groups = new Groups(['Settings' => $pdo]);
	$category = new Categorize(['Settings' => $pdo]);
	$internal = $external = $pre = 0;
	$show = 2;
	if ($argv[$argc - 1] === 'show') {
		$show = 1;
	} else if ($argv[$argc - 1] === 'bad') {
		$show = 3;
	}
	$counter = 0;
	$pdo->log = new ColorCLI();
	$full = $all = $usepre = false;
	$what = $where = '';
	if ($argv[1] === 'full') {
		$full = true;
	} else if ($argv[1] === 'all') {
		$all = true;
	} else if ($argv[1] === 'predb_id') {
		$usepre = true;
	} else if (is_numeric($argv[1])) {
		$what = ' AND adddate > NOW() - INTERVAL ' . $argv[1] . ' HOUR';
	}
	if ($usepre === true) {
		$where = '';
		$why = ' WHERE predb_id = 0 AND nzbstatus = 1';
	} else if (isset($argv[1]) && is_numeric($argv[1])) {
		$where = '';
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $full === true) {
		$where = ' AND groups_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $full === true) {
		$where = ' AND groups_id IN ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $all === true) {
		$where = ' AND groups_id IN ' . $argv[2];
		$why = ' WHERE nzbstatus = 1';
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $all === true) {
		$where = ' AND groups_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 and predb_id = 0';
	} else if (isset($argv[2]) && is_numeric($argv[2])) {
		$where = ' AND groups_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if ($full === true) {
		$why = ' WHERE nzbstatus = 1 AND (isrenamed = 0 OR categories_id between 7000 AND 7999)';
	} else if ($all === true) {
		$why = ' WHERE nzbstatus = 1';
	} else {
		$why = ' WHERE 1=1';
	}
	resetSearchnames();
	echo $pdo->log->header(
		"SELECT id, name, searchname, fromname, size, groups_id, categories_id FROM releases" . $why . $what .
		$where . ";\n"
	);
	$res = $pdo->queryDirect("SELECT id, name, searchname, fromname, size, groups_id, categories_id FROM releases" . $why . $what . $where);
	$total = $res->rowCount();
	if ($total > 0) {
		$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
		foreach ($res as $row) {
			$groupname = $groups->getNameByID($row['groups_id']);
			$cleanerName = releaseCleaner($row['name'], $row['fromname'], $row['size'], $groupname, $usepre);
			$preid = 0;
			$predb = $predbfile = $increment = false;
			if (!is_array($cleanerName)) {
				$cleanName = trim((string)$cleanerName);
				$propername = $increment = true;
				if ($cleanName != '' && $cleanerName != false) {
					$run = $pdo->queryOneRow("SELECT id FROM predb WHERE title = " . $pdo->escapeString($cleanName));
					if (isset($run['id'])) {
						$preid = $run['id'];
						$predb = true;
					}
				}
			} else {
				$cleanName = trim($cleanerName["cleansubject"]);
				$propername = $cleanerName["properlynamed"];
				if (isset($cleanerName["increment"])) {
					$increment = $cleanerName["increment"];
				}
				if (isset($cleanerName["predb"])) {
					$preid = $cleanerName["predb"];
					$predb = true;
				}
			}
			if ($cleanName != '') {
				if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupname)) {
					if (preg_match('/^[0-9]{1,6}-[0-9]{1,6}-[0-9]{1,6}$/', $cleanName, $match)) {
						$rf = new ReleaseFiles($pdo);
						$files = $rf->get($row['id']);
						foreach ($files as $f) {
							if (preg_match(
								'/^(?P<title>.+?)(\\[\w\[\]\(\). -]+)?\.(pdf|htm(l)?|epub|mobi|azw|tif|doc(x)?|lit|txt|rtf|opf|fb2|prc|djvu|cb[rz])/', $f["name"],
								$match
							)
							) {
								$cleanName = $match['title'];
								break;
							}
						}
					}
				}
				//try to match clean name against predb filename
				$prefile = $pdo->queryOneRow("SELECT id, title FROM predb WHERE filename = " . $pdo->escapeString($cleanName));
				if (isset($prefile['id'])) {
					$preid = $prefile['id'];
					$cleanName = $prefile['title'];
					$predbfile = true;
					$propername = true;
				}
				if ($cleanName != $row['name'] && $cleanName != $row['searchname']) {
					if (strlen(utf8_decode($cleanName)) <= 3) {
					} else {
						$determinedcat = $category->determineCategory($row["groups_id"], $cleanName);
						if ($propername == true) {
							$pdo->queryExec(
								sprintf(
									"UPDATE releases SET videos_id = 0, tv_episodes_id = 0, imdbid = NULL, musicinfo_id = NULL, consoleinfo_id = NULL, bookinfo_id = NULL, anidbid = NULL, "
									. "iscategorized = 1, isrenamed = 1, searchname = %s, categories_id = %d, predb_id = " . $preid . " WHERE id = %d", $pdo->escapeString($cleanName), $determinedcat, $row['id']
								)
							);
						} else {
							$pdo->queryExec(
								sprintf(
									"UPDATE releases SET videos_id = 0, tv_episodes_id = 0, imdbid = NULL, musicinfo_id = NULL, consoleinfo_id = NULL, bookinfo_id = NULL, anidbid = NULL,  "
									. "iscategorized = 1, searchname = %s, categories_id = %d, predb_id = " . $preid . " WHERE id = %d", $pdo->escapeString($cleanName), $determinedcat, $row['id']
								)
							);
						}
						if ($increment === true) {
							$internal++;
						} else if ($predb === true) {
							$pre++;
						} else if ($predbfile === true) {
							$pre++;
						} else if ($propername === true) {
							$external++;
						}
						if ($show === 1) {
							$oldcatname = $category->getNameByID($row["categories_id"]);
							$newcatname = $category->getNameByID($determinedcat);

							NameFixer::echoChangedReleaseName([
									'new_name'     => $cleanName,
									'old_name'     => $row["searchname"],
									'new_category' => $newcatname,
									'old_category' => $oldcatname,
									'group'        => $groupname,
									'release_id'   => $row["id"],
									'method'       => 'misc/testing/Various/renametopre.php'
								]
							);
						}
					}
				} else if ($show === 3 && preg_match('/^\[?\d*\].+?yEnc/i', $row['name'])) {
					echo $pdo->log->primary($row['name']);
				}
			}
			if ($cleanName == $row['name']) {
				$pdo->queryExec(sprintf("UPDATE releases SET isrenamed = 1, iscategorized = 1 WHERE id = %d", $row['id']));
			}
			if ($show === 2 && $usepre === false) {
				$consoletools->overWritePrimary("Renamed Releases:  [Internal=" . number_format($internal) . "][External=" . number_format($external) . "][Predb=" . number_format($pre) . "] " . $consoletools->percentString(++$counter, $total));
			} else if ($show === 2 && $usepre === true) {
				$consoletools->overWritePrimary("Renamed Releases:  [" . number_format($pre) . "] " . $consoletools->percentString(++$counter, $total));
			}
		}
	}
	echo $pdo->log->header("\n" . number_format($pre) . " renamed using preDB Match\n" . number_format($external) . " renamed using ReleaseCleaning.php\n" . number_format($internal) . " using renametopre.php\nout of " . number_format($total) . " releases.\n");
	if (isset($argv[1]) && is_numeric($argv[1]) && !isset($argv[2])) {
		echo $pdo->log->header("Categorizing all releases using searchname from the last ${argv[1]} hours. This can take a while, be patient.");
	} else if (isset($argv[1]) && $argv[1] !== "all" && isset($argv[2]) && !is_numeric($argv[2]) && !preg_match('/\([\d, ]+\)/', $argv[2])) {
		echo $pdo->log->header("Categorizing all non-categorized releases in other->misc using searchname. This can take a while, be patient.");
	} else if (isset($argv[1]) && isset($argv[2]) && (is_numeric($argv[2]) || preg_match('/\([\d, ]+\)/', $argv[2]))) {
		echo $pdo->log->header("Categorizing all non-categorized releases in ${argv[2]} using searchname. This can take a while, be patient.");
	} else {
		echo $pdo->log->header("Categorizing all releases using searchname. This can take a while, be patient.");
	}
	$timestart = time();
	if (isset($argv[1]) && is_numeric($argv[1])) {
		$relcount = catRelease("searchname", "WHERE (iscategorized = 0 OR categories_id = 0010) AND adddate > NOW() - INTERVAL " . $argv[1] . " HOUR", true);
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $full === true) {
		$relcount = catRelease("searchname", str_replace(" AND", "WHERE", $where) . " AND iscategorized = 0 ", true);
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $all === true) {
		$relcount = catRelease("searchname", str_replace(" AND", "WHERE", $where), true);
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] == "full") {
		$relcount = catRelease("searchname", str_replace(" AND", "WHERE", $where) . " AND iscategorized = 0 ", true);
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] == "all") {
		$relcount = catRelease("searchname", str_replace(" AND", "WHERE", $where), true);
	} else if (isset($argv[1]) && $argv[1] == "full") {
		$relcount = catRelease("searchname", "WHERE categories_id = 0010 OR iscategorized = 0", true);
	} else if (isset($argv[1]) && $argv[1] == "all") {
		$relcount = catRelease("searchname", "", true);
	} else if (isset($argv[1]) && $argv[1] == "predb_id") {
		$relcount = catRelease("searchname", "WHERE predb_id = 0 AND nzbstatus = 1", true);
	} else {
		$relcount = catRelease("searchname", "WHERE (iscategorized = 0 OR categories_id = 0010) AND adddate > NOW() - INTERVAL " . $argv[1] . " HOUR", true);
	}
	$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
	$time = $consoletools->convertTime(time() - $timestart);
	echo $pdo->log->header("Finished categorizing " . number_format($relcount) . " releases in " . $time . " seconds, using the usenet subject.\n");
	resetSearchnames();
}

function resetSearchnames()
{
	global $pdo;
	echo $pdo->log->header("Resetting blank searchnames.");
	$bad = $pdo->queryDirect(
		"UPDATE releases SET videos_id = 0, tv_episodes_id = 0, imdbid = NULL, musicinfo_id = NULL, consoleinfo_id = NULL, bookinfo_id = NULL, anidbid = NULL, "
		. "predb_id = 0, searchname = name, isrenamed = 0, iscategorized = 0 WHERE searchname = ''"
	);
	$tot = $bad->rowCount();
	if ($tot > 0) {
		echo $pdo->log->primary(number_format($tot) . " Releases had no searchname.");
	}
	echo $pdo->log->header("Resetting searchnames that are 8 characters or less.");
	$run = $pdo->queryDirect(
		"UPDATE releases SET videos_id = 0, tv_episodes_id = 0, imdbid = NULL, musicinfo_id = NULL, consoleinfo_id = NULL, bookinfo_id = NULL, anidbid = NULL, "
		. "predb_id = 0, searchname = name, isrenamed = 0, iscategorized = 0 WHERE LENGTH(searchname) <= 8 AND LENGTH(name) > 8"
	);
	$total = $run->rowCount();
	if ($total > 0) {
		echo $pdo->log->primary(number_format($total) . " Releases had searchnames that were 8 characters or less.");
	}
}

// Categorizes releases.
// $type = name or searchname
// Returns the quantity of categorized releases.
function catRelease($type, $where, $echooutput = false)
{
	global $pdo;
	$cat = new Categorize(['Settings' => $pdo]);
	$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
	$relcount = 0;
	echo $pdo->log->primary("SELECT id, " . $type . ", groups_id FROM releases " . $where);
	$resrel = $pdo->queryDirect("SELECT id, " . $type . ", groups_id FROM releases " . $where);
	$total = $resrel->rowCount();
	if ($total > 0) {
		foreach ($resrel as $rowrel) {
			$catId = $cat->determineCategory($rowrel['groups_id'], $rowrel[$type]);
			$pdo->queryExec(sprintf("UPDATE releases SET iscategorized = 1, categories_id = %d WHERE id = %d", $catId, $rowrel['id']));
			$relcount++;
			if ($echooutput) {
				$consoletools->overWritePrimary("Categorizing: " . $consoletools->percentString($relcount, $total));
			}
		}
	}
	if ($echooutput !== false && $relcount > 0) {
		echo "\n";
	}
	return $relcount;
}

function releaseCleaner($subject, $fromName, $size, $groupname, $usepre)
{
	$groups = new Groups();
	$releaseCleaning = new ReleaseCleaning($groups->pdo);
	$cleanerName = $releaseCleaning->releaseCleaner($subject, $fromName, $size, $groupname, $usepre);
	if (!is_array($cleanerName) && $cleanerName != false) {
		return ["cleansubject" => $cleanerName, "properlynamed" => true, "increment" => false];
	} else {
		return $cleanerName;
	}
}
