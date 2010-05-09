<?php
/**
 * Table Definition for User_yubikey
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class User_yubikey extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_yubikey';                     // table name
    public $user_id;                         // int(11)  unique_key not_null
    public $yubikey_id;                      // varchar(12)  not_null
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=null)
    { return Memcached_DataObject::staticGet('User_yubikey',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function table()
    {

        $db = $this->getDatabaseConnection();
        $dbtype = $db->phptype; // Database type is stored here. Crazy but true.

        return array('user_id'    => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
                     'yubikey_id' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
                     'created'    => DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME + DB_DATAOBJECT_NOTNULL,
                     'modified'   => ($dbtype == 'mysql' || $dbtype == 'mysqli') ?
                     DB_DATAOBJECT_MYSQLTIMESTAMP + DB_DATAOBJECT_NOTNULL :
                     DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME
                     );
    }

    /**
     * List primary and unique keys in this table.
     * Unique keys used for lookup *MUST* be listed to ensure proper caching.
     */
    function keys()
    {
        return array_keys($this->keyTypes());
    }

    function keyTypes()
    {
        return array('user_id' => 'P', 'yubikey_id' => 'K');
    }

    /**
     * No sequence keys in this table.
     */
    function sequenceKey()
    {
        return array(false, false, false);
    }

    Static function verifyYubikeyID($user_id, $identity)
    {
        $yubikeyobj = User_yubikey::staticGet('user_id', $user_id);
        return ($yubikeyobj->yubikey_id == $identity);
    }
}
