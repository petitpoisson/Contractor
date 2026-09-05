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

namespace XavierSpirlet\Component\Contractor\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Clients List Controller
 */
class ClientsController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array for model.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     */
    public function getModel($name = 'Client', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
    public function delete() {
        // Security check for CSRF token
        $this->checkToken();

        $pks = $this->input->post->get('cid', [], 'array');
        $userId = $this->app->getIdentity()->id;
        $clientModel = $this->getModel('Client'); 
        $contractModel = $this->getModel('Contract');

            /*
            echo("<p>Deleting client ".$cid." / ".$cn." (".$client->company_name.") who has ".count($client->contracts)." contracts and ".count($client->invoices)." invoices. </p><pre>");
            print_r($client->contracts); 
            echo("</pre><pre>");
            print_r($client->invoices); 
            die("</pre>");
            */
        foreach($pks as $pk) {
            $client = $clientModel->getItem($pk);
            $cid = $client->id;
            $cn = $client->client_name;

            foreach($client->contracts as $cont) {
                // Delete associated contracts lines
                $contractModel->deleteLinesByContract($cont->id); 
                
                // Joomla models require an array passed by reference for delete()
                $contractIdArray = [$cont->id];
                $contractModel->delete($contractIdArray); 
            }

            foreach($client->invoices as $inv) {
                // Delete invoices 
                $clientModel->deleteInvoice($inv->id); 
            }
            
            // Delete client (already logs the deletion)
            // Joomla models require an array passed by reference for delete()
            $clientIdArray = [$cid];
            $clientModel->delete($clientIdArray);
            
            // Enqueue message
            $this->app->enqueueMessage(sprintf(Text::_('COM_CONTRACTOR_CLIENT_DELETED'), $cn, $cid));
        }
        $this->setRedirect(Route::_('index.php?option=com_contractor&view=clients', false));
        return true;
    }
}

