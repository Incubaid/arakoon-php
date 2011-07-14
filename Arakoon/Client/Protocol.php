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

require_once 'Logger.php';

/**
 * Arakoon_Client_Protocol
 *
 * @category   	Arakoon
 * @package    	Arakoon_Client
 * @copyright 	Copyright (C) 2010 Incubaid BVBA
 * @license    	http://www.arakoon.org/licensing
 */
class Arakoon_Client_Protocol
{
	/**
	 * Operation codes
	 */
	const OP_CODE_DELETE					= 0x0000000a;
	const OP_CODE_EXISTS					= 0x07;
	const OP_CODE_EXPECT_PROGRESS_POSSIBLE	= 0x00000012;
	const OP_CODE_GET						= 0x00000008;
	const OP_CODE_HELLO						= 0x00000001;
	const OP_CODE_MAGIC 					= 0xb1ff0000;
	const OP_CODE_MULTI_GET					= 0x00000011;
	const OP_CODE_PREFIX					= 0x0000000c;
	const OP_CODE_RANGE						= 0x0000000b;
	const OP_CODE_RANGE_ENTRIES				= 0x0000000f;
	const OP_CODE_SEQUENCE					= 0x00000010;
	const OP_CODE_SET						= 0x00000009;
	const OP_CODE_STATISTICS				= 0x00000013;
	const OP_CODE_TEST_AND_SET				= 0x0000000d;
	const OP_CODE_VERSION					= 0x00000001;
	const OP_CODE_WHO_MASTER 				= 0x00000002;
	
	/**
	 * Sequenced operation codes
	 */
	const OP_CODE_SEQUENCED_DELETE			= 2;
	const OP_CODE_SEQUENCED_SET				= 1;
	const OP_CODE_SEQUENCED_SEQUENCE		= 5;
	
	/**
	 * Result codes
	 */
    const RESULT_CODE_SUCCES 		= 0;
    const RESULT_CODE_NOT_MASTER 	= 4;
    const RESULT_CODE_NOT_FOUND 	= 5;
    const RESULT_CODE_WRONG_CLUSTER	= 6;
    
	/**
	 * Type sizes
	 */
    const TYPE_SIZE_BOOL = 1;
    const TYPE_SIZE_FLOAT = 8;
    const TYPE_SIZE_INT_32 = 4;
    const TYPE_SIZE_INT_64 = 8;    

	/**
     * Constructor of an Arakoon_Client_Protocol object.
     * It is a private constructor because the class only contains static functions.
     * 
     * @return void
     */
    private function __construct()
    {
    	
    }
    
    
    /**
	 * ============================================================================================================
	 * Pack functions
	 * ====================================================================================================
	 */
    
	/**
     * @todo document
     */
	public static function packBool($data)
	{
		$buffer = pack('c', $data); // char format is used because there is no boolean format available
    	return $buffer;
	}
	
    /**
     * @todo document
     */
	public static function packInt($data)
	{
	    $buffer =  pack('I', $data);
	    return $buffer;
	}
	
	/**
     * @todo document
     */
	public static function packSignedInt($data)
	{
	    $buffer =  pack('i', $data);
	    return $buffer;
	}
	
	/**
     * @todo document
     */
	public static function packString($data)
	{
    	$buffer =  pack('Ia*', strlen($data), $data);
    	return $buffer;
	}
	
	/**
     * @todo document
     */
	public static function packStringOption($data)
	{
		$buffer = '';
		
		if (empty($data))
		{
	        $buffer .= self::packBool(0);
	    }
	    else
	    {
	        $buffer .= self::packBool(1);
	        $buffer .= self::packString($data);
	    }
	    
	    return $buffer;
	}
	
	
	/**
	 * ====================================================================================================
	 * Unpack functions
	 * ====================================================================================================
	 */
	
	/**
     * @todo document
     */
	public static function unpackBool($buffer)
	{
		$data = unpack('cbool', $buffer);
		return $data['bool'];
	}
	
	/**
     * Unpacks a string.
     * 
     * @param 	$buffer
     * @param 	$offset
     * @return 	array
     */
	public static function unpackEntireString($buffer)
	{
	    $data = unpack('a*string', $buffer);
		return $data['string'];
	}
	
	/**
     * @todo document
     */
	public static function unpackFloat($buffer, $offset)
	{
	    if($offset > 0)
	    {
	        $data = unpack("c{$offset}char/dfloat", $buffer);
	    }
	    else
	    {
	        $data = unpack('dfloat', $buffer);
	    }    
	    
	    return array($data['float'], $offset + self::TYPE_SIZE_FLOAT);
	}

