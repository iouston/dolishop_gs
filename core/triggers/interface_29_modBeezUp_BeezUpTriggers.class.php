<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modDolishop_GS_Dolishop_GSTriggers.class.php
 * \ingroup dolishop_gs
 * \brief   Example trigger.
 *
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

dol_include_once("/dolishop_gs/class/dolishop_gs.class.php");

/**
 *  Class of triggers for Dolishop_GS module
 */
class InterfaceDolishop_GSTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "crm";
        $this->description = "Dolishop_GS triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = '1.0.0';
        $this->picto = 'dolishop_gs@dolishop_gs';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string $action Event action code
     * @param CommonObject $object Object
     * @param User $user Object user
     * @param Translate $langs Object langs
     * @param Conf $conf Object conf
     * @return int                    <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {

        if (empty($conf->dolishop_gs->enabled)) return 0;     // Module not active, we do nothing

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        $langs->load("other");

        switch ($action) {
            case 'ORDER_CANCEL':
                $dolishop_gs = new Dolishop_GS($this->db);
                $dolishop_gs->cancelOrder($object->id);
                break;
            case 'ORDER_CLOSE':
                $dolishop_gs = new Dolishop_GS($this->db);
                $dolishop_gs->closeOrder($object->id);
                break;
            case 'PRODUCT_MODIFY':
            case 'PRODUCT_CREATE':
            case 'PRODUCT_DELETE':
            case 'CATEGORY_LINK':
            case 'CATEGORY_UNLINK':
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                $dolishop_gs = new Dolishop_GS($this->db);

                $generateCatalogue = false;
                if ($action == 'CATEGORY_LINK' || $action == 'CATEGORY_UNLINK') {
                    $generateCatalogue = $object->id == $conf->global->BEEZUP_SOURCE_CAT_ID;
                } else {
                    $categorie = new Categorie($this->db);
                    $categories = $categorie->getListForItem($object->id, 'product');
                    if (is_array($categories) && count($categories)) {
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $conf->global->BEEZUP_SOURCE_CAT_ID) {
                                // $generateCatalogue = true;
                            }
                        }
                    }
                }

                if ($generateCatalogue) {
                    $dolishop_gs->generateCatalogue();
                }

                break;
        }

        return 0;
    }
}
