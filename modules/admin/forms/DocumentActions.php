<?PHP
/*
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @package     Module_Admin
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Unterformular fuer die Aktions im Metadaten-Formular.
 */
class Admin_Form_DocumentActions extends Admin_Form_AbstractDocumentSubForm {

    const ELEMENT_ID = 'Id';
    
    const ELEMENT_HASH = 'OpusHash';
    
    /**
     * Name für Button zum Speichern.
     */
    const ELEMENT_SAVE = 'Save';
    
    /**
     * Name für Button zum Speichern und im Metadaten-Formular bleiben.
     */
    const ELEMENT_SAVE_AND_CONTINUE = 'SaveAndContinue';
    
    /**
     * Name für Button um das Editieren abzubrechen.
     */
    const ELEMENT_CANCEL = 'Cancel';
    
    public function init() {
        parent::init();
        
        $this->addElement('hidden', self::ELEMENT_ID);
        $this->addElement('hash', self::ELEMENT_HASH, array('salt' => 'unique')); // TODO salt?
        $this->addElement('submit', self::ELEMENT_SAVE, array('decorators' => array('ViewHelper')));
        $this->addElement('submit', self::ELEMENT_SAVE_AND_CONTINUE, array('decorators' => array('ViewHelper')));
        $this->addElement('submit', self::ELEMENT_CANCEL, array('decorators' => array('ViewHelper')));
        
        $this->setDecorators(array(
            'PrepareElements',
            array('ViewScript', array('viewScript' => 'form/documentActions.phtml')),
            array(array('fieldsWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'fields-wrapper')),
            array(array('divWrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'subform', 'id' => 'subform-Actions'))
        ));
    }
    
    public function populateFromModel($document) {
        $this->getElement(self::ELEMENT_ID)->setValue($document->getId());        
    }
    
    public function processPost($post, $context) {
        // Prüfen, ob "Speichern" geklickt wurde
        if (array_key_exists(self::ELEMENT_SAVE, $post)) {
            return Admin_Form_Document::RESULT_SAVE;
        }
        else if (array_key_exists(self::ELEMENT_SAVE_AND_CONTINUE, $post)) {
            return Admin_Form_Document::RESULT_SAVE_AND_CONTINUE;
        }
        else if (array_key_exists(self::ELEMENT_CANCEL, $post)) {
            return Admin_Form_Document::RESULT_CANCEL;
        }
        
        return null;
    }

}
