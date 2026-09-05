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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

/**
 * Controller for the Contract Message view
 */
class ContractmsgController extends BaseController
{
    /**
     * Task triggered by the "Cancel" button.
     */
    public function cancel()
    {
        $this->checkToken();
        $app    = Factory::getApplication();
        $return = $app->input->getString('return', 'contracts');
        $id     = $app->input->getInt('id', 0);

        if ($return === 'contract' && $id > 0) {
            $url = 'index.php?option=com_contractor&view=contract&layout=edit&id=' . $id;
        } else {
            $url = 'index.php?option=com_contractor&view=contracts';
        }

        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Task triggered by the "Send message" button.
     */
    public function send()
    {
        $this->checkToken();
        $app = Factory::getApplication();
        
        // Retrieve inputs from the form
        $id         = $app->input->getInt('id', 0);
        $returnCtx  = $app->input->getString('return', 'contracts');
        $mailTo     = $app->input->getString('mail_to', '');
        $mailFrom   = $app->input->getString('mail_from', '');
        $mailFromname = $app->input->getString('mail_from_name', '');
        $mailReply  = $app->input->getString('mail_replyto', '');
        $mailSubj   = $app->input->getString('mail_subject', '');
        $mailSource = $app->input->get('mail_source', '', 'raw');

        // Determine redirect URL based on context
        $url = ($returnCtx === 'contract' && $id > 0) 
            ? 'index.php?option=com_contractor&view=contract&layout=edit&id=' . $id 
            : 'index.php?option=com_contractor&view=contracts';

        // Basic validation
        if (empty($mailTo) || empty($mailSource)) {
            $app->enqueueMessage(Text::_('COM_CONTRACTOR_MSG_ERR_MISSING_DATA'), 'error');
            $this->setRedirect(Route::_($url, false));
            return;
        }

        // --- TAG REPLACEMENT LOGIC ---
        // Boot the model properly to retrieve the replacement dictionary
        $mvcFactory  = $app->bootComponent('com_contractor')->getMVCFactory();
        $model       = $mvcFactory->createModel('Contractmsg', 'Administrator', ['ignore_request' => true]);
        $messageData = $model->getMessageData($id);

        if ($messageData && !empty($messageData->replacements)) {
            $mailSource = str_replace(
                array_keys($messageData->replacements),
                array_values($messageData->replacements),
                $mailSource
            );
        }
        // -----------------------------

        // Fetch Client ID for logging purposes
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('client_id'))
            ->from($db->quoteName('#__ctr_contract'))
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query);
        $clientId = (int) $db->loadResult();

        // Load configuration to determine Mail Engine
        $config = ContractorHelper::getConfig();
        $engine = (int) ($config['mailengine'] ?? 0);

        try {
            // Instantiate Joomla Mailer
            $mailer = Factory::getMailer();

            // Configure Mailer based on chosen engine
            if ($engine === 1) {
                // PHP mail() / sendmail
                $mailer->isMail(); 
            } elseif ($engine === 2) {
                // Custom SMTP
                $mailer->isSMTP();
                $mailer->Host = $config['smtp_host'] ?? 'localhost';
                $mailer->Port = (int) ($config['smtp_port'] ?? 25);
                
                $security = $config['smtp_security'] ?? 'none';
                $mailer->SMTPSecure = ($security === 'none') ? '' : $security;
                
                // Evite que PHPMailer force le TLS si l'utilisateur a explicitement demandé 'none'
                $mailer->SMTPAutoTLS = ($security !== 'none');
                
                $auth = $config['smtp_auth'] ?? 'none';
                if ($auth !== 'none') {
                    $mailer->SMTPAuth = true;
                    // Options: 'LOGIN', 'PLAIN', 'CRAM-MD5' (converted to uppercase for PHPMailer)
                    $mailer->AuthType = strtoupper($auth);
                    $mailer->Username = $config['smtp_username'] ?? '';
                    $mailer->Password = $config['smtp_password'] ?? '';
                } else {
                    $mailer->SMTPAuth = false;
                }
            }

            // Prepare Email Content and Headers
            $mailer->setSender([$mailFrom, $mailfromname]);
            $mailer->addRecipient($mailTo);
            
            if (!empty($mailReply)) {
                $mailer->addReplyTo($mailReply);
            }
            
            $mailer->setSubject($mailSubj);
            $mailer->isHtml(true);
            $mailer->setBody($mailSource); // This now contains the fully replaced HTML

            // Execute Send
            $result = $mailer->Send();

            if ($result !== true) {
                throw new \Exception(Text::_('COM_CONTRACTOR_MSG_ERR_SEND_FAILED'));
            }

            // Log Success and notify user
            $logMsg = Text::sprintf('COM_CONTRACTOR_LOG_MSG_SENT', $mailTo);
            ContractorHelper::writeLog($clientId, 0, $logMsg);
            $app->enqueueMessage(Text::_('COM_CONTRACTOR_MSG_SENT_SUCCESS'), 'success');

        } catch (\Exception $e) {
            // Log Error and notify user safely
            $errorMsg = $e->getMessage();
            $logMsg = Text::sprintf('COM_CONTRACTOR_LOG_MSG_FAILED', $mailTo, $errorMsg);
            ContractorHelper::writeLog($clientId, 2, $logMsg);
            
            $app->enqueueMessage(Text::sprintf('COM_CONTRACTOR_MSG_SEND_EXCEPTION', $errorMsg), 'error');
        }

        // Return to previous view
        $this->setRedirect(Route::_($url, false));
    }
}