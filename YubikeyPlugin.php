<?php

/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin to allow two factor authentication with the yubikey
 * http://yubico.com/
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Eric Helgeson <erichelgeson@gmail.com>
 * @copyright 2010
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

 if (!defined('STATUSNET') && !defined('LACONICA')) {
     exit(1);
 }

require_once(INSTALLDIR.'/plugins/Yubikey/Auth/Yubico.php');
require_once(INSTALLDIR.'/plugins/Yubikey/User_yubikey.php');

class YubikeyPlugin extends Plugin {
    var $api_key;
    var $client_id;

    function onInitializePlugin(){
        if(!isset($this->api_key)) {
            common_log(LOG_ERR, 'YubikeyPlugin: Must specify api_key in config.php.');
        }
        if(!isset($this->client_id)) {
            common_log(LOG_ERR, 'YubkeyPlugin: Must specify client_id in config.php.');
        }
    }
    
    function onEndShowPasswordsettings($action) {
        //XXX Show association settings on this page?
    }
    
    function _checkYubikeyOTP($otp) {
        # Generate a new id+key from https://api.yubico.com/get-api-key/
        try {
            $yubi = &new Auth_Yubico($this->client_id, $this->api_key);
            $auth = @$yubi->verify($otp);
        } catch ( PEAR_Exception $e) {
            common_log(LOG_ERR, "Yubikey:: exception ".$e);
            return false;
        }       
        if (PEAR::isError($auth)) {
            common_log(LOG_ERR, "Yubikey:: Authentication failed: " . $auth->getMessage().' Response: '.$yubi->getLastResponse());
            return false;
        } else {
            common_log(LOG_DEBUG, "Yubikey:: Valid OTP login");
            return true;
        }
    }
    
    function onStartLoginAction($action, $user)
    {
        $rawotp = $action->trimmed('otp');
        //may want to parse later?
        $otp = Auth_Yubico::parsePasswordOTP($rawotp);

        if (!is_array($otp)) {
          common_log(LOG_ERR, 'Yubikey:: Could not parse One Time Passcode.');
          $action->showForm('Could not parse Yubikey One Time Passcode.');
          return false;
        }

        $identity = $otp['prefix'];
        $key = $otp['otp'];
        
        common_log(LOG_DEBUG, 'User: '. $user->id .' OTP: '. $key . ', prefix: '. $identity);
        
        if (!User_yubikey::verifyYubikeyID($user->id, $identity)) {
            common_log(LOG_DEBUG, 'Yubikey:: User: '. $user->id.' does not have a Yubikey on record.');
            // Return true because they dont have a yubikey associated and can continue
            return true;
        }

        if ( $this->_checkYubikeyOTP($key) ) {
            return true;
        } else {
            $action->showForm(_('Yubikey authentication failed.'));
            return false;
        }
    }
    
    /**
     * Show some extra instructions for using Yubikey
     *
     * @param Action $action Action being executed
     *
     * @return boolean hook value
     */

    function onEndLoginFormData($action)
    {
        $action->elementStart('li');
        $action->password('otp', _('Yubikey OTP'));
        $action->elementEnd('li');
    }
    
    /**
     * Data definitions
     *
     * Assure that our data objects are available in the DB
     *
     * @return boolean hook value
     */

    function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('user_yubikey',
                             array(new ColumnDef('user_id', 'integer',
                                                 null, false, 'PRI'),
                                   new ColumnDef('yubikey_id', 'varchar',
                                                 '12', false, 'MUL'),
                                   new ColumnDef('created', 'datetime',
                                                 null, false),
                                   new ColumnDef('modified', 'timestamp')));
        return true;
    }

    /**
     * Add our tables to be deleted when a user is deleted
     *
     * @param User  $user    User being deleted
     * @param array &$tables Array of table names
     *
     * @return boolean hook value
     */

    function onUserDeleteRelated($user, &$tables)
    {
        $tables[] = 'User_yubikey';
        return true;
    }
    
    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Yubikey',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Eric Helgeson',
                            'homepage' => 'http://status.net/wiki/Plugin:Yubikey',
                            'rawdescription' =>
                            _m('Uses <a href="http://yubico.com/">Yubico</a> service to add   '.
                               'two factor authentication.'));
        return true;
    }
}