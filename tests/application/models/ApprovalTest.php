<?php

class ApprovalTest extends TestCase {

    private $dataMapper;
    private $objectManager;
    private $userId;
    private $userId1;
    private $nodeId;
    private $nodeId1;
    private $nodeId2;
    private $nodeId3;
    private $elementId1;
    private $elementId2;
    private $resourceId3;
    private $formId;
    private $scenario;
    private $scenarioId;

    public function setUp() {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $session = new Zend_Session_Namespace('Auth');
        $session->domainId = 1;
        $this->objectManager = new Application_Model_ObjectsManager();
        $this->dataMapper = new Application_Model_DataMapper();
        $this->dataMapper->dbLink->delete('approval_entry');
        
        $this->dataMapper->dbLink->delete('item');
        $this->dataMapper->dbLink->delete('form');
        $this->dataMapper->dbLink->delete('privilege');
        $this->dataMapper->dbLink->delete('resource');
        $this->dataMapper->dbLink->delete('user_group');
        $this->dataMapper->dbLink->delete('scenario_entry');
        $this->dataMapper->dbLink->delete('scenario_assignment');
        $this->dataMapper->dbLink->delete('scenario');
        $this->dataMapper->dbLink->delete('user');
        $this->dataMapper->dbLink->delete('position');
        $this->dataMapper->dbLink->delete('node');
        /*  Lets prepare some staff: node, node, position, user, access control 
         *    We have: 
         *    1. One node with ID nodeId
         *    2. Two nodes, connected to this node
         *    3. Two positions, connected to nodes respectively
         *    4. Two users on these positions
         *    5. ACLs should allow: first user has Approval privileges to first node (therefore, to both nodes
         *          second user has 'read' access to first node and 'write' access to second node.
         *          Also, privilege grants first user access to Administrative page. User2 is restricted from accessing this page and subpages.
         */
//NODES
        $nodeArray = array('nodeName' => 'First node', 'parentNodeId' => -1, 'domainId' => 1);
        $node = new Application_Model_Node($nodeArray);
        $nodeId = $this->dataMapper->saveObject($node);

        $nodeArray3 = array('nodeName' => 'First object', 'parentNodeId' => $nodeId, 'domainId' => 1);
        $node3 = new Application_Model_Node($nodeArray3);
        $nodeId3 = $this->dataMapper->saveObject($node3);
        $nodeArray1 = array('nodeName' => 'Second object', 'parentNodeId' => $nodeId, 'domainId' => 1);
        $node1 = new Application_Model_Node($nodeArray1);
        $nodeId1 = $this->dataMapper->saveObject($node1);
        $nodeArray2 = array('nodeName' => 'Third bject', 'parentNodeId' => $nodeId3, 'domainId' => 1);
        $node2 = new Application_Model_Node($nodeArray2);
        $nodeId2 = $this->dataMapper->saveObject($node2);

// ELEMENTS
        $elementArray = array('elementName' => 'eName', 'domainId' => 1, 'elementCode' => 34);
        $element = new Application_Model_Element($elementArray);
        $this->assertTrue($element->isValid());
        $this->elementId1 = $this->dataMapper->saveObject($element);
        $elementArray1 = array('elementName' => 'eName1', 'domainId' => 1, 'elementCode' => 44);
        $element1 = new Application_Model_Element($elementArray1);
        $this->assertTrue($element1->isValid());
        $this->elementId2 = $this->dataMapper->saveObject($element1);


// POSITIONS        
        $positionArray = array('positionName' => 'First position', 'nodeId' => $nodeId, 'domainId' => 1);
        $position = new Application_Model_Position($positionArray);
        $positionId = $this->dataMapper->saveObject($position);
        $positionArray1 = array('positionName' => 'First position', 'nodeId' => $nodeId1, 'domainId' => 1);
        $position1 = new Application_Model_Position($positionArray1);
        $positionId1 = $this->dataMapper->saveObject($position1);

// USERS        
        $userArray = array('userName' => 'user1', 'domainId' => 1, 'login' => 'user login', 'password' => 'user password', 'positionId' => $positionId);
        $user = new Application_Model_User($userArray);
        $this->userId = $this->dataMapper->saveObject($user);
        $userArray1 = array('userName' => 'user2', 'domainId' => 1, 'login' => 'user login2', 'password' => 'user password', 'positionId' => $positionId1);
        $user1 = new Application_Model_User($userArray1);
        $this->userId1 = $this->dataMapper->saveObject($user1);

// RESOURCES
        $resourceArray = array('resourceName' => 'admin', 'domainId' => 1);
        $resource = new Application_Model_Resource($resourceArray);
        $resourceId = $this->dataMapper->saveObject($resource);

// PRIVILEGES        
        $privilegeArray = array('objectType' => 'node', 'objectId' => $nodeId, 'userId' => $this->userId, 'privilege' => 'approve', 'domainId' => 1);
        $privilege = new Application_Model_Privilege($privilegeArray);
        $this->dataMapper->saveObject($privilege);
        $privilegeArray1 = array('objectType' => 'node', 'objectId' => $nodeId, 'userId' => $this->userId1, 'privilege' => 'read', 'domainId' => 1);
        $privilege1 = new Application_Model_Privilege($privilegeArray1);
        $this->dataMapper->saveObject($privilege1);
        $privilegeArray2 = array('objectType' => 'node', 'objectId' => $nodeId1, 'userId' => $this->userId1, 'privilege' => 'write', 'domainId' => 1);
        $privilege2 = new Application_Model_Privilege($privilegeArray2);
        $this->dataMapper->saveObject($privilege2);
        $privilegeArray3 = array('objectType' => 'resource', 'objectId' => $resourceId, 'userId' => $this->userId, 'privilege' => 'read', 'domainId' => 1);
        $privilege3 = new Application_Model_Privilege($privilegeArray3);
        $this->dataMapper->saveObject($privilege3);

// USERGROUPS        
        $usergroupArray = array('userId' => $this->userId, 'role' => 'admin', 'domainId' => 1, 'userGroupName' => 'administrators');
        $usergroup = new Application_Model_Usergroup($usergroupArray);
        $this->dataMapper->saveObject($usergroup);
        $usergroupArray1 = array('userId' => $this->userId1, 'role' => 'manager', 'domainId' => 1, 'userGroupName' => 'managers');
        $usergroup1 = new Application_Model_Usergroup($usergroupArray1);
        $this->dataMapper->saveObject($usergroup1);
        
// SCENARIO
        $entryArray1 = array('domainId' => 1, 'orderPos' => 1, 'userId' => $this->userId, 'active' => true);
        $entryArray2 = array('domainId' => 1, 'orderPos' => 2, 'userId' => $this->userId1, 'active' => true);
        $scenarioArray1 = array('scenarioName' => 'eName1', 'active' => false, 'domainId' => 1, 'entries' => array(0 => $entryArray1, 1 => $entryArray2));
        $this->scenario = new Application_Model_Scenario($scenarioArray1);
        $this->scenarioId = $this->objectManager->saveScenario($this->scenario);
        $this->scenario = $this->objectManager->getScenario($this->scenarioId);
        
// Assignment
        $assignmentArray = array('domainId'=>1, 'nodeId'=>$nodeId1, 'scenarioId'=>$this->scenarioId);
        $assignment = new Application_Model_ScenarioAssignment($assignmentArray);
        $assignmentId = $this->dataMapper->saveObject($assignment);
        $this->assertTrue(is_int($assignmentId));
        
        
// FORM
        $itemArray1 = array('itemName' => 'item1', 'domainId' => 1, 'value' => 55.4, 'elementId' => $this->elementId1, 'active' => true);
        $itemArray2 = array('itemName' => 'item2', 'domainId' => 1, 'value' => 22.1, 'elementId' => $this->elementId2, 'active' => true);
        $formArray1 = array('userId' => $this->userId1, 'formName' => 'fName1', 'nodeId' => $nodeId1, 'items' => array(0 => $itemArray1, 1 => $itemArray2), 'domainId' => 1, 'active' => true);
        $form = new Application_Model_Form($formArray1);
//        Zend_Debug::dump($form);
        $this->assertTrue($form->isValid());
        $this->formId = $this->objectManager->saveForm($formArray1);

        $this->nodeId = $nodeId;
        $this->nodeId1 = $nodeId;
        $this->nodeId2 = $nodeId1;
        $this->nodeId3 = $nodeId2;
        parent::setUp();
    }

