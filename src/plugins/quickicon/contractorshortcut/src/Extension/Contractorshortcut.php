<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.Contractorshortcut
 *
 * @copyright   (C) 2026 Xavier Spirlet - Petitpoisson. All rights reserved
 * @license     GNU/GPL 3 or later
 */

namespace XavierSpirlet\Plugin\Quickicon\Contractorshortcut\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

final class Contractorshortcut extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load plugin language files automatically
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetIcons' => 'onGetIcons',
        ];
    }

    /**
     * Injects the custom icon into the Dashboard
     */
    public function onGetIcons(QuickIconsEvent $event): void
    {
        $context = $event->getContext();

        if ($context !== $this->params->get('context', 'mod_quickicon')) {
            return;
        }

        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage', 'com_contractor')) {
            return;
        }

        $result = $event->getArgument('result', []);

        $result[] = [
            [
                'link'  => 'index.php?option=com_contractor&view=dashboard',
                'image' => 'fas fa-file-contract', 
                'icon'  => 'fas fa-file-contract', // Clé privilégiée pour FontAwesome dans J4/J5
                'text'  => Text::_('PLG_QUICKICON_CONTRSH_BUTTON_TEXT'),
                'id'    => 'plg_quickicon_contractorshortcut',
                'group' => 'MOD_QUICKICON_CONTENT' // S'affichera dans le groupe "Contenu" du tableau de bord
            ]
        ];

        $event->setArgument('result', $result);
    }
}