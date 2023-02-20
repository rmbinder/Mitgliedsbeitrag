<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * @class Membernumbers
 * @brief Reads membernumbers out of database and generates new membernumbers
 *
 */
class Membernumbers
{
	public $mNumbers = array();   					///< Array with all membernumbers
	public $mUserWithoutMembernumber = array();   	///< Array with users without membernumbers
	
	protected $doubleNumber;          				///< if false, no double membernumbers exist
	protected $preFormatSegment;          			///< Pre-segment of the new member number
	protected $postFormatSegment;          			///< Post-segment of the new member number
	protected $lengthSerialNumber;          		///< The length of the numerical part of the new member number
    public $userWithoutMembernumberExist;          	///< if true, user without membernumber exist
   
    /**
     * constructor that will initialize variables 
     * @param \Database $database       Database object (should be @b $gDb)
     */
    public function __construct(&$database)
    {
        $this->mDb =& $database;
        $this->userWithoutMembernumberExist = false;
        $this->readNumbers();
        $this->doubleNumber = false;
        $this->checkDoubleNumbers();
        $this->preFormatSegment = '';
        $this->postFormatSegment = '';
        $this->lengthSerialNumber = 0;
    }

    /**
     * Set the database object for communication with the database of this class.
     * @param \Database $database An object of the class Database. This should be the global $gDb object.
     */
    public function setDatabase(&$database)
    {
        $this->mDb =& $database;
    }

    /**
     * Called on serialization of this object. The database object could not
     * be serialized and should be ignored.
     * @return string[] Returns all class variables that should be serialized.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), array('mDb'));
    }
    
    
    /**
     * Reads the membernumbers out of database table @b TBL_USER_DATA
     * and stores the values to the @b mNumbers array.
     */
    public function readNumbers()
    {
    	global $gProfileFields;
    	
    	$sql = 'SELECT usd_value
                  FROM '. TBL_USER_DATA .'
                 WHERE usd_usf_id = ? ';
    	
    	$statement = $this->mDb->queryPrepared($sql, array($gProfileFields->getProperty('MEMBERNUMBER'.$GLOBALS['gCurrentOrgId'], 'usf_id')));

    	while ($row = $statement->fetch())
    	{
    		$this->mNumbers[] = $row['usd_value'];
    	}
    	
    	sort($this->mNumbers);
	}
     