	/**
     * Unpacks an integer.
     * 
     * @param	$data signed integer that needs to be packed
     * @return 	packed data
     */
	public static function unpackInt($buffer, $offset)
	{
	    if($offset > 0)
	    {
	    	$data = unpack("c{$offset}char/Iint", $buffer);
	    }
	    else
	    {
	        $data = unpack('Iint', $buffer);
	    }
	    
	    return array($data['int'], $offset + self::TYPE_SIZE_INT_32);
	}
	
	private static function _Make64($hi, $lo)
	{
		// on x64, we can just use int
		if ( ((int)4294967296)!=0 )
			return (((int)$hi)<<32) + ((int)$lo);
	
		// workaround signed/unsigned braindamage on x32
		$hi = sprintf ( "%u", $hi );
		$lo = sprintf ( "%u", $lo );
	
		// use GMP or bcmath if possible
		if ( function_exists("gmp_mul") )
			return gmp_strval ( gmp_add ( gmp_mul ( $hi, "4294967296" ), $lo ) );
	
		if ( function_exists("bcmul") )
			return bcadd ( bcmul ( $hi, "4294967296" ), $lo );
	
		// compute everything manually
		$a = substr ( $hi, 0, -5 );
		$b = substr ( $hi, -5 );
		$ac = $a*42949; // hope that float precision is enough
		$bd = $b*67296;
		$adbc = $a*67296+$b*42949;
		$r4 = substr ( $bd, -5 ) +  + substr ( $lo, -5 );
		$r3 = substr ( $bd, 0, -5 ) + substr ( $adbc, -5 ) + substr ( $lo, 0, -5 );
		$r2 = substr ( $adbc, 0, -5 ) + substr ( $ac, -5 );
		$r1 = substr ( $ac, 0, -5 );
		while ( $r4>100000 ) { $r4-=100000; $r3++; }
		while ( $r3>100000 ) { $r3-=100000; $r2++; }
		while ( $r2>100000 ) { $r2-=100000; $r1++; }
	
		$r = sprintf ( "%d%05d%05d%05d", $r1, $r2, $r3, $r4 );
		$l = strlen($r);
		$i = 0;
		while ( $r[$i]=="0" && $i<$l-1 )
			$i++;
		return substr ( $r, $i );
	}

	/**
     * @todo document
     */
	public static function unpackInt64($buffer, $offset)
	{

		$data = unpack('Iint', substr($buffer, $offset));
		return array($data['int'], $offset + self::TYPE_SIZE_INT_64);
	}
		
	/**
     * Unpacks a string.
     * 
     * @param 	$buffer
     * @param 	$offset
     * @return 	array
     */
	public static function unpackString($buffer, $offset)
	{
	    list($size, $offset2) = self::unpackInt($buffer, $offset);
	    $string = substr($buffer, $offset2, $size);
	    return array($string, $offset2 + $size);    
	}
	
	
	/**
	 * ====================================================================================================
	 * Read functions
	 * ====================================================================================================
	 */
	
	/**
     * @todo document
     */
	private static function readBool($connection)
	{
    	$buffer = $connection->readBytes(self::TYPE_SIZE_BOOL);
    	return self::unpackBool($buffer);
	}
	
	/**
     * @todo document
     */
	private static function readFloat($connection)
	{
    	$buffer = $connection->readBytes(self::TYPE_SIZE_FLOAT);    
	    list($float, $offset) = self::unpackFloat($buffer, 0);
	    return $float;
	}
	
	/**
     * @todo document
     */
	private static function readInt($connection)
	{
    	$buffer = $connection->readBytes(self::TYPE_SIZE_INT_32);    
	    list($integer, $offset) = self::unpackInt($buffer, 0);
	    return $integer;
	}
		
	/**
     * @todo document
     */
	private static function readString($connection)
	{
	    $byteCount = self::readInt($connection);
	    $buffer = $connection->readBytes($byteCount);
	    return self::unpackEntireString($buffer);
	}

	/**
     * @todo document
     */
	private static function readStringOption($connection)
	{
	    $isSet = self::readBool($connection);
	    $string = NULL;
	    
	    if($isSet)
	    {
	        $string = self::readString($connection);
	    }
	    
	    return $string;
	}
	
	
	/**
	 * ====================================================================================================
	 * Encode functions
	 * ====================================================================================================
	 */
	
	/**
     * @todo document
     */
	static function encodeDelete($key)
	{
        $buffer = self::packInt(self::OP_CODE_DELETE | self::OP_CODE_MAGIC);
        $buffer .= self::packString($key);
        
        return $buffer;
    }
    
