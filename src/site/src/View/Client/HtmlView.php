<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Component\Contractor\Site\View\Client;

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class HtmlView extends BaseHtmlView
{
    public $contracts = [];
    public $invoices  = [];
    public $hasAccess = true;
    public $params;
    public $config = [];
    public $color1Dark;
    public $color2Dark;
    public $color1Text;
    public $color2Text;
    public $color1DarkText;
    public $color2DarkText;

    public function display($tpl = null)
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        $this->params = $app->getParams();

        if ($user->guest) {
            $redirectUrl = $this->params->get('guest_redirect_url', '');
            $loginUrl = empty($redirectUrl) 
                ? 'index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString())
                : (is_numeric($redirectUrl) ? 'index.php?Itemid=' . (int) $redirectUrl : $redirectUrl);

            $app->enqueueMessage(Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'error');
            $app->redirect(Route::_($loginUrl, false));
            return;
        }

        /** @var \XavierSpirlet\Component\Contractor\Site\Model\ClientModel $model */
        $model = $this->getModel();
        $this->contracts = $model->getContracts();
        $this->invoices  = $model->getInvoices();

        $this->loadConfig();
        $this->handleFrameworkAssets();
        $this->generateDynamicStyles();

        parent::display($tpl);
    }

    private function loadConfig()
    {
        $this->config = ContractorHelper::getConfig();

        $this->config['font_framework'] = $this->config['font_framework'] ?? '0';
        $this->config['front_loadfw']   = $this->config['front_loadfw'] ?? '0';
        $this->config['front_color1']   = $this->config['front_color1'] ?? '#c7d1e1';
        $this->config['front_color2']   = $this->config['front_color2'] ?? '#c7d8a9';

        $this->color1Dark = $this->adjustBrightness($this->config['front_color1'], -30);
        $this->color2Dark = $this->adjustBrightness($this->config['front_color2'], -30);

        $this->color1Text     = ContractorHelper::getContrastColor($this->config['front_color1']);
        $this->color2Text     = ContractorHelper::getContrastColor($this->config['front_color2']);
        $this->color1DarkText = ContractorHelper::getContrastColor($this->color1Dark);
        $this->color2DarkText = ContractorHelper::getContrastColor($this->color2Dark);
    }

    private function handleFrameworkAssets()
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('com_contractor.custom', 'media/com_contractor/css/contractor.css');

        if ($this->config['font_framework'] === '0') {
            // BOOTSTRAP
            if ($this->config['front_loadfw'] === '1') {
                // OVERRIDE Joomla's native bootstrap core assets with your local ones
                $wa->registerStyle('bootstrap', 'media/com_contractor/css/bootstrap.min.css', [], [], []);
                $wa->registerScript('bootstrap.bundle', 'media/com_contractor/js/bootstrap.bundle.min.js', [], ['type' => 'module'], []);
                
                $wa->useStyle('bootstrap');
                $wa->useScript('bootstrap.bundle');
            } else {
                // Use Joomla's native HTMLHelper to trigger native Bootstrap
                \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.collapse');
            }
        } else {
            // UIKIT
            if ($this->config['front_loadfw'] === '1') {
                $wa->registerAndUseStyle('uikit', 'media/com_contractor/css/uikit.min.css');
                $wa->registerAndUseScript('uikit', 'media/com_contractor/js/uikit.min.js');
                $wa->registerAndUseScript('uikit-icons', 'media/com_contractor/js/uikit-icons.min.js');
            }
        }
    }

    private function generateDynamicStyles()
    {
        $doc = Factory::getApplication()->getDocument();
        $css = "
            :root {
                --ctr-contract-bg: {$this->config['front_color1']};
                --ctr-contract-bg-open: {$this->color1Dark};
                --ctr-contract-text: {$this->color1Text};
                --ctr-contract-text-open: {$this->color1DarkText};

                --ctr-invoice-bg: {$this->config['front_color2']};
                --ctr-invoice-bg-open: {$this->color2Dark};
                --ctr-invoice-text: {$this->color2Text};
                --ctr-invoice-text-open: {$this->color2DarkText};
            }
        ";
        $doc->addStyleDeclaration($css);
    }

    private function adjustBrightness($hex, $steps)
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