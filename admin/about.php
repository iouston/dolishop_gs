<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/dolishop_gs/index.php
 *  \ingroup    dolishop_gs
 *  \brief      Page to show product set
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once("/dolishop_gs/lib/dolishop_gs.lib.php");

// Translations
$langs->load("dolishop_gs@dolishop_gs");

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) {
	accessforbidden();
}

$versions = array(
    array('version' => '1.0.0', 'date' => '23/11/2023', 'updates' => $langs->trans('Dolishop_GSFirstVersion')),
);
/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('Dolishop_GSAbout'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('Dolishop_GSAbout'), $linkback);

// Configuration header
$head = dolishop_gs_prepare_admin_head();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("ModuleDolishop_GSName"),
	0,
	'dolishop_gs@dolishop_gs'
);

// About page goes here
echo $langs->trans("Dolishop_GSAboutPage");

echo '<br />';

$url = 'http://www.iouston.com/systeme-gestion-entreprise-dolibarr/modules-dolibarr/module-dolibarr-dolishop_gs';

print '<h2>'.$langs->trans("About").'</h2>';
print $langs->transnoentities("Dolishop_GSAboutDescLong", $url, $url);

print '<h2>'.$langs->trans("MaintenanceAndSupportTitle").'</h2>';
print $langs->transnoentities("MaintenanceAndSupportDescLong");

print '<h2>'.$langs->trans("UpdateTitle").'</h2>';
print $langs->transnoentities("UpdateDescLong");

print '<h2>'.$langs->trans("ModulesTitle").'</h2>';
print $langs->transnoentities("ModulesDescLong");

echo '<br />';

print '<a href="http://www.dolistore.com">'.img_picto('dolistore', dol_buildpath('/dolishop_gs/img/dolistore.png', 1), '', 1).'</a>';

print '<hr />';

print '<a href="http://www.iouston.com">'.img_picto('iouston', dol_buildpath('/dolishop_gs/img/iouston.png', 1), '', 1).'</a>';

echo '<br />';

print $langs->trans("IoustonDesc");

print '<hr />';
print '<h2>'.$langs->trans("Dolishop_GSChangeLog").'</h2>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Dolishop_GSChangeLogVersion").'</td>';
print '<td>'.$langs->trans("Dolishop_GSChangeLogDate").'</td>';
print '<td>'.$langs->trans("Dolishop_GSChangeLogUpdates").'</td>';
print "</tr>\n";

foreach ($versions as $version)
{
	print '<tr class="oddeven">';
	print '<td>';
	print $version['version'];
	print '</td>';
	print '<td>';
	print $version['date'];
	print '</td>';
	print '<td>';
	print $version['updates'];
	print '</td>';
	print '</tr>';
}


print '</table>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();
