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
 *  \file       htdocs/dolishop_gs/class/dolishop_gs.class.php
 *  \ingroup    dolishop_gs
 *  \brief      File of class to manage slices
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

if (!empty($conf->productgallery->enabled)) {
    dol_include_once("/productgallery/class/productgallery.class.php");
}

dol_include_once('/dolishop/class/dolishop.class.php');
dol_include_once('/dolishop/class/dolishop.entity.class.php');
dol_include_once('/dolishop/class/dolishop.helper.class.php');

/**
 * Class to manage products or services
 */
class Dolishop_GS extends CommonObject
{
	public $element='dolishop_gs';
	public $table_element='dolishop_gs';
	public $fk_element='fk_dolishop_gs';
	public $picto = 'dolishop_gs@dolishop_gs';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'rowid';


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

    /**
     *  Generate XML
     *
     */
    function getCatalogueXMLPath()
    {
        global $conf, $langs, $user, $db;

        $categoryId = $conf->global->DOLISHOP_GS_SOURCE_CAT_ID;
        $path = '';


        $categorie = new Categorie($db);
        if ($categorie->fetch($categoryId) > 0) {
            $filename = dol_sanitizeFileName($categorie->label . '.xml');
            $filename = strtolower(str_replace(" ", "_", $filename));
           
            $path = '/dolishop_gs/public/' . $filename;
        }

        return $path;

    }