    /**
     * Checks the membernumbers if double entries exist
     * and stores the double membernumber in @b doubleNumber.
     */
    public function checkDoubleNumbers()
    {
    	for ($i = 0; $i < count($this->mNumbers)-1; $i++)
     	{
     		if ($this->mNumbers[$i] === $this->mNumbers[$i+1])
     		{
     			$this->doubleNumber =  $this->mNumbers[$i];
     		}
		}
    }
   
     
    /**
     * Returns false if there are no double membernumbers
     * or the value of the double membernumber
     * @return int|bool Returns the value of @b doubleNumber.
     */
	public function isDoubleNumber()
    {
    	return $this->doubleNumber;
    }
     
     
    /**
     * Reads all user without a membernumber from database
     * The values for user_id, last_name, first_name will be stored in @b mUserWithoutMembernumber.
     * If user exist without membernumer, @b userWithoutMembernumberExist will be set to @b true
     * @param array $roleselection Array with role_ids if roles are selected 
     */
	public function readUserWithoutMembernumber($roleselection = '')
    {
     	global $gProfileFields;
     	
     	$sqlRoleCond = '';
     	if (is_array($roleselection))
     	{
     		$sqlRoleCond =  'AND rol_id IN ('.implode(', ', $roleselection).')';
     	}
     	
     	// usr_id, Name, Vorname und Mitgliedsnummer einlesen von Mitgliedern, 
     	// die 1. keine Mitgliedsnummer besitzen 
     	// oder 2. eine Mitgliedsnummer < 1 besitzen
     	$sql = 'SELECT usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, membernumber.usd_value AS membernumber
                  FROM '.TBL_USERS.'
             LEFT JOIN '.TBL_USER_DATA.' AS last_name
                    ON last_name.usd_usr_id = usr_id
                   AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
             LEFT JOIN '.TBL_USER_DATA.' AS first_name
                    ON first_name.usd_usr_id = usr_id
           		   AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
             LEFT JOIN '.TBL_USER_DATA.' AS membernumber
                    ON membernumber.usd_usr_id = usr_id
                   AND membernumber.usd_usf_id = ? -- $gProfileFields->getProperty(\'MEMBERNUMBER\'.$GLOBALS[\'gCurrentOrgId\'], \'usf_id\')
                 WHERE usr_valid = 1
                   AND membernumber.usd_value IS NULL
            AND EXISTS (SELECT 1
                  FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES.  ','. TBL_USER_DATA. '
                 WHERE mem_usr_id = usr_id
                   AND mem_rol_id = rol_id
                   AND mem_begin <= ? -- DATE_NOW
                   AND mem_end    > ? -- DATE_NOW
                   AND rol_valid  = 1
                   '.$sqlRoleCond.'
                   AND rol_cat_id = cat_id
                   AND cat_org_id = ? -- $GLOBALS[\'gCurrentOrgId\']
              ) ';
     	
        $queryParams = array(
            $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
            $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
            $gProfileFields->getProperty('MEMBERNUMBER'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
            DATE_NOW,
            DATE_NOW,
            $GLOBALS['gCurrentOrgId']
        );

    	$statement = $this->mDb->queryPrepared($sql, $queryParams);

     	while ($row = $statement->fetch())
     	{
     		$this->mUserWithoutMembernumber[] = array('last_name'    => $row['last_name'],
     												  'first_name'   => $row['first_name'],
     												  'usr_id'       => $row['usr_id'],
     												  'membernumber' => $row['membernumber']);
     	}
     	
     	if (sizeof($this->mUserWithoutMembernumber) > 0)
     	{
     		$this->userWithoutMembernumberExist = true;
     	}
    }
       
       
    /**
     * generates new membernumbers and stores the values in @b mUserWithoutMembernumber
     * @param int|bool $fillGaps 1/true if gaps in membernumbers should be filled
     */
    public function getMembernumber($fillGaps = '')
	{
    	if ($this->userWithoutMembernumberExist)
    	{
    		$membernumberCounter = 1;
    		$memberCounter = 0;
    		
    		if (!$fillGaps)                       //Lücken in den Mitgliedsnummern nicht auffüllen 
    		{
    		    //wenn die Lücken in den Mitgliedsnumern nicht aufgefüllt werden sollen, dann muss die höchste bereits vergebene Mitgliedsnummer ausgelesen werden
    		    
    		    // Arbeitsarray erzeugen
    		    $workmNumbers = array();
    		    
    		    foreach ($this->mNumbers as $data)
    		    {
                    if (!empty($this->preFormatSegment))
    		        {
                        if (substr($data, 0, strlen($this->preFormatSegment)) == $this->preFormatSegment)
    		            {
    		                $workmNumbers[] = intval(substr($data, strlen($this->preFormatSegment)));
    		            }
    		        }
    		        else
    		        {
    		            if (is_numeric(substr($data, 0 ,1)))
    		            {
    		                $workmNumbers[] = intval($data);
    		            }
    		        }
    		    }
    		    // Startindex für Mitgliedsnummern bestimmen
    		    $membernumberCounter = max($workmNumbers)+1;
    		}
    		
    		while (sizeof($this->mUserWithoutMembernumber) > $memberCounter)
    		{
    			$newMembernumber = $this->preFormatSegment.str_pad($membernumberCounter, $this->lengthSerialNumber, '0', STR_PAD_LEFT);

    			$foundMarker = false;
    			foreach ($this->mNumbers as $data)
    			{
    				if (empty($this->preFormatSegment))
    				{
    					if ((int) $data == (int) $newMembernumber )
    					{
    						$foundMarker = true;
    					}
    				}
    				else
    				{
    					if (substr($data, 0, strlen($newMembernumber)) == $newMembernumber )
    					{
    						$foundMarker = true;
    					}
    				}
    			}
    			if (!$foundMarker)
    			{
    				$this->mUserWithoutMembernumber[$memberCounter]['membernumber'] = $newMembernumber.$this->postFormatSegment;
    				$memberCounter++;
    			}
    			$membernumberCounter++;
    		}
    	}
    }
    
    
    /**
     * Separates the formatting text into @b preFormatSegment, @b postFormatSegment and @b lengthSerialNumber
     */
    public function separateFormatSegment($formatText = '')
    {
    	//$formatText darf nicht leer sein und muss Zeichen fuer lfd. Nummer enthalten 
    	if ($formatText != '' && strstr($formatText, '#') != false)
    	{
    		$firstHash = strpos($formatText, '#'); 
    		$lastHash  = strrpos($formatText, '#');
    		
    		$this->lengthSerialNumber = $lastHash - $firstHash + 1;
    		if (substr_count($formatText, '#', $firstHash, $this->lengthSerialNumber) != $this->lengthSerialNumber)
    		{
    			$this->lengthSerialNumber = 0;
    			return;
    		}
    		
    		$this->preFormatSegment = substr($formatText, 0, $firstHash);
    		$this->postFormatSegment = substr($formatText, $lastHash + 1);
    	}
    } 
}
