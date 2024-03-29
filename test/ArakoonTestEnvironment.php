<?php

/**
 * This file is part of Arakoon, a distributed key-value store. 
 * Copyright (C) 2010 Incubaid BVBA
 * Licensees holding a valid Incubaid license may use this file in
 * accordance with Incubaid's Arakoon commercial license agreement. For
 * more information on how to enter into this agreement, please contact
 * Incubaid (contact details can be found on http://www.arakoon.org/licensing).
 * 
 * Alternatively, this file may be redistributed and/or modified under
 * the terms of the GNU Affero General Public License version 3, as
 * published by the Free Software Foundation. Under this license, this
 * file is distributed in the hope that it will be useful, but WITHOUT 
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the 
 * GNU Affero General Public License along with this program (file "COPYING").
 * If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @copyright Copyright (C) 2010 Incubaid BVBA
 */

/**
 * ArakoonTestClient class (singleton)
 */

require_once "../Arakoon/Client.php";

class ArakoonTestEnvironment
{
	private static $_instance = NULL;
	
	private $_client;	
	private $_arakoonExeCmd;
	private $_configFilePath;
	
	/**
	 * @todo document
	 */
	private function __construct()
	{

	}
	
	/**
	 * @todo document
	 */
	public function getClient()
	{
		return $this->_client;
	}

        public function getConfigFilePath()
        {

            return $this->_configFilePath;
        }

        public function setConfigclient($config)
        {

            $this->_client = new Arakoon_Client($config);
        }
	/**
	 * @todo document
	 */
    static public function getInstance()
    {
        if (self::$_instance == NULL)
        {
        	self::$_instance = new ArakoonTestEnvironment();
        }
        
        return self::$_instance;
    }

    /**
	 * @todo document
	 */
	public function setup($config, $arakoonExeCmd, $configFilePath, $autoStart = TRUE)
	{
		if ($this->_client != null)
		{
			throw new Exception("Test environment already running");		
		}
		
		// setup nodes
		$this->_client = new Arakoon_Client($config);	
		$this->_arakoonExeCmd = $arakoonExeCmd;
		$this->_configFilePath = $configFilePath;
		
		// create arakoon directories
		foreach ($config->getNodes() as $node)
		{
			$id = $node->getId();
			$homeDir = $node->getHome();
			if (!file_exists($homeDir))
			{
				mkdir($homeDir);
			}
		}
		
		if ($autoStart)
		{
			$this->startAllNodes();
			sleep(1); // sleep 1 second to ensure Arakoon nodes are up
		}
	}
	
	/**
	 * @todo document
	 */
	public function tearDown()
	{
		$this->stopAllNodes();

		// remove arakoon directories		
		$config = $this->_client->getConfig();
		foreach ($config->getNodes() as $nodeId => $node)
		{
			$homeDir = $node->getHome();
			if (file_exists($homeDir))
			{
				$this->recursiveRemoveDir($homeDir);
			}
		}
	}
	
	/**
	 * @todo document
	 */
	private function recursiveRemoveDir($dir)
	{
		$removeSucces = TRUE;
	
		if(substr($dir,-1) == "/")
		{
			$dir = substr($dir, 0, -1);
		}
	
		if (file_exists($dir) && is_dir($dir) || is_readable($dir))
		{
			$dirHandle = opendir($dir);
				
			while ($contents = readdir($dirHandle))
			{
				if($contents != "." && $contents != "..")
				{
					$path = $dir . "/" . $contents;
						
					if(is_dir($path))
					{
						$this->recursiveRemoveDir($path);
					}
					else
					{
						unlink($path);
					}
				}
			}
			rmdir($dir);
		}
		else
		{
			$removeSucces = FALSE;
		}
	}

	/**
	 * @todo document
	 */
  	public function stopNotMaster()
    {
        $master = $this->_client->whoMaster();
        $config = $this->_client->getConfig();
        
        foreach ($config->getNodes() as $node)
       	{
     		$nodeId = $node->getId();
     		
         	if ($nodeId != $master)
           	{
          		$this->stopNode($nodeId);
          		break;
          	}
        }
    }

    /**
	 * @todo document
	 */
	public function stopMaster()
	{
      	$master = $this->_client->whoMaster();
      	$this->stopNode($master);
  	}
  	
  	/**
	 * @todo document
	 */
  	public function startNode($nodeId)
    {
    	//$staticLibLink = "LD_LIBRARY_PATH=/home/jonas/incubaid/projects/arakoon/ROOT/OCAML/lib";
    	$staticLibLink = "LD_LIBRARY_PATH=/usr/local/lib";
     	shell_exec("$staticLibLink $this->_arakoonExeCmd -config $this->_configFilePath -daemonize --node $nodeId");
   	}
   	
   	/**
	 * @todo document
	 */
	public function startAllNodes()
    {
    	$config = $this->_client->getConfig();
    	
    	foreach ($config->getNodes() as $node)
        {
        	$nodeId = $node->getId();        	
        	$this->startNode($nodeId);
		}
    }
    
    /**
	 * @todo document
	 */
   	public function stopNode($nodeId)
   	{
   		shell_exec("pkill -f $nodeId");
   	}

   	/**
	 * @todo document
	 */
  	public function stopAllNodes()
    {
    	$config = $this->_client->getConfig();
    	
    	foreach ($config->getNodes() as $node)
        {
        	$nodeId = $node->getId();        	
        	$this->stopNode($nodeId);
		}
    }
}
?>
