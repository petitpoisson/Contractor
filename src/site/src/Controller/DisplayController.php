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

namespace XavierSpirlet\Component\Contractor\Site\Controller;

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default Controller for the Site (Frontend)
 */
class DisplayController extends BaseController
{
    /**
     * The default view to display if none is specified in the URL.
     *
     * @var string
     */
    protected $default_view = 'client';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  $this
     */
    public function display($cachable = false, $urlparams = [])
    {
        // Tu pourrais ajouter ici de la logique globale au frontend avant affichage si besoin
        
        return parent::display($cachable, $urlparams);
    }
}