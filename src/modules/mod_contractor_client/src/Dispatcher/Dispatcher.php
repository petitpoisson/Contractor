<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_contractor_client
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Module\ContractorClient\Site\Dispatcher;

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Dispatcher class for mod_contractor_client
 */
class Dispatcher extends AbstractModuleDispatcher
{
    /**
     * Method to generate the layout data.
     *
     * @return  array
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        // Force loading the component's language file
        $app->getLanguage()->load('com_contractor', JPATH_SITE);

        // Boot component to access classes (Helper, Model)
        $app->bootComponent('com_contractor');
        
        // Import Helper explicitly via fully qualified name
        $helperClass = '\\XavierSpirlet\\Component\\Contractor\\Administrator\\Helper\\ContractorHelper';

        // 1. Fetch config and process assets FIRST (so routing and styling work for guests too)
        $data['config'] = $helperClass::getConfig();
        $this->processAssetsAndStyles($data['config'], $helperClass);

        // 2. Handle Guest redirection logic
        $data['is_guest'] = $user->guest;

        if ($user->guest) {
            $redirectUrl = $data['params']->get('guest_redirect_url', '');
            $loginUrl = empty($redirectUrl) 
                ? 'index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString())
                : (is_numeric($redirectUrl) ? 'index.php?Itemid=' . (int) $redirectUrl : $redirectUrl);

            // Pass the generated login URL to the layout
            $data['login_url'] = Route::_($loginUrl, false);
            
            // Stop further execution and model fetching
            return $data; 
        }

        // 3. Fetch data ONLY for logged-in users
        $mvcFactory = $app->bootComponent('com_contractor')->getMVCFactory();
        $model = $mvcFactory->createModel('Client', 'Site');

        $data['contracts'] = $model->getContracts();
        $data['invoices']  = $model->getInvoices();

        return $data;
    }

    /**
     * Handles assets and dynamic CSS variables mapping.
     */
    private function processAssetsAndStyles(array &$config, string $helperClass): void
    {
        $app = Factory::getApplication();
        $doc = $app->getDocument();
        $wa  = $doc->getWebAssetManager();

        $config['font_framework'] = $config['font_framework'] ?? '0';
        $config['front_loadfw']   = $config['front_loadfw'] ?? '0';
        $config['front_color1']   = $config['front_color1'] ?? '#c7d1e1';
        $config['front_color2']   = $config['front_color2'] ?? '#c7d8a9';

        $color1Dark = $this->adjustBrightness($config['front_color1'], -30);
        $color2Dark = $this->adjustBrightness($config['front_color2'], -30);

        $color1Text     = $helperClass::getContrastColor($config['front_color1']);
        $color2Text     = $helperClass::getContrastColor($config['front_color2']);
        $color1DarkText = $helperClass::getContrastColor($color1Dark);
        $color2DarkText = $helperClass::getContrastColor($color2Dark);

        // Assets
        $wa->registerAndUseStyle('com_contractor.custom', 'media/com_contractor/css/contractor.css');

        if ($config['font_framework'] === '0') {
            if ($config['front_loadfw'] === '1') {
                $wa->registerStyle('bootstrap', 'media/com_contractor/css/bootstrap.min.css', [], [], []);
                $wa->registerScript('bootstrap.bundle', 'media/com_contractor/js/bootstrap.bundle.min.js', [], ['type' => 'module'], []);
                $wa->useStyle('bootstrap');
                $wa->useScript('bootstrap.bundle');
            } else {
                \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.collapse');
            }
        } else {
            if ($config['front_loadfw'] === '1') {
                $wa->registerAndUseStyle('uikit', 'media/com_contractor/css/uikit.min.css');
                $wa->registerAndUseScript('uikit', 'media/com_contractor/js/uikit.min.js');
                $wa->registerAndUseScript('uikit-icons', 'media/com_contractor/js/uikit-icons.min.js');
            }
        }

        // Dynamic CSS variables
        $css = "
            :root {
                --ctr-contract-bg: {$config['front_color1']};
                --ctr-contract-bg-open: {$color1Dark};
                --ctr-contract-text: {$color1Text};
                --ctr-contract-text-open: {$color1DarkText};

                --ctr-invoice-bg: {$config['front_color2']};
                --ctr-invoice-bg-open: {$color2Dark};
                --ctr-invoice-text: {$color2Text};
                --ctr-invoice-text-open: {$color2DarkText};
            }
        ";
        $doc->addStyleDeclaration($css);
    }

    /**
     * Adjusts hex color brightness.
     */
    private function adjustBrightness(string $hex, int $steps): string
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}