    public function tearDown() {
        $this->dataMapper->dbLink->delete('approval_entry');
        $this->dataMapper->dbLink->delete('item');
        $this->dataMapper->dbLink->delete('form');
        $this->dataMapper->dbLink->delete('element');
        $this->dataMapper->dbLink->delete('user_group');
        $this->dataMapper->dbLink->delete('privilege');
        $this->dataMapper->dbLink->delete('scenario_entry');
        $this->dataMapper->dbLink->delete('scenario_assignment');
        $this->dataMapper->dbLink->delete('scenario');
        $this->dataMapper->dbLink->delete('user');
        $this->dataMapper->dbLink->delete('position');
        $this->dataMapper->dbLink->delete('node');
    }

    
    public function testApprovalEntry(){
        $entryArray = array('domainId'=>1, 'userId'=>$this->userId, 'formId'=>$this->formId, 'decision'=>'approve');
        $entry = new Application_Model_ApprovalEntry($entryArray);
        $this->assertTrue($entry->isValid());
        $this->assertEquals($entry->userId, $this->userId);
        $this->assertEquals($entry->decision, 'approve');
        $entryId = $this->dataMapper->saveObject($entry);
        $this->assertTrue(is_int($entryId));
    }
    
    
    public function testApproveAction(){
        $session = new Zend_Session_Namespace('Auth');
        $session->domainId = 1;
        $result = $this->objectManager->approveForm($this->formId, $this->userId,'approve');
        $this->assertTrue(is_int($result));
        $result1 = $this->objectManager->approveForm($this->formId, $this->userId,'approve');
        $this->assertTrue(is_int($result1));
        $result2 = $this->objectManager->approveForm($this->formId, $this->userId1,'approve');
        $this->assertTrue(is_int($result2));
        $approvals = $this->dataMapper->getAllObjects('Application_Model_ApprovalEntry', array(0=>array('column'=>'formId', 'operand'=>$this->formId)));
        $this->assertEquals(count($approvals), 2);
        $this->assertTrue(!empty($approvals));
    }
    
