<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
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
 * 	\file		core/triggers/interface_99_modMyodule_Mytrigger.class.php
 * 	\ingroup	mymodule
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfaceDeclinaison
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger for update price of a declinaison.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        $this->picto = 'declinaison@declinaison';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
        $db=&$this->db;
        
        if ($action == 'PRODUCT_MODIFY') {
    		    	
			if($conf->global->DECLINAISON_NO_MODIFY_ITEM==1) {
				//var_dump($object);exit;	
				
				$sql = "SELECT fk_declinaison,ref_added";
				$sql.= " FROM ".MAIN_DB_PREFIX."declinaison";
				$sql.= " WHERE fk_parent = ".$object->id;
				
				$resql = $db->query($sql);
				
				while($res = $db->fetch_object($resql)) {
					
						$product = new Product($db);
						$product->fetch($res->fk_declinaison);
						
						$new_libelle = !empty($object->libelle) ? $object->libelle : $object->label;
						
						$product->label = $new_libelle.$res->ref_added;
						$product->libelle = $product->label;
						
						$product->description = $object->description;
						$product->note = $object->note;
						$product->weight = $object->weight;
						$product->weight_units = $object->weight_units;
						$product->length = $object->length;
						$product->length_units = $object->length_units;
						$product->surface = $object->surface;
						$product->surface_units = $object->surface_units;
						$product->volume = $object->volume;
						$product->volume_units = $object->volume_units;
						$product->customcode = $object->customcode;
						$product->country_id = $object->country_id;
				
						$product->accountancy_code_buy = $object->accountancy_code_buy;
						$product->accountancy_code_sell= $object->accountancy_code_sell;
						
						$product->array_options = $object->array_options;
						
						$product->update($product->id, $user);
				
				}
				
			}
			
			
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        } elseif ($action == 'PRODUCT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        
        } elseif ($action == 'PRODUCT_PRICE_MODIFY') {
        	/*
			 * Quand on modifie le prix du parents tous ses enfants héritent de la propriété si bouton 'maintenir à jour' coché
			 */
			
			
			$sql = "SELECT fk_declinaison, up_to_date";
			$sql.= " FROM ".MAIN_DB_PREFIX."declinaison";
			$sql.= " WHERE fk_parent = ".$object->id;
			
			$resql = $db->query($sql);
			$products = array();
			while($res = $db->fetch_object($resql)) {
				$products[$res->fk_declinaison] = $res->up_to_date;
			}

			if($resql->num_rows != 0) {
				foreach($products as $fk_declinaison => $up_to_date) {
					if($up_to_date == 1) {
						$product = new Product($db);
						$product->fetch($fk_declinaison);
						$product->updatePrice($product->id, $object->price, 'HT', $user);
					}
				}
			}
			
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }
		
		


        return 0;
    }
}