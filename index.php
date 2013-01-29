<?php
/**
 *	VisonoPadLister
 *	_______________
 *	@version 1.2
 *	@author René Woltmann
 *	@copyright Visono GmbH 2012
 *
 *	Dieses kleine Script listet alle bestehenden Pads auf.
 *	Es verbindet sich mit der SQLite Datenbank, die auch das Etherpad benutzt.
 *	In der Tabelle gibt es einen Link, der die Api anspricht und ein Pad mit der betreffenden ID löscht.
 */

//let me know, what's wrong!
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('html_errors', 1);

//some important information
$dsn = 'mysql://root:passwd@localhost/etherpad';

require_once('Pad.php');
require_once 'MDB2.php';


/**
 * helper function
 * gets an order string for sorting two pad objects
 * orders by title as default
 */
function comp($a, $b){
	$orderBy = isset($_REQUEST['order'])? $_REQUEST['order'] : 'alpha';
	$sortValueA = null;
	$sortValueB = null;
	switch($orderBy){
		case 'num':{
			$sortValueA = strtotime($a->lastEdit);
			$sortValueB = strtotime($b->lastEdit);
		}break;
		case 'user':{
			$sortValueA = $a->authorsCount;
			$sortValueB = $b->authorsCount;
		}break;
		default:{
			$sortValueA = strtolower($b->id);
			$sortValueB = strtolower($a->id);
		}break;
	}
	if($sortValueA === $sortValueB){
		return 0;
	}
	return $sortValueA < $sortValueB? 1 : -1;
}

//needed vars
$pads = array();
$padKeys = array();

//connection to db
$mdb2 =& MDB2::singleton($dsn);
if (PEAR::isError($mdb2)) {
	die($mdb2->getMessage());
}

//grab all keys from db
$results =& $mdb2->query('Select distinct substr(store.key,5,1000) as pad from store where store.key like "pad:%"');

if (PEAR::isError($results)) {
	die($results->getMessage());
}

while (($row = $results->fetchRow())) {
	$piece = explode(':', $row[0]);
	$padKeys[$piece[0]] = $piece[0];
}

foreach($padKeys as $key){
	$pads[] = new Pad($key, $mdb2);
}

$mdb2->disconnect();

//order the pads
usort($pads, "comp");


//done. let's display it the oldschool way :)
?>
<!doctype html>
<html>
	<head>
	<meta charset="UTF-8">
	<title>VisonoPadLister</title>
	<script src="jquery.js"></script>
	<script src="pad.js"></script>
	<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
		<h1>VisonoPadOverview</h1>
		<p>
			<a id="addpad">Neues Pad erstellen</a> <a target="_blank" id="silentLink"></a>
		</p>
		<p id="order">
			Sortieren: 
			<a href="<?php $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ?>?order=num">letzte Änderung</a> | 
			<a href="<?php $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ?>?order=alpha">alphabetisch</a> | 
			<a href="<?php $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ?>?order=user">aktive User</a>
		</p>
		<table>
			<tr>
				<th>Name</th>
				<th>Letzte Änderung</th>
				<th>Aktive User</th>
				<th>Autoren</th>
				<th>Revisionen</th>
				<th>Tools</th>
			</tr>
			<?php foreach($pads as $pad): ?>
				<tr id="tr<?php $pad->id ?>">
					<td>
						<a href="<?php $pad->viewUrl ?>" target="_blank"><?php $pad->id ?></a>
					</td>
					<td>
						<?php $pad->lastEdit ?>
					</td>
					<td>
						<?php $pad->authorsCount ?>
					</td>
					<td>
						<ul>
							<?php foreach($pad->authors as $author): ?>
								<li><?php $author ?></li>
							<?php endforeach ?>
		
						</ul>
					</td>
					<td>
						<?php $pad->revisions ?>
					</td>
					<td>
						<a name="<?php $pad ?>" title='Pad "<?php $pad ?>" löschen' class="deletor" href="<?php $pad->deleteUrl ?>"> 
							<img src="trash.png" alt="[X]" />
						</a>
					</td>
				</tr>
			<?php endforeach ?>
		</table>
	</body>
</html>
