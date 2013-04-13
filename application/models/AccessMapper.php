<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AccessMapper
 *
 * @author Olenka
 */
require_once 'BaseDBAbstract.php';

class Application_Model_AccessMapper extends BaseDBAbstract {

    private $user;
    private $credentials; // credentials from DB
    // As we have nodes and orgobjects as subjects of conditinal access
    // we have to transform credentials from their form as they are stored
    // For ex. if node1 includes node2 and orgobject3 we have to transform
    // single record (node1, 'read') to (node2, 'read') (orgobject3, 'read')
    // then for node2 repeat this procedure.
    // Result we store in $orgobjectPrivileges array();
    private $orgobjectPrivileges; // credentials prepared for future use. 
    public $acl;

    public function __construct($userId = null) {
        parent::__construct();
        $dataMapper = new Application_Model_DataMapper();
        if (empty($userId)) {
            $session = new Zend_Session_Namespace('auth');
            $userId = $session->userId;
        }
        $this->acl = new Zend_Acl();
        $this->acl->addResource('admin');
        $this->acl->addResource('node', 'admin');
        $this->acl->addResource('element', 'admin');
        $this->acl->addResource('user', 'admin');
        $this->acl->addResource('position', 'admin');
        $this->acl->addResource('privilege', 'admin');
        $this->acl->addResource('scenario', 'admin');
        $this->acl->addResource('error');
        $this->acl->addResource('form');
        $this->acl->addResource('new-form', 'form');
        $this->acl->addResource('open-form', 'form');
        $this->acl->addResource('add-form', 'form');
        $this->acl->addResource('auth');
        $this->acl->addResource('home');
        $this->acl->addRole('guest');
        $this->acl->addRole('staff', 'guest');
        $this->acl->addRole('manager', 'staff');
        $this->acl->addRole('admin', null);
        $this->acl->deny('guest', null);
        $this->acl->allow('guest', 'error');
        $this->acl->allow('guest', 'auth');
        $this->acl->allow('guest', 'home');
// ++++++++++++++++++++++++ FIX ME ++++++++++++++++++++
        $this->acl->allow('guest', 'form');
// ++++++++++++++++++++++++++++++++++++++++++++++++++++        
        $this->acl->allow('admin', 'admin');
        if ($userId) {
            $this->user = $dataMapper->getObject($userId, 'Application_Model_User');
            if (!$this->user) {
                // We have session variables set up but for some reason user doesnt exist
                Zend_Session::destroy();
                return;
            }
            $this->credentials = $dataMapper->getAllObjects('Application_Model_Privilege', array(0 => array('column' => 'userId',
                    'operand' => $userId)));

            // Create new role based on user login
            // Retrieve parent privilege group fo user. If no group, user's base group is 'guest'
            $userRole = $dataMapper->getAllObjects('Application_Model_UserGroup', array(0 => array('column' => 'userId',
                    'operand' => $userId)));
            if (!($userRole[0] instanceof Application_Model_UserGroup)) {
                $role = 'guest';
            } else {
                $role = $userRole[0]->role;
            }

            // Add new role based on user login with parent role
            $this->acl->addRole($this->user->login, $role);
            // Check for user's credentials
            if (is_array($this->credentials) && !empty($this->credentials)) {
                foreach ($this->credentials as $credential) {
                    // If user is granted some additional privileges to resources access besides his usergroup lets add them
                    if ('resource' == $credential->objectType) {
                        $resource = $dataMapper->getObject($credential->objectId, 'Application_Model_Resource');
                        $resourceName = $resource->resourceName;
                    } else {
                        // If user is granted privileges to some actions 
                        $resourceName = $credential->objectType . '_' . $credential->objectId;
                        if (!$this->acl->has($resourceName)) {
                            $this->acl->addResource($resourceName);
                        }
                    }
                    $this->acl->allow($this->user->login, $resourceName, $credential->privilege);
                }
            } else {
                return;
                //throw new Exception('No privileges loaded from DB');
            }
        }
        // userId parameter wasn't suppluyed
        // Assume that user uses default admin login
        else {
            if (!$this->acl->hasRole($this->config->default->adminlogin)) {
                $this->acl->addRole($this->config->default->adminlogin, 'admin');
            }
        }
    }

    /**
     * Reinitialize credentials for user
     * 
     * @param type $userId
     */
    public function reinit($userId = null) {
        self::__construct($userId);
    }

    /**
     * Addon to standart ACL->isAllowed() procedure.
     * As we use dynamic resources table first we check if resource exists in user's table.
     * If not - user is denyed access to this resource. By default everything is denied.
     * If yes - we use standart acl->isAllowed method to determine user privilege.
     * 
     * @param type $user
     * @param type $resource
     * @param type $privilege
     * @return boolean
     * 
     */
    public function isAllowed($user, $resourceType, $privilege = null, $resourceId = null) {
        if (empty($resourceId)) {
            $resource = $resourceType;
        } else {
            $resource = $resourceType . '_' . $resourceId;
        }
        if (!$this->acl->has($resource)) {
            return false;
        }
//        echo $user.' => ' .$privilege. ' for '.$resource.': '. $this->acl->isAllowed($user, $resource, $privilege).PHP_EOL;   
        return $this->acl->isAllowed($user, $resource, $privilege);
    }

    /**
     * getAllowedObjectsIds() method returns ids of objects of specified class that 
     * user can read, write or approve.
     * @param string $class
     * @param string $privilege
     * @return array
     * 
     */
    public function getAllowedObjectIds() {
//        Zend_Debug::dump($this->credentials);
        if (is_array($this->credentials)) {
            foreach ($this->credentials as $credential) {
                if ('node' == $credential->objectType) {
                    if (empty($result[$credential->privilege])) {
                        $result[$credential->privilege] = $this->getNodeObjects($credential->objectId);
                    } else {
                        $result[$credential->privilege] = array_merge($result[$credential->privilege], $this->getNodeObjects($credential->objectId));
                    }
                } 
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * getNodeObjects - recursive function that works in connection with getAllowedOrgobjectIds()
     * @param type $nodeId
     * @return type
     */
    private function getNodeObjects($nodeId) {
        $dataMapper = new Application_Model_DataMapper();
        $result[] = $nodeId;
        $nodes = $dataMapper->getAllObjects('Application_Model_Node', array(0 => array('column' => 'parentNodeId',
                'operand' => $nodeId)));
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                if (!empty($result)) {
                    $result = array_merge($result, $this->getNodeObjects($node->nodeId));
                } else {
                    $result = $this->getNodeObjects($node->nodeId);
                }
            }
        }
        return $result;
    }

}

?>
