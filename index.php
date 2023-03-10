<?php

// loading settings
require 'load.php';

if(isset($_GET['page'])) $page = $_GET['page'];
else $page = "home";

$pages = array("home" => array("Hlavní stránka"),
			   "sync" => array("Synchronizace dat"),
			   "output" => array("Výpis dat"),
			   "delete" => array("Výmaz dat"));

$menu = "";
foreach($pages as $mkey => $mvals){
	$menu .= "<li" . ($page == $mkey ? " class='active'" : "") . "><a href='/index.php?page=" . $mkey . "'>" . $mvals[0] . "</a></li>";
}

$output = "
<html>
	<head>
	  <meta charset='UTF-8'>
	  <meta name='author' content='Daniel Kužela'>
	  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
	  <title>3IT úkol</title>
	</head>
	<body>
		<style>
		body{margin:0;}
		header{margin:0;padding:0;width:100%;background:#EEE;display:flex;justify-content:center;}
		header ul{list-style-type:none;display:flex;}
		header ul li{padding:15px;list-style-type:none;display:flex;}
		header ul li.active a{font-weight:700;text-decoration:none;color:#111}
		.content{display:flex;align-items:center;justify-content:center;flex-direction:column}
		p{text-align:center}
		a{color:#614BF2}
		a:hover{text-decoration:none;color:#111}
		table{width:700px;}
		thead{background:#EEE}
		td{padding:10px;}
		tr:has(input:checked){background:#ADA2F2;}
		.sorting{margin:30px 0;background:#EEE;width:700px;display:flex;flex-direction:column;align-items:center;padding:30px;box-sizing:border-box;}
		.sorting select{padding:5px;margin:15px;}
		</style>
		<header>
			<h1>3IT Test</h1>
			<ul>
				" . $menu . "
			</ul>
			</header>
			<div class='content'>
				<h2>" . $pages[$page][0] . "</h2>";


if($page == "home"){

	$output .= "Vypracování úkolu: 90 minut.";

}
elseif($page == "sync"){

	$xml = simplexml_load_file("https://www.3it.cz/test/data/xml", 'SimpleXMLElement', LIBXML_NOCDATA);

	$query_insert = "";
	foreach($xml as $item)
	{

		$query_insert .= ", (" . DB::esc($item->ID) . ", '" . DB::esc($item->JMENO) . "', '" . DB::esc($item->PRIJMENI) . "', '" . DB::esc($item->DATE) . "')";
	
	}

	$query = DB::query("INSERT IGNORE INTO zaznamy (id_zaznamu, jmeno, prijmeni, datum) VALUES " . substr($query_insert,2));
	$aff = DB::affectedRows();

	$output .= "<p>Bylo synchronizováno " . $aff . " záznamů.";


}
elseif($page == "output"){

	$sorting = array("0" => array("Podle id ASC", "id ASC"),
					 "1" => array("Podle id DESC", "id DESC"),
					 "2" => array("Podle jména ASC", "jmeno ASC"),
					 "3" => array("Podle jména DESC", "jmeno DESC"),
					 "4" => array("Podle příjmení ASC", "prijmeni ASC"),
					 "5" => array("Podle příjmení DESC", "prijmeni DESC"),
					 "6" => array("Podle data ASC", "datum ASC"),
					 "7" => array("Podle data DESC", "datum DESC"));

	if(isset($_GET['sort'])){ $sort = $_GET['sort']; }
	else $sort = 0;

	$sort_select = "";
	foreach($sorting as $sort_key => $sort_options){
		$sort_select .= "<option value='" . $sort_key . "'" . ($sort_key == $sort ? " selected" : "") . ">" . $sort_options[0] . "</option>";
	}

	$output .= "

	<form action='/index.php?page=sort' method='GET' id='Sorting'>
	<div class='sorting'>
		Seřaďte svá data:<br />
		<select name='sort' onchange='Sorting.submit()'>" . $sort_select . "</select>
	</div>
	<input type='hidden' name='page' value='output' />
	</form>

	<table>
		<thead>
			<tr>
				<td></td>
				<td>ID záznamu</td>
				<td>Jméno</td>
				<td>Příjmení</td>
				<td>Datum</td>
		</thead>
		<tbody>";

	if(is_numeric($sort) AND isset($sorting[$sort][1])) $q_order = "ORDER BY " . DB::esc($sorting[$sort][1]);
	else $q_order = "";
	
	$query = DB::query("SELECT * FROM zaznamy " . $q_order);
	if(DB::size($query) != 0){

		while($item = DB::row($query)){

			$output .= "<tr>
				<td><input type='checkbox' value='' /></td>
				<td>" . $item['id_zaznamu'] . "</td>
				<td>" . $item['jmeno'] . "</td>
				<td>" . $item['prijmeni'] . "</td>
				<td>" . date("d. m. Y", strtotime($item['datum'])) . "</td>
			</tr>";

		}



	}
	else $output .= "<tr><td colspan='5'><p>Zatím nebyly nahrány žádné záznamy.<br />Přejděte na <a href='/index.php?page=sync'>synchronizaci dat</a>.</p></td></tr>";

}
elseif($page == "delete"){

	$query = DB::query("TRUNCATE TABLE zaznamy");

	$output .= "<p>Vaše záznamy byly vymazány.";



}


echo $output;