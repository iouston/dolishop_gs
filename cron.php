<?php

/*
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more detaile.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
if (!defined('NOLOGIN')) {
    define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
    define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

/**
 *	\file       htdocs/dolishop_gs/index.php
 *	\ingroup    dolishop_gs
 *	\brief      Home page of dolishop_gs module
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

dol_include_once("/dolishop_gs/class/dolishop_gs.class.php");


$langs->load('dolishop_gs@dolishop_gs');

$dolishop_gs = new Dolishop_GS($db);
$dolishop_gs->generateCatalogue();

echo 1;

$db->close();