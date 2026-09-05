<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Component\Contractor\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class TagModel extends AdminModel
{
    /**
     * Save function override to add writeLog()
     */
    public function save($data)
    {
        $isNew  = empty($data['id']);
        $result = parent::save($data);

        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;

        if ($result) {
            $tagId  = (int) $this->getState($this->getName() . '.id');
            $action = $isNew ? 'created' : 'updated';
            $desc   = "Joomla user ({$userId}) {$action} tag {$data['value']} (id {$tagId})";
            
            // clientId est null car un tag est global
            \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog(null, 0, $desc);
        } else {
            $tagIdS = $isNew ? 'NEW' : (int) $data['id'];
            $errorMsg = $this->getError();
            $errorMsg = $errorMsg ? $errorMsg : 'unknown error';
            
            \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog(
                null, 
                2, 
                "Joomla user ({$userId}) tried to save tag {$data['value']} (id {$tagId}) but it failed: {$errorMsg}"
            );
        }
        
        return $result;
    }
    public function delete(&$pks)
    {
        parent::delete($pks);        
        $pki=implode(",", $pks); 
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        ContractorHelper::writeLog(null, 1, "Joomla user ({$userId}) deleted tag(s) with id(s) {$pki}");
        return true; 
    }
    public function getTable($type = 'Tag', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        // Replaced 'client' with 'tag' to load the correct XML form
        $form = $this->loadForm('com_contractor.tag', 'tag', ['control' => 'jform', 'load_data' => $loadData]);
        return $form;
    }

    protected function loadFormData()
    {
        // Replaced 'client.data' with 'tag.data' to match the current context
        $data = $this->getState('tag.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
        }
        
        return $data;
    }

}