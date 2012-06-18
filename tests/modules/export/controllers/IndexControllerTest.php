<?php
/**
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
 * @package     Tests
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Export_IndexControllerTest extends ControllerTestCase {

    public function testIndexActionWithoutFormat() {
        $this->dispatch('/export');
        $this->assertResponseCode(500);
        $response = $this->getResponse();
        $this->assertContains('export format is not specified', $response->getBody());
    }

    public function testIndexActionWithUnsupportedFormat() {
        $this->dispatch('/export/index/index/export/unsupporedformat');
        $this->assertResponseCode(500);
        $response = $this->getResponse();
        $this->assertContains('export format is not supported', $response->getBody());
    }

    public function testIndexActionWithoutQuery() {
        $this->dispatch('/export/index/index/export/xml');
        $this->assertResponseCode(500);
        $response = $this->getResponse();
        $this->assertContains('Unspecified search type', $response->getBody());
    }

    public function testIndexActionWithoutStylesheetParam() {
        $this->dispatch('/export/index/index/export/xml/query/foo/searchtype/latest');
        $this->assertResponseCode(200, $this->getResponse()->getBody());
        $response = $this->getResponse();
        $this->assertContains('<?xml version="1.0" encoding="utf-8"?>', $response->getBody());
        $this->assertContains('<export timestamp=', $response->getBody());
    }

    public function testIndexActionWithStylesheetParam() {
        $this->dispatch('/export/index/index/export/xml/query/foo/searchtype/latest/stylesheet/example');
        $this->assertResponseCode(200, $this->getResponse()->getBody());
        $response = $this->getResponse();
        $this->assertContains('<?xml version="1.0" encoding="utf-8"?>', $response->getBody());
        $this->assertContains('<export-example>', $response->getBody());
    }

    public function testIndexActionCollectionSearch() {
        $this->dispatch('/export/index/index/searchtype/collection/id/2/export/xml/stylesheet/example');
        $this->assertResponseCode(200, $this->getResponse()->getBody());
        $response = $this->getResponse();
        $this->assertContains('<?xml version="1.0" encoding="utf-8"?>', $response->getBody());
        $this->assertContains('<export-example>', $response->getBody());
        $this->assertTrue(substr_count($response->getBody(), '<doc>') == 1);
    }

    public function testIndexActionInvalidCollectionSearch_MissingIdParam() {
        $this->dispatch('/export/index/index/searchtype/collection/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Could not browse collection due to missing id parameter.", $this->getResponse()->getBody());
    }

    public function testIndexActionInvalidCollectionSearch_UnknownId() {
        $this->dispatch('/export/index/index/searchtype/collection/id/-1/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Collection with id '-1' does not exist.", $this->getResponse()->getBody());
    }

    public function testIndexActionInvalidCollectionSearch_Unvisible() {
        $this->dispatch('/export/index/index/searchtype/collection/id/23/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Collection with id '23' is not visible.", $this->getResponse()->getBody());
    }

    public function testIndexActionSeriesSearch() {
        $this->dispatch('/export/index/index/searchtype/series/id/1/export/xml/stylesheet/example');
        $this->assertResponseCode(200, $this->getResponse()->getBody());
        $response = $this->getResponse();
        $this->assertContains('<?xml version="1.0" encoding="utf-8"?>', $response->getBody());
        $this->assertContains('<export-example>', $response->getBody());
        $this->assertTrue(substr_count($response->getBody(), '<doc>') == 5);
    }

    public function testIndexActionInvalidSeriesSearch_MissingIdParam() {
        $this->dispatch('/export/index/index/searchtype/series/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Could not browse series due to missing id parameter.", $this->getResponse()->getBody());
    }

    public function testIndexActionInvalidSeriesSearch_UnknownId() {
        $this->dispatch('/export/index/index/searchtype/series/id/999999/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Series with id '999999' does not exist.", $this->getResponse()->getBody());
    }

    public function testIndexActionInvalidSeriesSearch_Unvisible() {
        $this->dispatch('/export/index/index/searchtype/series/id/3/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Series with id '3' is not visible.", $this->getResponse()->getBody());
    }

    public function testIndexActionInvalidSeriesSearch_NoDocuments() {
        $this->dispatch('/export/index/index/searchtype/series/id/8/export/xml/stylesheet/example');
        $this->assertResponseCode(500);
        $this->assertContains("Series with id '8' does not have any published documents.", $this->getResponse()->getBody());
    }

    /**
     * request for raw export output is denied for non-administrative people
     */
    public function testRequestToRawXmlIsDenied() {
        $r = Opus_UserRole::fetchByName('guest');

        $modules = $r->listAccessModules();
        $addOaiModuleAccess = !in_array('export', $modules);
        if ($addOaiModuleAccess) {
            $r->appendAccessModule('export');
            $r->store();
        }

        // enable security
        $config = Zend_Registry::get('Zend_Config');
        $security = $config->security;
        $config->security = '1';
        Zend_Registry::set('Zend_Config', $config);

        $this->dispatch('/export/index/index/export/xml');
        $this->assertResponseCode(500);
        $this->assertContains('missing parameter stylesheet', $this->getResponse()->getBody());

        // restore security settings
        if ($addOaiModuleAccess) {
            $r->removeAccessModule('export');
            $r->store();
        }
        
        $config->security = $security;
        Zend_Registry::set('Zend_Config', $config);
    }

    /**
     * Regression test for OPUSVIER-2337
     */
    public function testUnavailableSolrServerReturns503() {
        $this->requireSolrConfig();

        // role guest needs privilege to access module export
        $r = Opus_UserRole::fetchByName('guest');

        $modules = $r->listAccessModules();
        $addOaiModuleAccess = !in_array('export', $modules);
        if ($addOaiModuleAccess) {
            $r->appendAccessModule('export');
            $r->store();
        }

        // manipulate solr configuration
        $config = Zend_Registry::get('Zend_Config');
        $host = $config->searchengine->index->host;
        $port = $config->searchengine->index->port;
        $oldValue = $config->searchengine->index->app;
        $config->searchengine->index->app = 'solr/corethatdoesnotexist';
        $security = $config->security;
        $config->security = '1';
        Zend_Registry::set('Zend_Config', $config);

        $this->dispatch('/export/index/index/searchtype/all/export/xml/stylesheet/example');
        $body = $this->getResponse()->getBody();
        $this->assertNotContains("http://${host}:${port}/solr/corethatdoesnotexist", $body);
        $this->assertContains("exception 'Application_SearchException' with message 'search server is not responding -- try again later'", $this->getResponse()->getBody());
        $this->assertResponseCode(503);
        
        // restore configuration
        $config = Zend_Registry::get('Zend_Config');        
        $config->searchengine->index->app = $oldValue;
        $config->security = $security;
        Zend_Registry::set('Zend_Config', $config);
    }

    /**
     * Regression test for OPUSVIER-1726
     */
    public function testSolrIndexIsNotUpToDate() {
        $doc1 = new Opus_Document();
        $doc1->setServerState('published');
        $doc1->setLanguage('eng');
        $title = new Opus_Title();
        $title->setValue('test document for OPUSVIER-1726');
        $title->setLanguage('eng');
        $doc1->setTitleMain($title);
        $doc1->store();
        $docId1 = $doc1->getId();
        
        // add a document to the search index that is not stored in database
        $doc2 = new Opus_Document();
        $doc2->setServerState('published');
        $doc2->setLanguage('eng');
        $title = new Opus_Title();
        $title->setValue('another test document for OPUSVIER-1726');
        $title->setLanguage('eng');
        $doc2->setTitleMain($title);
        // unregister index plugin: database changes are not reflected in search index
        $doc2->unregisterPlugin('Opus_Document_Plugin_Index');
        $doc2->store();
        $docId2 = $doc2->getId();

        $indexer = new Opus_SolrSearch_Index_Indexer();

        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $methodGetSolrXmlDocument = $class->getMethod('getSolrXmlDocument');
        $methodGetSolrXmlDocument->setAccessible(true);
        $solrXml = $methodGetSolrXmlDocument->invoke($indexer, $doc2);

        // delete document from database
        $doc2->deletePermanent();

        // add document to search index
        $methodSendSolrXmlToServer = $class->getMethod('sendSolrXmlToServer');
        $methodSendSolrXmlToServer->setAccessible(true);
        $methodSendSolrXmlToServer->invoke($indexer, $solrXml);
        $indexer->commit();

        $this->dispatch('/export/index/index/searchtype/simple/query/opusvier-1726/export/xml');

        // make search index up to date
        $indexer->removeDocumentFromEntryIndexById($docId2);
        $indexer->commit();

        $body = $this->getResponse()->getBody();
        
        $this->assertNotContains("No Opus_Db_Documents with id $docId2 in database.", $body);
        $this->assertContains('Language="eng" Value="test document for OPUSVIER-1726" Type="main"', $body);
        $this->assertNotContains('Language="eng" Value="another test document for OPUSVIER-1726" Type="main"', $body);
        $this->assertContains('<Opus_Document Id="' . $docId1 . '" Language="eng"', $body);
        $this->assertNotContains('<Opus_Document Id="' . $docId2 . '" Language="eng"', $body);
        $this->assertContains('doccount="1"', $body);
        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());

        $doc1->deletePermanent();
    }

    /**
     * helper function for tests related to OPUSVIER-2488
     */
    private function helperForOPUSVIER2488($url, $numOfTestDocs, $rows, $start = 0) {
        $docs = array();
        for ($i = 0; $i < $numOfTestDocs; $i++) {
            $doc = new Opus_Document();
            $doc->setServerState('published');
            $doc->setLanguage('eng');
            $title = new Opus_Title();
            $title->setValue('OPUSVIER-2488');
            $title->setLanguage('eng');
            $doc->setTitleMain($title);
            $doc->store();
            array_push($docs, $doc);
        }

        $this->dispatch($url);
        $body = $this->getResponse()->getBody();

        $docIds = array();
        // perform cleanup before asserting anything
        foreach ($docs as $doc) {
            array_push($docIds, $doc->getId());
            $doc->deletePermanent();
        }

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $this->assertContains('doccount="' . $rows . '"', $body);
        $this->assertEquals($rows, substr_count($body, 'Language="eng" Value="OPUSVIER-2488" Type="main"'));
        $this->assertNotContains('Application_Exception', $body);

        for ($i = $start; $i < $rows; $i++) {
            $this->assertContains('<Opus_Document Id="' . $docIds[$i] . '"', $body);
        }
    }

    /**
     * begin: tests for OPUSVIER-2488
     */    
    public function testPaginationIsSupportedInExportWithoutPaginationParams() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStart0() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/0', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStart2() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2', 5, 3, 2);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStart5() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/5', 5, 0);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStartTooLarge() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/10', 5, 0);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStartTooSmall() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/-1', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamStartInvalid() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/foo', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRows0() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/0', 5, 0);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRows2() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/2', 5, 2);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRows5() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/5', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRowsTooLarge() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/50', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRowsTooSmall() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/-1', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamRowsInvalid() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/rows/foo', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamsStart0Rows2() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/0/rows/2', 5, 2);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamsStart0Rows10() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/0/rows/10', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamsStart2Rows2() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2/rows/2', 5, 2, 2);
    }

    public function testPaginationIsSupportedInExportWithPaginationParamsStart2Rows5() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2/rows/5', 5, 3, 2);
    }

    public function testPaginationIsSupportedInExportWithExtremeValues1() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/0/rows/2147483647', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithExtremeValues2() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/0/rows/2147483648', 5, 5);
    }

    public function testPaginationIsSupportedInExportWithExtremeValues3() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2147483647/rows/10', 5, 0);
    }
    
    public function testPaginationIsSupportedInExportWithExtremeValues4() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2147483648/rows/10', 5, 0);
    }

    public function testPaginationIsSupportedInExportWithExtremeValues5() {
        $this->helperForOPUSVIER2488('/export/index/index/searchtype/simple/query/opusvier-2488/export/xml/start/2147483646/rows/1', 5, 0);
    }

    /**
     * end: tests for OPUSVIER-2488
     */

    /**
     * Regression test for OPUSVIER-2434
     */
    public function testInvalidSearchQueryReturns500() {
        $this->requireSolrConfig();

        $this->dispatch('/export/index/index/searchtype/simple/export/xml/start/0/rows/10/query/%22%5C%22%22');

        $this->assertContains("exception 'Application_SearchException' with message 'search query is invalid -- check syntax'", $this->getResponse()->getBody());
        $this->assertNotContains("exception 'Application_SearchException' with message 'search server is not responding -- try again later'", $this->getResponse()->getBody());

        $this->assertEquals(500, $this->getResponse()->getHttpResponseCode());
    }
}

