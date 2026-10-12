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

    public function send()
    {
        $this->processEmail(false);
    }

    public function sendTest()
    {
        $this->processEmail(true);
    }

    /**
     * Traite l'envoi de l'e-mail (Normal ou Test)
     */
    private function processEmail(bool $isTest)
    {
        $this->checkToken();
        $app = Factory::getApplication();
        
        $id           = $app->input->getInt('id', 0);
        $returnCtx    = $app->input->getString('return', 'contracts');
        $mailFrom     = $app->input->getString('mail_from', '');
        $mailFromname = $app->input->getString('mail_from_name', '');
        $mailReply    = $app->input->getString('mail_replyto', '');
        $mailSubj     = $app->input->getString('mail_subject', '');
        $mailSource   = $app->input->get('mail_source', '', 'raw');

        // Configuration selon le type d'envoi
        if ($isTest) {
            $mailTo = $app->input->getString('test_email', '');
            $mailSubj = '[TEST] ' . $mailSubj;
            // On reste sur la même page après un test
            $url = 'index.php?option=com_contractor&view=contractmsg&id=' . $id . '&return=' . $returnCtx;
        } else {
            $mailTo = $app->input->getString('mail_to', '');
            // On redirige vers la liste ou le contrat après un envoi réel
            $url = ($returnCtx === 'contract' && $id > 0) 
                ? 'index.php?option=com_contractor&view=contract&layout=edit&id=' . $id 
                : 'index.php?option=com_contractor&view=contracts';
        }

        if (empty($mailTo) || empty($mailSource)) {
            $app->enqueueMessage(Text::_('COM_CONTRACTOR_MSG_ERR_MISSING_DATA'), 'error');
            $this->setRedirect(Route::_($url, false));
            return;
        }

        // --- TAG REPLACEMENT LOGIC ---
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

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('client_id'))
            ->from($db->quoteName('#__ctr_contract'))
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query);
        $clientId = (int) $db->loadResult();

        $config = ContractorHelper::getConfig();
        $engine = (int) ($config['mailengine'] ?? 0);

        try {
            $mailer = Factory::getMailer();

            if ($engine === 1) {
                $mailer->isMail(); 
            } elseif ($engine === 2) {
                $mailer->isSMTP();
                $mailer->Host = $config['smtp_host'] ?? 'localhost';
                $mailer->Port = (int) ($config['smtp_port'] ?? 25);
                
                $security = $config['smtp_security'] ?? 'none';
                $mailer->SMTPSecure = ($security === 'none') ? '' : $security;
                $mailer->SMTPAutoTLS = ($security !== 'none');
                
                $auth = $config['smtp_auth'] ?? 'none';
                if ($auth !== 'none') {
                    $mailer->SMTPAuth = true;
                    $mailer->AuthType = strtoupper($auth);
                    $mailer->Username = $config['smtp_username'] ?? '';
                    $mailer->Password = $config['smtp_password'] ?? '';
                } else {
                    $mailer->SMTPAuth = false;
                }
            }

            $mailer->setSender([$mailFrom, $mailFromname]);
            $mailer->addRecipient($mailTo);
            
            if (!empty($mailReply)) {
                $mailer->addReplyTo($mailReply);
            }
            
            $mailer->setSubject($mailSubj);
            $mailer->isHtml(true);

            $htmlBody = '<!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . htmlspecialchars($mailSubj) . '</title>
            </head>
            <body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333333; margin: 0; padding: 20px;">' 
            . $mailSource . 
            '</body>
            </html>';
            
            $mailer->setBody($mailSource); 

            $result = $mailer->Send();

            if ($result !== true) {
                throw new \Exception(Text::_('COM_CONTRACTOR_MSG_ERR_SEND_FAILED'));
            }

            if ($isTest) {
                // Message d'alerte spécifique pour le test (sans l'enregistrer dans les logs du client)
                $app->enqueueMessage("Message de test envoyé avec succès à {$mailTo}.", 'success');
            } else {
                $logMsg = Text::sprintf('COM_CONTRACTOR_LOG_MSG_SENT', $mailTo);
                ContractorHelper::writeLog($clientId, 0, $logMsg);
                $app->enqueueMessage(Text::_('COM_CONTRACTOR_MSG_SENT_SUCCESS'), 'success');
            }

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $app->enqueueMessage(Text::sprintf('COM_CONTRACTOR_MSG_SEND_EXCEPTION', $errorMsg), 'error');
            
            if (!$isTest) {
                $logMsg = Text::sprintf('COM_CONTRACTOR_LOG_MSG_FAILED', $mailTo, $errorMsg);
                ContractorHelper::writeLog($clientId, 2, $logMsg);
            }
        }

        $this->setRedirect(Route::_($url, false));
    }
}