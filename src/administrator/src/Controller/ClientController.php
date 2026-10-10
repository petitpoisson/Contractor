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

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

/**
 * Client Form Controller
 */
class ClientController extends FormController
{
    public function save($key = null, $urlVar = null)
    {
        $task = $this->getTask();
        
        // If the task is 'save2copy', we force the state to unpublished in the request data
        if ($task === 'save2copy') {
            $data = $this->input->post->get('jform', [], 'array');
            
            $data['published'] = 0; 
            
            $this->input->post->set('jform', $data);
        }

        return parent::save($key, $urlVar);
    }

    /**
     * AJAX Task to add an invoice and upload the file instantly
     */
    public function addInvoice()
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        
        try {
            if (!Session::checkToken('request')) {
                throw new \Exception('Invalid CSRF Token. Please refresh the page.');
            }

            // Gather inputs
            $clientId = $app->input->post->getInt('client_id', 0);
            $date     = $app->input->post->getString('invoicedate', '');
            $ref      = $app->input->post->getString('reference', '');
            $link     = $app->input->post->getString('pdf_link', '');
            $file     = $app->input->files->get('invoice_file');

            // Delegate logic to the Model
            $model = $this->getModel('Client');
            $invoiceObj = $model->addInvoice($clientId, $date, $ref, $link, $file);

            echo json_encode(['success' => true, 'invoice' => $invoiceObj]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        $app->close();
    }

    /**
     * AJAX Task to delete an invoice and its file
     */
    public function deleteInvoice()
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        
        try {
            if (!Session::checkToken('request')) {
                throw new \Exception('Invalid CSRF Token.');
            }

            $id = $app->input->post->getInt('id', 0);
            
            if (!$id) {
                throw new \Exception('Invalid Invoice ID.');
            }

            // Delegate logic to the Model
            $model = $this->getModel('Client');
            $model->deleteInvoice($id);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        $app->close();
    }
    /**
     * AJAX Task to update a specific field of an invoice
     */
    public function updateInvoiceField()
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json', true);
        
        try {
            if (!Session::checkToken('request')) {
                throw new \Exception('Invalid CSRF Token.');
            }

            $id    = $app->input->post->getInt('id', 0);
            $field = $app->input->post->getString('field', '');
            $value = $app->input->post->getString('value', '');

            // Security check: only allow specific fields
            if (!$id || !in_array($field, ['invoicedate', 'reference'])) {
                throw new \Exception('Invalid parameters.');
            }

            $model = $this->getModel('Client');
            $model->updateInvoiceField($id, $field, $value);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        $app->close();
    }
}