    /**
     * @todo document
     */
	public static function encodeExpectProgressPossible()
	{
        $buffer = self::packInt(self::OP_CODE_EXPECT_PROGRESS_POSSIBLE | self::OP_CODE_MAGIC);
        
        return $buffer;
    }
    
	/**
     * @todo document
     */
	public static function encodeExists($key, $allowDirtyRead)
	{
        $buffer = self::packInt(self::OP_CODE_EXISTS | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packString($key);
        
        return $buffer;
    }    
        
	/**
     * @todo document
     */
	public static function encodeGet($key, $allowDirtyRead)
	{
		$buffer = self::packInt(self::OP_CODE_GET | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packString($key);
        
        return $buffer;
	}
	
	/**
     * @todo document
     */
	public static function encodeHello($clientId, $clusterId)
	{
        $buffer  = self::packInt(self::OP_CODE_HELLO | self::OP_CODE_MAGIC);
        $buffer .= self::packString($clientId);
        $buffer .= self::packString($clusterId);
        
        return $buffer;
    }
	
	/**
     * @todo document
     */
	public static function encodeMultiGet($keys, $allowDirtyRead)
	{
		$buffer = self::packInt(self::OP_CODE_MULTI_GET | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packInt(count($keys));
        
        foreach ($keys as $key)
        {
            $buffer .= self::packString($key);
        }
        
        return $buffer;
    }
    
    /**
     * @todo document
     */
	static function encodePrefix($key, $maxElements, $allowDirtyRead)
	{
        $buffer = self::packInt(self::OP_CODE_PREFIX | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packString($key);
        $buffer .= self::packSignedInt($maxElements);
        
        return $buffer;
    }
    
	/**
     * @todo document
     */
	public static function EncodePrologue($clusterId)
	{
    	$buffer = self::packInt(self::OP_CODE_MAGIC);
    	$buffer .= self::packInt(self::OP_CODE_VERSION);
    	$buffer .= self::packString($clusterId);
    	
    	return $buffer;
	}
	
	/**
     * @todo document
     */
	public static function encodeRange($beginKey, $includeBeginKey, $endKey, $includeEndKey, $maxElements , $allowDirtyRead)
	{
        $buffer = self::packInt(self::OP_CODE_RANGE | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packStringOption($beginKey);
        $buffer .= self::packBool($includeBeginKey);
        $buffer .= self::packStringOption($endKey);
        $buffer .= self::packBool($includeEndKey);
        $buffer .= self::packSignedInt($maxElements);
        
        return  $buffer;
    }

    /**
     * @todo document
     */
    public static function encodeRangeEntries($beginKey, $includeBeginKey, $endKey, $includeEndKey, $maxElements , $allowDirtyRead)
    {
        $buffer = self::packInt(self::OP_CODE_RANGE_ENTRIES | self::OP_CODE_MAGIC);
        $buffer .= self::packBool($allowDirtyRead);
        $buffer .= self::packStringOption($beginKey);
        $buffer .= self::packBool($includeBeginKey);
        $buffer .= self::packStringOption($endKey);
        $buffer .= self::packBool($includeEndKey);
        $buffer .= self::packSignedInt($maxElements);
        
        return  $buffer;
    }
        
	/**
     * @todo document
     */
	public static function encodeSet($key, $value)
	{
        $buffer = self::packInt(self::OP_CODE_SET | self::OP_CODE_MAGIC);
        $buffer .= self::packString($key);
        $buffer .= self::packString($value);
        
        return $buffer;
    }    

    /**
     * @todo document
     */
    public static function encodeStatistics()
    {
        $buffer = self::packInt(self::OP_CODE_STATISTICS | self::OP_CODE_MAGIC);
        
        return $buffer;
    }
    
    /**
     * @todo document
     */
	public static function encodeTestAndSet($key, $oldValue, $newValue)
	{
        $buffer = self::packInt(self::OP_CODE_TEST_AND_SET | self::OP_CODE_MAGIC);
        $buffer .= self::packString($key);
        $buffer .= self::packStringOption($oldValue);
        $buffer .= self::packStringOption($newValue);
        
        return $buffer;
    }
    
	/**
     * @todo document
     */
    public static function encodeWhoMaster()
    {
        //return self::packInt(self::OP_CODE_WHO_MASTER | self::OP_CODE_MAGIC);
        $buffer = pack("I", self::OP_CODE_WHO_MASTER | self::OP_CODE_MAGIC);;
        
        return $buffer; 
    }
    
    
    /**
	 * ====================================================================================================
	 * Decode functions
	 * ====================================================================================================
	 */
    
	/**
     * @todo document
     */
	public static function decodeBoolResult($connection)
	{
    	self::evaluateResultCode($connection);
    	$bool = self::readBool($connection);
        
    	return $bool;
	}
    
	/**
     * @todo document
     */
	public static function decodeStatisticsResult($connection)
	{
        self::evaluateResultCode($connection);
        $statistics = array();
        $buffer = self::readString($connection);
        
        $offset0 = 0;
        list($start, $offset1)   		= self::unpackFloat($buffer, $offset0);
        list($last, $offset2)        	= self::unpackFloat($buffer, $offset1);
        list($avg_set_size, $offset3) 	= self::unpackFloat($buffer, $offset2);
        list($avg_get_size, $offset4) 	= self::unpackFloat($buffer, $offset3);
        list($n_sets, $offset5)       	= self::unpackInt($buffer, $offset4);
        list($n_gets, $offset6)      	= self::unpackInt($buffer, $offset5);
        list($n_deletes, $offset7)    	= self::unpackInt($buffer, $offset6);
        list($n_multigets, $offset8)  	= self::unpackInt($buffer, $offset7);
        list($n_sequences, $offset9) 	= self::unpackInt($buffer, $offset8);
        list($n_entries, $offset10)  	= self::unpackInt($buffer, $offset9);
        
        $node_is = array();
        $cycleOffset = $offset10;
        for ($i = 0; $i < $n_entries; $i++)
        {
            list($name, $tempOffset1) = self::unpackString($buffer, $cycleOffset);
            list($integer, $tempOffset2) = self::unpackInt64($buffer, $tempOffset1);
            $node_is[$name] = $integer;
            
            $cycleOffset = $tempOffset2;
        }
        
        $statistics['start'] = $start;
        $statistics['last'] = $last;
        $statistics['avg_set_size'] = $avg_set_size;
        $statistics['avg_get_size'] = $avg_get_size;
        $statistics['n_sets'] = $n_sets;
        $statistics['n_gets'] = $n_gets;
        $statistics['n_deletes'] = $n_deletes;
        $statistics['n_multigets'] = $n_multigets;
        $statistics['n_sequences'] = $n_sequences;
        $statistics['node_is'] = $node_is;
        
        return $statistics;
    }
    
    /**
     * @todo document
     */
	public static function decodeStringResult($connection)
	{
        self::evaluateResultCode($connection);
        $string = self::readString($connection);
        
        return $string; 
    }
    
	public static function decodeStringListResult($connection)
	{
        self::evaluateResultCode($connection);        
        $list = array();
        $listLength = self::readInt($connection);

        for($i = 0; $i < $listLength; $i++)
        {
            array_unshift($list, self::readString($connection));
        }
        
        return $list;
    }
    
	/**
     * @todo document
     */
	public static function decodeStringOptionResult($connection)
	{
        self::evaluateResultCode($connection);
        $stringOption = self::readStringOption($connection);
        
        return $stringOption; 
    }
    
    /**
     * @todo document
     */
	static function decodeStringPairListResult($connection)
	{
        self::evaluateResultCode($connection);
        $list = array();
        $listLength = self::readInt($connection);

        for($i = 0; $i < $listLength; $i++)
        {
            $key = self::readString($connection);
            $value = self::readString($connection);
            array_unshift($list, array($key, $value));
        }

        return $list;
    }
    
	/**
     * @todo document
     */
	public static function decodeVoidResult($connection)
	{
        self::evaluateResultCode($connection);
    }
    
    
   /**
	 * ====================================================================================================
	 * Other functions
	 * ====================================================================================================
	 */
         
    /**
     * @todo document
     */
    private static function evaluateResultCode($connection)
    {
        $resultCode = self::readInt($connection);
                
        if ($resultCode != self::RESULT_CODE_SUCCES)
        {
        	$resultMsg = self::readString($connection);
        	
	        switch($resultCode)
	        {	        		
	        	case self::RESULT_CODE_NOT_FOUND:
	        		$exceptionMsg = 'A \'not found\'';
	        		break;
	            	            	
	        	case self::RESULT_CODE_NOT_MASTER:
	        		$exceptionMsg = 'A \'not master\'';
	        		break;
	        		
        		case self::RESULT_CODE_WRONG_CLUSTER:
	        		$exceptionMsg = 'A \'wrong cluster\'';
	        		break;
	        		
	        	default:
	        		$exceptionMsg = 'An ';
	        		break;	            
	        }
	        
	        $exceptionMsg .= " error occured (code: $resultCode) while executing an Arakoon operation (message: $resultMsg)";
	        	        	        
	        Arakoon_Client_Logger::logError($exceptionMsg, __FILE__, __FUNCTION__, __LINE__);
	        throw new Exception($exceptionMsg);	        
        }
    }
}
?>