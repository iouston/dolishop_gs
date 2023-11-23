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
 *  \file       htdocs/dolishop_gs/admin/setup.php
 *  \ingroup    dolishop_gs
 *  \brief      Admin page
 */

$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';

dol_include_once("/dolishop_gs/lib/dolishop_gs.lib.php");
dol_include_once("/dolishop_gs/class/dolishop_gs.class.php");

// Translations
$langs->load("dolishop_gs@dolishop_gs");
$langs->load("admin");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

$fk_categorie = GETPOST('fk_categorie', 'int');
$rang = GETPOST('rang', 'int');

$reg = array();

/*
 * Actions
 */
$dolishop_gs = new Dolishop_GS($db);

$error=0;

// Action mise a jour ou ajout d'une constante
if ($action == 'update')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));


	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res = dolibarr_set_const($db,$constname,$constvalue,'chaine',0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'del_DOLISHOP_GS_PUSH_NOTIFICATIONS') {
    $res = dolibarr_set_const($db,'DOLISHOP_GS_PUSH_NOTIFICATIONS',0,'chaine',0,'',$conf->entity);
    $dolishop_gs->deactivateSubscription($conf->global->DOLISHOP_GS_SUBSCRIPTION_ID);
} else if ($action == 'set_DOLISHOP_GS_PUSH_NOTIFICATIONS') {
    if (!empty($conf->global->DOLISHOP_GS_SUBSCRIPTION_ID)) {
        if ($dolishop_gs->activateSubscription($conf->global->DOLISHOP_GS_SUBSCRIPTION_ID) !== false) {
            dolibarr_set_const($db,'DOLISHOP_GS_PUSH_NOTIFICATIONS',1,'chaine',0,'',$conf->entity);
        }
    }

    if (empty($conf->global->DOLISHOP_GS_PUSH_NOTIFICATIONS)) {
        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        if ($dolishop_gs->createSubscription($id) !== false) {
            dolibarr_set_const($db,'DOLISHOP_GS_SUBSCRIPTION_ID',$id,'chaine',0,'',$conf->entity);
            if ($dolishop_gs->activateSubscription($id) !== false) {
                dolibarr_set_const($db,'DOLISHOP_GS_PUSH_NOTIFICATIONS',1,'chaine',0,'',$conf->entity);
            }
        }
    }
} else if ($action == 'build') {
    $dolishop_gs->generateCatalogue();
}

/*
 * View
 */

llxHeader('', $langs->trans('Dolishop_GSSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('Dolishop_GSSetup'), $linkback);

// Configuration header
$head = dolishop_gs_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleDolishop_GSName"),
	0,
	"dolishop_gs@dolishop_gs"
);

$form = new Form($db);
$formproduct = new FormProduct($db);

// Setup page goes here
print load_fiche_titre($langs->trans("Dolishop_GSOptions"),'','');


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td colspan="2">';
$text = $langs->trans("Value");
print $form->textwithpicto($text, "", 1, 'help', '', 0, 2, 'idhelptext');
print '</td>';
print "</tr>\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<tr class="oddeven">';
print '<td>';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="DOLISHOP_GS_SOURCE_CAT_ID">';
print '<input type="hidden" name="constnote" value="">';
print $langs->trans('DescDOLISHOP_GS_SOURCE_CAT_ID');
print '</td>';
print '<td>';
print $form->select_all_categories('product', $conf->global->DOLISHOP_GS_SOURCE_CAT_ID, 'constvalue');
print '<input type="hidden" name="consttype" value="chaine">';
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</td>';
print '</tr>';
print '</form>';

$path = $dolishop_gs->getCatalogueXMLPath();

if (file_exists(dol_buildpath($path, 0))) {
    $link = dol_buildpath($path, 3);

    print '<tr class="oddeven">';
    print '<td>';
    print $langs->trans('Dolishop_GSXmlLink');
    print '</td>';
    print '<td colspan="2">';
    print '<a href="'.$link.'">'.$link.'</a>';
    print '</td>';
}

print '</table>';

// Page end
dol_fiche_end();

print '<div class="center">';
if ($conf->global->DOLISHOP_GS_SOURCE_CAT_ID > 0) {
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=build&token='.newToken().'" class="button">'.$langs->trans("GenerateCatalogue").'</a>';
}
print '</div>';
llxFooter();