    /**
     *  Generate XML for goole shopping
     * Specifications → https://support.google.com/merchants/answer/7052112?hl=fr#before_you_begin&zippy=%2Cautres-exigences%2Cmettre-en-forme-vos-donn%C3%A9es-produit
     *
     */
    function generateCatalogue($id)
    {
        
        global $conf, $langs, $user, $db, $mysoc;

        $categoryId = $conf->global->DOLISHOP_GS_SOURCE_CAT_ID;
        $link = '';

        $entity = new DoliShopEntity($db);
        $entity->fetch($id);  

        $_SERVER['SERVER_NAME'] = $entity->domain;

        $dolishop = DoliShop::getInstance($db);
        //$fk_category = $dolishop->entity->fk_category; //categorie de la boutique, souvent vide à cause du menu, nécessite de parser les sous cat
        $fk_category =$categoryId;
        $categorie = DoliShopHelper::getCategory($fk_category);

        if ($categorie) {
            $path = $this->getCatalogueXMLPath();

            $realpath = dol_buildpath($path, 0);

            $products = $categorie->getObjectsInCateg('product');

            $doc = new DOMDocument();

            $catalogue = $doc->createElement('rss');
            $catalogue->setAttribute( "lang", "FR" );
            $catalogue->setAttribute( "version", "2.0" );
            $catalogue->setAttribute( "GMT", "+1" );
            $catalogue->setAttribute("date", dol_print_date(dol_now(), "%Y-%m-%d %H:%M"));
            
            $channel = $doc->createElement('channel');
            $channel->appendChild($doc->createElement('title', 'Flux XML')); 
            $channel->appendChild($doc->createElement('description', 'Un flux xml pour Google Merchant Center généré par dolibarr dolishop'));
            
            if (count($products)) {
                foreach ($products as $product) {

                    if ($product->status) {
                        $extrafields = new ExtraFields($db);
                        $extrafields->fetch_name_optionals_label($product->table_element);
                        $array_options = $product->array_options;
                        $product->load_stock();

                        $node = $doc->createElement('item');

                        //ID
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:id', $product->id));
                        
                        //Title
                        if ($product->label) {
                            $item = $doc->createElement('title');
                            $item->appendChild($doc->createCDATASection($product->label));
                            $node->appendChild($item);
                        }
                                                
                        // Description
                        if ($product->description) {
                            $item = $doc->createElement('description');
                            $item->appendChild($doc->createCDATASection($product->description));
                            $node->appendChild($item);
                        }

                        // Link
                        $item = $doc->createElementNS('http://base.google.com/ns/1.0', 'link', DoliShopHelper::route('product.show', $product));
                        $node->appendChild($item);


                        // Images
                        $images = array();
                        if (!empty($conf->productgallery->enabled)) {
                            $productgallery = new ProductGallery($db);
                            $images = $productgallery->liste_array($product->id);
                        }

                        if (count($images)) {
                            foreach ($images as $i => $image) {
                                $node_name = $i > 0 ? 'g:additional_image_link' : 'g:image_link';
                                $url = DOL_MAIN_URL_ROOT.'/viewimage.php?modulepart=medias&file='.urlencode($image->filename);
                                $item = $doc->createElementNS('http://base.google.com/ns/1.0', $node_name);
                                $item->appendChild($doc->createCDATASection($url));
                                $node->appendChild($item);
                            }
                        }    

                        //disponibilité / availability
                        $disponibilite = $product->stock_reel > 0 ? 'in_stock' : 'out_of_stock';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:availability', $disponibilite));


                        // Prix niveau 1
                        $level = 1;
                        if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
                            $prix = $product->multiprices_ttc[$level];
                        } else {
                            $prix = $product->price_ttc;
                        }
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:price', $prix.' '.$conf->currency));

                        //Prix soldé
                        if(isset($array_options['options_dolishop_prix_barre'])){
                        $prix_barre = isset($array_options['options_dolishop_prix_barre']) ? $array_options['options_dolishop_prix_barre'] : '';
                        $prix_barre = price2num($prix_barre);
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:sale_price', $prix_barre.' '.$conf->currency));    
                        }
                        

                        //Début promotion
                        $debut_promo = isset($array_options['options_dolishop_debut_promo']) ? $array_options['options_dolishop_debut_promo'] : '';
                        if ($debut_promo) {
                            $item = $doc->createElement('sale_price_effective_date');
                            $debut = dol_print_date($debut_promo, '%Y-%m-%dT%H:%M+0100');                           
                        }
                        //Fin promotion
                        $fin_promo = isset($array_options['options_dolishop_fin_promo']) ? $array_options['options_dolishop_fin_promo'] : '';
                        if ($fin_promo) {
                            $fin->appendChild($doc->createCDATASection(dol_print_date($fin_promo, '%Y-%m-%dT%H:%M+0100')));
                        }

                        $item->appendChild($doc->createCDATASection($debut.' / '.$fin));
                        $node->appendChild($item);

                        

                        //  $node->appendChild($doc->createElement('ecotaxe', 0));

                        //categorie -> voir ce que google attend pour ce point
                        //$categories = $categorie->getListForItem($product->id, 'product');
                        // if (count($categories)) {
                        //     $cats = array();
                        //     foreach ($categories as $i => $category) {
                        //         if ($category['id'] == $categoryId) {
                        //             unset($categories[$i]);
                        //         } else {
                        //             $cats[] = $category;
                        //         }
                        //     }

                        //     for ($i = 0; $i < min(3, count($cats)); $i++) {
                        //         $cat = $cats[$i];
                        //         $node_name = sprintf('categorie%d', $i+1);
                        //         $item = $doc->createElement($node_name);
                        //         $item->appendChild($doc->createCDATASection($cat['label']));
                        //         $node->appendChild($item);
                        //     }
                        // }
                       
                        //Type de produit -> revoir ce que google attend pour ce point
                    
                        
                        //Marque
                        $value = isset($array_options['options_dolishop_marque']) ? $array_options['options_dolishop_marque'] : '';
                        $brand = $extrafields->showOutputField('dolishop_marque', $value, '', $product->table_element);
                        if ($brand) {
                            $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:brand', $brand)); 
                        }
                       
                        //GTIN (ean, code barre)
                        if(isset($product->barcode)){
                        $gtin = isset($product->barcode) ? $product->barcode : '';              
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:gtin', $product->barcode));    
                        }
                        
                        //mpn (ean, code barre)
                        /**
                        * Utilisez seulement les références fabricant attribuées par un fabricant.
                        * Utilisez la référence fabricant la plus spécifique possible.
                        * Par exemple, des produits déclinés dans plusieurs couleurs doivent avoir des références fabricant différentes.
                        * Associez la référence fabricant correcte à vos produits (lorsqu'elle est obligatoire) pour garantir une expérience utilisateur et des performances optimales.
                        * Ne fournissez la référence fabricant que si vous la connaissez. En cas de doute, ne spécifiez pas cet attribut (par exemple, ne fournissez pas de valeur approximative ou devinée).
                        * Si vous envoyez un produit dont la référence fabricant est incorrecte, il sera refusé.**/

                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:mpn', 'unknown')); 
                        
                        //identifier_exists
                        /**
                         * Indique si les codes produit uniques (CPU), le code GTIN, la référence fabricant et la marque sont disponibles pour votre produit
                         */

                          $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:identifier_exists', 'no')); // Forcé à no pour le moment, car pas mpn pour le moment

                         //Etat du produit
                        $condition = isset($array_options['options_dolishop_condition']) ? $array_options['options_dolishop_condition'] : 'new';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:condition', $condition));


                        //Réservé aux adultes
                        $adult_only = isset($array_options['options_dolishop_adult_only']) ? 'yes' : 'no';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:adult', $adult_only));

                        //Est ce que c'est un lot ?
                        $is_bundle = isset($array_options['options_dolishop_is_bundle']) ? 'yes' : 'no';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:is_bundle', $is_bundle));

                        //Multipack
                        // Nombre de produits dans le lot (par exemple pack de 6)
                        if(isset($array_options['options_dolishop_multipack']) && $is_bundle=='yes'){
                        $multipack = isset($array_options['options_dolishop_multipack']) ? $array_options['options_dolishop_multipack'] : '';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:multipack', $multipack));    
                        }
                        

                        //Tranche d'âge
                        /**
                         * Obligatoire pour les vetements et accessoires
                         */
                        if(isset($array_options['options_dolishop_age_group'])){
                        $age_group = isset($array_options['options_dolishop_age_group']) ? $array_options['options_dolishop_age_group'] : '';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:age_group', $age_group)); 
                        }    

                        //Color
                        $value = isset($array_options['options_dolishop_couleur']) ? $array_options['options_dolishop_couleur'] : '';
                        $couleurs = $extrafields->showOutputField('dolishop_couleur', $value, '', $product->table_element);
                        if ($couleurs) {
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:color', $couleurs));     
                        }
                        
                        //Gender
                        if(isset($array_options['options_dolishop_gender'])){
                        $gender = isset($array_options['options_dolishop_gender']) ? $array_options['options_dolishop_gender'] : '';
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:gender', $gender)); 
                        }
                        

                        //Material
                        // A faire : Pour indiquer plusieurs matières pour un seul produit (pas de variantes), ajoutez une matière principale suivie de deux matières secondaires au maximum, séparées par une barre oblique /.
                        // Par exemple, indiquez "coton/polyester/élasthanne" au lieu de cotonpolyesterelesthane
                        if(isset($array_options['options_dolishop_material'])){
                        $material = isset($array_options['options_dolishop_material']) ? $array_options['options_dolishop_material'] : '';
                        $material= str_replace(', ',',',$material);
                        $material= str_replace(',','/',$material);
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:material', $material));    
                        }
                        
                        //item_group_id
                        /**
                         * Identifiant d'un groupe de produits disponibles en plusieurs versions (variantes)
                         * Utilisez une valeur unique pour chaque groupe de variantes. Utilisez, si possible, le SKU parent.
                         * Conservez la même valeur lorsque vous mettez à jour vos données produit.
                         */
                        $parentid = DoliShopHelper::getParentProductId($product);
                        if($parentid>0){
                        $item_group_id = !empty($parentid) ? $parentid : '';     
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:item_group_id', $item_group_id));    
                        }
                        
                        //Dimensions et mesures du produit
                        //lenght
                        if(isset($product->length)){            
                        $length = floatval($product->length)/(10^intval($product->length_units));
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:product_length', $length.' cm'));
                        }

                        //width
                        if(isset($product->width)){
                        $width = floatval($product->width)/(10^intval($product->width_units));
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:product_width', $width.' cm'));
                        }

                        //height
                        if(isset($product->height)){
                        $height = floatval($product->height)/(10^intval($product->height_units));
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:product_height', $height.' cm'));
                        }

                        //weight
                        if(isset($product->weight)){
                        $weight = floatval($product->weight)/(10^intval($product->weight_units));
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:product_weight', $weight.' cm'));
                        }

                        //external_seller_id
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:external_seller_id', $shop->name));              

                        //shipping
                        //A intégrer, pas très clair dans la doc google
                        
                        //shipping_weight
                        $shipping_weight = floatval($product->weight)/(10^intval($product->weight_units));
                        $node->appendChild($doc->createElement('shipping_weight', $weight.' kg'));

                        //shipping_lenght
                        //shipping_width
                        //shipping_height

                        //Ships_from_country
                        $ships_from_country = $mysoc->country_code; //TODO, prévoir une option dans la boutique
                        $node->appendChild($doc->createElementNS('http://base.google.com/ns/1.0', 'g:ships_from_country', $ships_from_country));
                        
                        // $country_id = $product->country_id;
                        // $country = $country_id > 0 ? dol_getIdFromCode($db, $country_id, 'c_country', 'rowid', 'label') : '';
                        // if ($country) {
                        //     $item = $doc->createElement('pays_fabrication');
                        //     $item->appendChild($doc->createCDATASection($country));
                        //     $node->appendChild($item);
                        // }

                        // // frais_de_port
                        // $frais_de_port = isset($array_options['options_dolishop_frais_de_port']) ? $array_options['options_dolishop_frais_de_port'] : '';
                        // $node->appendChild($doc->createElement('frais_de_port', $frais_de_port));

                        // // delai_de_livraison
                        // $delai_de_livraison = isset($array_options['options_dolishop_delai_de_livraison']) ? $array_options['options_dolishop_delai_de_livraison'] : '';
                        // $node->appendChild($doc->createElement('delai_de_livraison', $delai_de_livraison));

                        // $value = isset($array_options['options_dolishop_type']) ? $array_options['options_dolishop_type'] : '';
                        // $type = $extrafields->showOutputField('dolishop_type', $value, '', $product->table_element);
                        // if ($type) {
                        //     $item = $doc->createElement('type');
                        //     $item->appendChild($doc->createCDATASection($type));
                        //     $node->appendChild($item);
                        // }

                        // $frais_de_port = isset($array_options['options_dolishop_frais_de_port']) ? $array_options['options_dolishop_frais_de_port'] : 0;
                        // $frais_de_port = price2num($frais_de_port);
                        // $node->appendChild($doc->createElement('frais_de_port', $frais_de_port));

                        // $delai_de_livraison = isset($array_options['options_dolishop_delai_de_livraison']) ? $array_options['options_dolishop_delai_de_livraison'] : 0;
                        // $node->appendChild($doc->createElement('delai_de_livraison', $delai_de_livraison));


                        // $taille_shaft = isset($array_options['options_dolishop_taille_shaft']) ? $array_options['options_dolishop_taille_shaft'] : '';
                        // if ($taille_shaft) {
                        //     $item = $doc->createElement('taille_shaft');
                        //     $item->appendChild($doc->createCDATASection($taille_shaft));
                        //     $node->appendChild($item);
                        // }

                        // $forme = isset($array_options['options_dolishop_forme']) ? $array_options['options_dolishop_forme'] : '';
                        // if ($forme) {
                        //     $item = $doc->createElement('forme');
                        //     $item->appendChild($doc->createCDATASection($forme));
                        //     $node->appendChild($item);
                        // }

                        
                        $channel->appendChild($node);
                        $catalogue->appendChild($channel);
                    }
                    
                }
            }

            $doc->appendChild($catalogue);
            $doc->save($realpath);
            $link = dol_buildpath($path, 3);
        }

        return 0;
    }
}
