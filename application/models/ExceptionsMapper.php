<?php

class JSONExceptions extends Zend_Exception {

    protected $errorCode;
    protected $translate;
    protected $paththrough;
    protected $config;

    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        $registry = Zend_Registry::getInstance();
        $this->translate = $registry->get('Zend_Translate');
        $this->config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        if ($this->config->app->exceptions_passthrough) {
            $msg .=  $this->getTraceAsString();
        }
        parent::__construct($msg, $code, $previous);
    }

    public function errorToArray() {
        return array('error' => 1,
            'message' => $this->message,
            'code' => $this->code,
            'errorCode' => $this->errorCode);
    }

}

class DependantObjectDeletionAttempt extends JSONExceptions {

    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        //Lets try to translate error message
        parent::__construct($msg, $this->code, $previous);
        $this->message = $this->translate->_('Cannot delete object - other objects depend on it') . PHP_EOL . $this->message;
        $this->errorCode = $code;
        $this->code = 409;
        // Check if we have to print debug info about error

    }

}

class UnableToLoadMessageTemplate extends JSONExceptions {
    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        //Lets try to translate error message
        parent::__construct($msg, $this->code, $previous);
        $this->message = $this->translate->_('Cannot load required template.') . PHP_EOL . $this->message;
        $this->errorCode = $code;
        $this->code = 500;
        // Check if we have to print debug info about error
    }
    
}

class NonExistingObjectProperty extends JSONExceptions {
    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        //Lets try to translate error message
        parent::__construct($msg, $this->code, $previous);
        $this->message = $this->translate->_('Cannot set property, property doesnt exist.') . PHP_EOL . $this->message;
        $this->errorCode = $code;
        $this->code = 406;
        // Check if we have to print debug info about error
    }
}

class WrongApprovalOrder extends JSONExceptions {
    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        //Lets try to translate error message
        parent::__construct($msg, $this->code, $previous);
        $this->message = $this->translate->_('User cannot approve at this time.') . PHP_EOL . $this->message;
        $this->errorCode = $code;
        $this->code = 406;
        // Check if we have to print debug info about error
    }
    
}

class SaveObjectException extends JSONExceptions {
    public function __construct($msg = '', $code = 0, \Exception $previous = null) {
        
        parent::__construct('Error while trying to save object: ' . ((is_object($msg)) ? get_class($msg) : ' not an object'), $this->code, $previous);
        $this->message = $this->message . PHP_EOL . Zend_Debug::dump($msg, 'Class dump: ', false) . PHP_EOL;
        $this->errorCode = $code;
        $this->code = 406;
        // Check if we have to print debug info about error
    }
    
}