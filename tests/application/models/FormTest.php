<?php

/**
 * Description of FormTest
 *
 * @author Max
 */
require_once TESTS_PATH . '/application/TestCase.php';

class FormTest extends TestCase {

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testFormConstructException() {
        $form = new Application_Model_Form(1);
    }

    public function testFormConstructCorrect() {
        $itemArray = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'userId' => 6, 'elementId' => 2, 'formId' => 1);
        $item = new Application_Model_Item($itemArray);
        $this->assertTrue($item->isValid());
        $formArray = array('formName' => 'eName', 'formId' => 3, 'userId' => 5, 'active' => 0, 'domainId' => 5, 'contragentId' => 2, 'items' => array(0 => $item), 'nodeId' => 3, 'public' => 1, 'expgroup' => 'CAPEX');
        $form = new Application_Model_Form($formArray);
        $this->assertTrue($form->isValid());
        $formArray1 = $form->toArray();
        unset($formArray['date']);
        unset($formArray1['date']);
        $this->assertEquals($formArray, $formArray1);
    }

    public function testFormConstructFromWebForm() {
        $itemArray1 = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'elementId' => 33, 'active' => 1);
        $itemArray2 = array('itemName' => 'item2', 'domainId' => 1, 'value' => 22.1, 'elementId' => 22, 'active' => 1);
        $formArray1 = array('userId' => 2, 'formName' => 'fName1', 'nodeId' => 3, 'contragentId'=>5,'items' => array(0 => $itemArray1, 1 => $itemArray2), 'domainId' => 1, 'active' => 1, 'contragentName' => 'contr name', 'expgroup' => 'CAPEX');
        $form = new Application_Model_Form($formArray1);
        $this->assertTrue($form->isValid());
        $formArray1 = array('formName' => 'test', 'nodeId' => 3, 'domainId' => 1,
            'value_2' => 3, 'itemName_2' => 'we', 'value_1' => 1, 'itemName_1' => 'test',
            'elementId_1' => 33, 'elementId_2' => 22, 'userId' =>2, 'contragentId' => 22, 'expgroup' => 'CAPEX');
        $form1 = new Application_Model_Form($formArray1);
        $this->assertTrue($form1->isValid());
    }

    public function testFormValidation() {
        $itemArray = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'userId' => 6, 'elementId' => 2, 'formId' => 1);
        $item = new Application_Model_Item($itemArray);
        $itemArray1 = array('itemName' => 'item2', 'domainId' => 1, 'value' => 33.2, 'userId' => 6, 'elementId' => 1, 'formId' => 1);
        $item1 = new Application_Model_Item($itemArray1);
        $this->assertTrue($item->isValid());
        $formArray = array('formName' => 'pName');
        $form = new Application_Model_Form($formArray);
        $this->assertFalse($form->isValid());
        $form->formName = 'eName';
        $this->assertFalse($form->isValid());
        $form->domainId = 3;
        $this->assertFalse($form->isValid());
        $form->userId = 4;
        $this->assertFalse($form->isValid());
        $form->items = array(1 => $item, 2 => $item1);
        $this->assertFalse($form->isValid());
        $form->nodeId = 2;
        $this->assertFalse($form->isValid());
        $form->formId = 1;
        $this->assertFalse($form->isValid());
        $form->contragentId = 3;
        $this->assertFalse($form->isValid());
        $form->expgroup = 'OPEX';
        $this->assertTrue($form->isValid());
        $this->assertEquals('eName', $form->formName);
        $this->assertEquals(1, $form->formId);
        $this->assertEquals(4, $form->userId);
        $this->assertEquals(3, $form->contragentId);
        $this->assertEquals($form->items, array(0 => $item, 1 => $item1));
        $form->items = $item;
        $this->assertTrue($form->isValid());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Cannot 
     */
    public function testFormNotValidItemAssignment() {
        $itemArray = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'userId' => 6);
        $item = new Application_Model_Item($itemArray);
        $this->assertFalse($item->isValid());
        $formArray = array('formName' => 'eName', 'formId' => 3, 'userId' => 5, 'active' => false, 'domainId' => 5, 'items' => array(0 => $item), 'nodeId' => 3);
        $form = new Application_Model_Form($formArray);
        $this->assertFalse($form->isValid());
    }

    public function testFormItemsSetingGetting() {
        $itemArray = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'userId' => 6, 'elementId' => 2, 'formId' => 4);
        $item = new Application_Model_Item($itemArray);
        $this->assertTrue($item->isValid());
        $formArray = array('formName' => 'eName', 'formId' => 3, 'userId' => 5, 'active' => false, 'domainId' => 5, 'items' => array(0 => $item), 'nodeId' => 3);
        $form = new Application_Model_Form($formArray);
        $itemArray2 = $form->items;
        $this->assertTrue(is_array($itemArray2));
        $this->assertTrue($itemArray2[0] instanceof Application_Model_Item);
        $this->assertEquals(array(0 => $item), $itemArray2);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage One of items is neither of Application_Model_Item type nor Array().
     */
    public function testFormItemInvalidAssignment() {
        $item = 'wrong item';
        $formArray = array('formName' => 'eName', 'formId' => 3, 'userId' => 5, 'active' => false, 'domainId' => 5, 'items' => array(0 => $item), 'nodeId' => 3);
        $form = new Application_Model_Form($formArray);
    }

    public function testFormToArray() {
        $formArray = array('formName' => 'eName', 'userId' => 12, 'formId' => 3, 'active' => 0, 'public' => 1, 'domainId' => 5, 'contragentId' => 3);
        $form = new Application_Model_Form($formArray);
        $formArray2 = $form->toArray();
        unset($formArray['date']);
        unset($formArray2['date']);
        $this->assertEquals($formArray, $formArray2);
    }

}
