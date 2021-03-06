<?php

/**
 * Description of PrivilegeDataMapper
 *
 * @author Max
 */
require_once TESTS_PATH . '/application/TestCase.php';

class PrivilegeDataMapperTest extends TestCase {

    private $objectManager;
    private $userId;
    
    public function setUp()
    {
        $this->objectManager = new Application_Model_ObjectsManager(1, 'Application_Model_Privilege');
        $nodeArray = array('nodeName' => 'First node', 'parentNodeId' => -1, 'domainId' => 1);
        $node = new Application_Model_Node($nodeArray);
        $nodeId = $this->objectManager->saveObject($node);
        $positionArray = array('positionName' => 'First position', 'nodeId' => $nodeId, 'domainId' => 1);
        $position = new Application_Model_Position($positionArray);
        $positionId = $this->objectManager->saveObject($position);
        $userArray = array('userName' => 'user1', 'domainId' => 1, 'login' => 'user login', 'password' => 'user password', 'positionId' => $positionId);
        $user = new Application_Model_User($userArray);
        $this->userId = $this->objectManager->saveObject($user);
    }
    
    public function tearDown()
    {
        
    }
    
    public function testPrivilegeDataMapperSaveNew()
    {
        // Create and save privilege
        $objectManagerArray = array('userId'=>$this->userId,'aclId'=>3,'objectId'=>4, 'objectType' =>'node', 'domainId' => 1, 'privilege' => 'approve');
        $privilege = new Application_Model_Privilege($objectManagerArray);
        $this->assertTrue($privilege instanceof Application_Model_Privilege);
        $id = $this->objectManager->saveObject($privilege);
        $this->assertTrue(is_int($id));
        $privilege->privilegeId = $id;
        
        // Retrive privilege from DB and check its consistancy
        $privilege2 = $this->objectManager->getObject('privilege', $id);
        $this->assertTrue($privilege2 instanceof Application_Model_Privilege);
        $this->assertEquals($privilege, $privilege2);
    }
    

    public function testObjectDataMapperSaveExisting()
    {
        $objectManagerArray = array('userId'=>$this->userId,'aclId'=>3,'objectId'=>4, 'objectType'=>'node', 'domainId' => 1, 'privilege' => 'write');
        $objectManager = new Application_Model_Privilege($objectManagerArray);
        $id = $this->objectManager->saveObject($objectManager);
        
        $this->assertTrue(is_int($id));
        $objectManager2 = $this->objectManager->getObject('privilege', $id);
        $this->assertTrue($objectManager2 instanceof Application_Model_Privilege);
       
 //       $this->assertEquals($objectManager, $objectManager2);
       /* $id2 = $this->objectManager->saveObject($objectManager2);
         * 
         */
    }
    
    
    /**
     * 
     * @expectedException Exception
     */
    public function testDeleteDependentObject() {
        $nodeArray = array('nodeName' => 'lName', 'parentNodeId' => 8, 'active' => false, 'domainId' => 5);
        $node = new Application_Model_Node($nodeArray);
        $nodeId = $this->objectManager->saveObject($node);
        $objectManagerArray = array('aclName'=>'oName','aclId'=>3,'nodeId'=>$nodeId, 'domainId' => 4);
        $objectManager = new Application_Model_Privilege($objectManagerArray);
        $objectManagerId = $this->objectManager->saveObject($objectManager);
        $objectManager = new Application_Model_ObjectsManager(4);
        $objectManager->deleteObject($nodeId, 'Application_Model_Node');
    }
}

?>
