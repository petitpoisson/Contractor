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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;

class ConfigController extends FormController
{
    /**
     * Overrides cancel method to bypass check-in logic and redirect to dashboard.
     */
    public function cancel($key = null)
    {
        $this->checkToken();
        
        $this->setRedirect(Route::_('index.php?option=com_contractor&view=dashboard', false));
        return true;
    }

    /**
     * Overrides save method to handle custom saving logic without Table classes.
     */
    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app   = Factory::getApplication();
        $model = $this->getModel();
        $task  = $this->getTask();
        
        // 1. Retrieve raw data
        $data = $this->input->post->get('jform', [], 'array');

        // 2. Load form and pass data to native validator
        $form      = $model->getForm($data, false);
        $validData = $model->validate($form, $data);

        // 3. Check for validation errors
        if ($validData === false) {
            $errors = $model->getErrors();
            foreach ($errors as $error) {
                $this->setMessage($error->getMessage(), 'error');
            }
            $this->setRedirect(Route::_('index.php?option=com_contractor&view=config', false));
            return false;
        }

        // 4. Attempt to save with validated data
        if (!$model->save($validData)) {
            $this->setMessage(Text::_('JERROR_SAVE_FAILED') . ': ' . $model->getError(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_contractor&view=config', false));
            return false;
        }

        $this->setMessage(Text::_('JCONTROLLER_SAVE_SUCCESS'));

        if ($task === 'apply') {
            $this->setRedirect(Route::_('index.php?option=com_contractor&view=config', false));
        } else {
            $this->setRedirect(Route::_('index.php?option=com_contractor&view=dashboard', false));
        }

        return true;
    }

    /**
     * AJAX Task to test the Custom SMTP Connection.
     */
    /**
     * AJAX Task to test the Custom SMTP Connection.
     */
    public function testSmtp()
    {
        // 1. Nettoyage absolu du buffer pour s'assurer de ne renvoyer QUE du JSON (aucun warning PHP)
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $app = \Joomla\CMS\Factory::getApplication();

        try {
            // Absolute security: check CSRF token via AJAX
            $this->checkToken('request');
            
            // Retrieve dynamic parameters
            $host   = $app->input->getString('host', '');
            $port   = $app->input->getInt('port', 25);
            $secure = $app->input->getString('secure', 'none');
            $auth   = $app->input->getString('auth', 'login');
            $user   = $app->input->getString('user', '');
            $pass   = $app->input->getString('pass', '', 'raw');

            // Instantiate Joomla Mailer
            $mailer = \Joomla\CMS\Factory::getMailer();
            
            // Configure Mailer for SMTP test
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = $port;
            $mailer->SMTPSecure = ($secure === 'none') ? '' : $secure;
            
            // Certains serveurs forcent le TLS même si on dit 'none', on l'empêche pour le test si 'none' est choisi
            $mailer->SMTPAutoTLS = ($secure !== 'none');
            
            if ($auth !== 'none') {
                $mailer->SMTPAuth = true;
                $mailer->AuthType = strtoupper($auth);
                $mailer->Username = $user;
                $mailer->Password = $pass;
            } else {
                $mailer->SMTPAuth = false;
            }

            // Timeout très agressif pour éviter de bloquer l'UI
            $mailer->Timeout = 5;

            // Attempt connection
            if ($mailer->smtpConnect()) {
                $mailer->smtpClose();
                echo new \Joomla\CMS\Response\JsonResponse(null, \Joomla\CMS\Language\Text::_('COM_CONTRACTOR_SMTP_TEST_SUCCESS'));
            } else {
                // On récupère l'erreur exacte de PHPMailer si possible
                throw new \Exception(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_SMTP_TEST_FAILED') . ' (' . $mailer->ErrorInfo . ')');
            }
            
        } catch (\Exception $e) {
            echo new \Joomla\CMS\Response\JsonResponse(null, $e->getMessage(), true);
        }

        // 2. Arrêt immédiat pour empêcher Joomla d'ajouter son template
        exit;
    }
    /**
     * AJAX Task to reset the lastmsg field for all contracts.
     */
    public function resetDelays()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->checkToken('request');
            $model = $this->getModel();
            
            if ($model->resetWarningDelays()) {
                echo new \Joomla\CMS\Response\JsonResponse(null, \Joomla\CMS\Language\Text::_('COM_CONTRACTOR_RESET_DELAYS_SUCCESS'));
            } else {
                throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_SAVE_FAILED'));
            }
        } catch (\Exception $e) {
            echo new \Joomla\CMS\Response\JsonResponse(null, $e->getMessage(), true);
        }

        exit;
    }
}