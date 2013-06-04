<?php
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
 * @category    Application Unit Test
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Unit Tests fuer Admin_Form_DocumentGeneral.
 */
class Admin_Form_DocumentGeneralTest extends ControllerTestCase {
        
    public function testCreateForm() {
        $form = new Admin_Form_DocumentGeneral();
        
        $this->assertNotNull($form->getElement('Language'));
        $this->assertNotNull($form->getElement('Type'));
        $this->assertNotNull($form->getElement('PublishedDate'));
        $this->assertNotNull($form->getElement('PublishedYear'));
        $this->assertNotNull($form->getElement('CompletedDate'));
        $this->assertNotNull($form->getElement('CompletedYear'));
    }
    
    /**
     * TODO use temporary Opus_Document instead of doc from test data
     */
    public function testPopulateFromModel() {
        $this->setUpEnglish();
        
        $document = new Opus_Document(146);
        
        $form = new Admin_Form_DocumentGeneral();
        
        $form->populateFromModel($document);
        
        $this->assertEquals('deu', $form->getElement('Language')->getValue());
        $this->assertEquals('masterthesis', $form->getElement('Type')->getValue());
        $this->assertEquals('2007/04/30', $form->getElement('PublishedDate')->getValue());
        $this->assertEquals('2008', $form->getElement('PublishedYear')->getValue());
        $this->assertEquals('2011/12/01', $form->getElement('CompletedDate')->getValue());
        $this->assertEquals('2009', $form->getElement('CompletedYear')->getValue());
    }
    
    public function testUpdateModel() {
        $this->setUpEnglish();
        
        $form = new Admin_Form_DocumentGeneral();
        
        $form->getElement('Language')->setValue('eng');
        $form->getElement('Type')->setValue('masterthesis');
        $form->getElement('PublishedDate')->setValue('2005/06/17');
        $form->getElement('PublishedYear')->setValue('2006');
        $form->getElement('CompletedDate')->setValue('2006/07/03');
        $form->getElement('CompletedYear')->setValue('2007');
        
        $document = new Opus_Document();
        
        $form->updateModel($document);
        
        $this->assertEquals('eng', $document->getLanguage());
        $this->assertEquals('masterthesis', $document->getType());
        
        $this->assertNotNull($document->getPublishedDate());
        $this->assertEquals('2005/06/17', date('Y/m/d', $document->getPublishedDate()->getZendDate()->get()));
        $this->assertEquals('2006', $document->getPublishedYear());

        $this->assertNotNull($document->getCompletedDate());
        $this->assertEquals('2006/07/03', date('Y/m/d', $document->getCompletedDate()->getZendDate()->get()));
        $this->assertEquals('2007', $document->getCompletedYear());
    }
    
    /**
     * TODO Welche Validierung fuer Language?
     * TODO Welche Validierung fuer Type? 
     */
    public function testValidation() {
        $this->setUpEnglish();
        
        $form = new Admin_Form_DocumentGeneral();
        
        $post = array(
            'Language' => '',
            'Type' => '',
            'PublishedDate' => 'date1', // muss Datum sein
            'PublishedYear' => 'year1', // muss Integer sein
            'CompletedDate' => '2008/02/31', // muss korrektes Datum sein
            'CompletedYear' => '-1', // muss groesser als 0 sein
        );

        $this->assertFalse($form->isValid($post));
        
        $this->assertContains('dateFalseFormat', $form->getErrors('PublishedDate'));
        $this->assertContains('notInt', $form->getErrors('PublishedYear'));
        $this->assertContains('dateInvalidDate', $form->getErrors('CompletedDate'));
        $this->assertContains('notGreaterThan', $form->getErrors('CompletedYear'));
    }
    
}

