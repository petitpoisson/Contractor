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
use Joomla\CMS\Language\Text;

/**
 * Client Form Controller
 */
class TagController extends FormController
{
    public function delete()
    {
        $pks = $app->input->post->get('cid', [], 'array');
        $userId = $this->app->getIdentity()->id;
        $model = $this->getModel('Tag');
        
        foreach ($pks as $pk) {
            $pk = (int)$pk;

            if ($model->delete($pk)) {
                $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_TAGS_DELETED'));
            } else {
                $this->app->enqueueMessage($model->getError(), 'error');
            }
        $this->setRedirect('index.php?option=com_contractor&view=tags');    }
        }
}