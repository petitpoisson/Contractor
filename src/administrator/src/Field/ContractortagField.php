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

namespace XavierSpirlet\Component\Contractor\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Custom Field to load Tags with their custom colors
 */
class ContractortagField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Contractortag';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        $options = [];

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'value', 'color']))
            ->from($db->quoteName('#__ctr_tags'))
            ->order($db->quoteName('value') . ' ASC');

        $db->setQuery($query);
        $tags = $db->loadObjectList();

        if ($tags) {
            foreach ($tags as $tag) {
                $option = new \stdClass();
                $option->value = $tag->id;
                $option->text  = $tag->value;
                
                // Injection de l'attribut data-color (Joomla le rendra tel quel dans le HTML)
                $prop = 'data-color';
                $option->$prop = !empty($tag->color) ? $tag->color : '#586a7a';
                
                $options[] = $option;
            }
        }

        // On fusionne avec les options parentes (ex: l'option vide définie dans le XML)
        return array_merge(parent::getOptions(), $options);
    }
}