    public function testWrongOrderApproveAction(){
        $session = new Zend_Session_Namespace('Auth');
        $session->domainId = 1;
        $result = $this->objectManager->approveForm($this->formId, $this->userId1,'approve');
        $this->assertFalse($result);
        $result = $this->objectManager->approveForm($this->formId, $this->userId,'approve');
        $this->assertTrue(is_int($result));
        $result1 = $this->objectManager->approveForm($this->formId, $this->userId1,'approve');
        $this->assertTrue(is_int($result1));
        $result = $this->objectManager->approveForm($this->formId, $this->userId,'decline');
        $this->assertFalse($result);
        $approvals = $this->dataMapper->getAllObjects('Application_Model_ApprovalEntry', array(0=>array('column'=>'formId', 'operand'=>$this->formId), 1=>array('column'=>'userId', 'operand'=>$this->userId)));
        $this->assertTrue(!empty($approvals));
        $this->assertEquals($approvals[0]->decision, 'approve');
    }
    
    public function testApprovalAllowance(){
        $session = new Zend_Session_Namespace('Auth');
        $session->domainId = 1;
        $this->assertFalse($this->objectManager->isApprovalAllowed($this->formId, $this->userId1));
        $this->assertTrue($this->objectManager->isApprovalAllowed($this->formId, $this->userId));
    }
    
}

