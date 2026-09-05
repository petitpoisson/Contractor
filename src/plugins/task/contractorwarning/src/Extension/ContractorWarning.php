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

\defined('_JEXEC') or die;

namespace XavierSpirlet\Plugin\Task\ContractorWarning\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

/**
 * Task plugin to check expiring contracts.
 */
final class ContractorWarning extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use TaskPluginTrait;

    protected $autoloadLanguage = true;

    private const TASKS_MAP = [
        'contractorwarning.check' => [
            'langConstPrefix' => 'PLG_TASK_CONTRACTORWARNING_CHECK',
            'method'          => 'checkExpiring',
        ],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
        ];
    }

    private function checkExpiring(ExecuteTaskEvent $event): int
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_contractor/src/Helper/ContractorHelper.php';
        
        // Check if this is manual execution
        $app = Factory::getApplication();
        $isManual = ($app->isClient('administrator') && $app->input->getCmd('option') === 'com_scheduler');

        $config   = ContractorHelper::getConfig();
        $warnDays = (int) ($config['warn_days'] ?? 30);
        $warnWhen = (int) ($config['warn_when'] ?? 0);
        $warnMail = $config['warn_mail'] ?? '';

        // Return if no e-mail is configured
        if (empty($warnMail)) {
            $this->logTask('Contractor warnings email is missing.', 'info');
            return Status::OK;
        }

        // Cancel if alerts are set to never (warnWhen = 0), EXCEPT if this is a manual execution
        if (!$isManual && $warnWhen === 0) {
            $this->logTask('Contractor warnings are disabled by configuration.', 'info');
            return Status::OK;
        }

        $db = $this->getDatabase();
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $query = $db->getQuery(true)
            ->select(['a.id', 'a.client_id', 'a.name', 'a.valid_thru', 'a.lastmsg', 'c.client_name'])
            ->from($db->quoteName('#__ctr_contract', 'a'))
            ->join('LEFT', $db->quoteName('#__ctr_client', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.client_id'))
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.valid_thru') . ' IS NOT NULL')
            ->where($db->quoteName('a.valid_thru') . ' != ' . $db->quote('0000-00-00 00:00:00'))
            ->where($db->quoteName('a.valid_thru') . ' <= DATE_ADD(CURRENT_DATE, INTERVAL ' . $warnDays . ' DAY)');

        $db->setQuery($query);
        $contracts = $db->loadObjectList();

        $sentCount = 0;
        $mailer = Factory::getMailer();

        // --- CONFIGURE MAILER ENGINE ONCE ---
        $engine = (int) ($config['mailengine'] ?? 0);

        if ($engine === 1) {
            $mailer->isMail(); 
        } elseif ($engine === 2) {
            $auth   = ($config['smtp_auth'] ?? 'none') !== 'none';
            $host   = $config['smtp_host'] ?? 'localhost';
            $user   = $config['smtp_username'] ?? '';
            $pass   = $config['smtp_password'] ?? '';
            $secure = $config['smtp_security'] ?? 'none';
            $secure = ($secure === 'none') ? '' : $secure; // Joomla attend une chaîne vide pour "aucun"
            $port   = (int) ($config['smtp_port'] ?? 25);
            
            // Utilisation de la méthode native Joomla
            $mailer->useSmtp($auth, $host, $user, $pass, $secure, $port);
        }
        // ------------------------------------

        $rootUrl = rtrim(Uri::root(), '/');

        foreach ($contracts as $contract) {
            $shouldSend = false;
            $skipReason = ''; 

            if ($isManual) {
                $shouldSend = true;
                $this->logTask("Manual override: forcing email for contract ID {$contract->id}", 'debug');
            } elseif (empty($contract->lastmsg) || $contract->lastmsg === '0000-00-00 00:00:00') {
                $shouldSend = true;
            } else {
                if ($warnWhen === 9) { 
                    $skipReason = "Warning already sent once (rule: only once).";
                } else {
                    $lastMsgDate = new \DateTime($contract->lastmsg);
                    $diff = $today->diff($lastMsgDate)->days;
                    if ($diff >= $warnWhen) {
                        $shouldSend = true;
                    } else {
                        $skipReason = "Warning sent recently ({$diff} days ago). Waiting for {$warnWhen} days rule.";
                    }
                }
            }

            if ($shouldSend) {
                $validDate = clone $today;
                $validDate->modify($contract->valid_thru);
                $daysLeft = (int) $today->diff($validDate)->format('%R%a');
                
                $isExpired = ($daysLeft < 0);
                
                if ($isExpired) {
                    $statusText  = Text::_('PLG_TASK_CONTRACTORWARNING_STATUS_EXPIRED');
                    $statusColor = '#dc3545'; 
                    $icon        = '🔴';
                } else {
                    $statusText  = Text::_('PLG_TASK_CONTRACTORWARNING_STATUS_WARNING');
                    $statusColor = '#ffc107'; 
                    $icon        = '🟠';
                }

                $formattedDate = Factory::getDate($contract->valid_thru)->format('d/m/Y');
                $editUrl       = $rootUrl . '/administrator/index.php?option=com_contractor&view=contract&layout=edit&id=' . (int) $contract->id;
                
                $subject = Text::sprintf('PLG_TASK_CONTRACTORWARNING_EMAIL_SUBJECT', $contract->name);
                
                $html  = '<div style="font-family: Helvetica, Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 8px; overflow: hidden; background-color: #fdfdfd;">';
                $html .= '<div style="background-color: #f8f9fc; padding: 15px 20px; border-bottom: 1px solid #e3e6f0;">';
                $html .= '<h2 style="margin: 0; font-size: 18px; color: #4e73df;">' . Text::_('PLG_TASK_CONTRACTORWARNING_EMAIL_TITLE') . '</h2>';
                $html .= '</div>';
                $html .= '<div style="padding: 25px 20px;">';
                $html .= '<p style="margin-top: 0;">' . Text::sprintf('PLG_TASK_CONTRACTORWARNING_EMAIL_INTRO', '<strong>' . $contract->client_name . '</strong>') . '</p>';
                $html .= '<div style="background-color: #fff; border: 1px solid #eaecf4; border-left: 4px solid ' . $statusColor . '; padding: 15px; margin: 25px 0; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">';
                $html .= '<p style="margin: 0 0 10px 0;"><strong>' . Text::_('PLG_TASK_CONTRACTORWARNING_LBL_CONTRACT') . ':</strong> ' . $contract->name . '</p>';
                $html .= '<p style="margin: 0 0 10px 0;"><strong>' . Text::_('PLG_TASK_CONTRACTORWARNING_LBL_CLIENT') . ':</strong> ' . $contract->client_name . '</p>';
                $html .= '<p style="margin: 0 0 10px 0;"><strong>' . Text::_('PLG_TASK_CONTRACTORWARNING_LBL_EXPIRES') . ':</strong> ' . $formattedDate . '</p>';
                $html .= '<p style="margin: 0;"><strong>' . Text::_('PLG_TASK_CONTRACTORWARNING_LBL_STATUS') . ':</strong> <span style="color: ' . $statusColor . '; font-weight: bold;">' . $icon . ' ' . $statusText . '</span></p>';
                $html .= '</div>';
                $html .= '<div style="text-align: center; margin-top: 35px; margin-bottom: 10px;">';
                $html .= '<a href="' . $editUrl . '" style="background-color: #4e73df; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">' . Text::_('PLG_TASK_CONTRACTORWARNING_BTN_VIEW') . '</a>';
                $html .= '</div>';
                $html .= '</div></div>';

                // Gestion du sender
                $mailFrom = !empty($config['mailfrom']) ? $config['mailfrom'] : Factory::getApplication()->get('mailfrom');
                $fromName = !empty($config['fromname']) ? $config['fromname'] : Factory::getApplication()->get('fromname');
                $mailer->setSender([$mailFrom, $fromName]);
                
                $mailer->clearAllRecipients();
                $mailer->addRecipient($warnMail);
                $mailer->setSubject($subject);
                $mailer->isHtml(true);
                $mailer->setBody($html);

                try {
                    if ($mailer->send()) {
                        $updateQ = $db->getQuery(true)
                            ->update($db->quoteName('#__ctr_contract'))
                            ->set($db->quoteName('lastmsg') . ' = CURRENT_TIMESTAMP')
                            ->where($db->quoteName('id') . ' = ' . (int) $contract->id);
                        $db->setQuery($updateQ)->execute();
                        
                        $logDesc = "Warning email sent to admin for contract ID {$contract->id} ({$statusText})";
                        ContractorHelper::writeLog((int) $contract->client_id, 0, $logDesc); 
    
                        $sentCount++;
                    } else {
                        ContractorHelper::writeLog((int) $contract->client_id, 2, "Failed to send warning email for contract ID {$contract->id}."); 
                    }
                } catch (\Exception $e) {
                    ContractorHelper::writeLog((int) $contract->client_id, 2, "SMTP Exception sending warning for contract ID {$contract->id}: " . $e->getMessage()); 
                }
            } else {
                $logDesc = "No warning email sent for contract ID {$contract->id}. Reason: {$skipReason}";
                ContractorHelper::writeLog((int) $contract->client_id, 1, $logDesc); 
            }
        }

        if ($sentCount > 0) {
            // Remplacement de 'null' par 0 pour éviter une erreur TypeError si writeLog attend strictement un int.
            ContractorHelper::writeLog(NULL, 0, "Task Scheduler routine completed. Total warning emails sent: {$sentCount}");
        }

        $this->logTask("Checked expiring contracts. Emails sent: {$sentCount}", 'info');
        
        return Status::OK;
    }
}