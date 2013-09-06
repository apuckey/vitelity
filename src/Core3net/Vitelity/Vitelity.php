<?php
namespace Core3net\Vitelity;
/**
 * Vitelity API Package for use with Laravel
 *
 * With the ever growing popularity of SMS and vFAX, Utilizing an API that the
 * Laravel development community is accustomed to using seemed necessary. 
 * 
 * PHP version >= 5.4
 * Laravel/Curl >= Dev Master
 *
 * @category   Vendor API
 * @package    Core3net/Vitelity
 * @author     Chris Horne <chorne@core3networks.com>
 * @copyright  2013 Core 3 Networks
 * @link       http://github.com/core3net/vitelity
*/

class Vitelity 
{
    /**
     * The Vitelity API Username. This information can be obtained by
     * logging into the Vitelity user portal at www.vitelity.com
     * 
     * @var VITELITY_USERNAME
     */
    public static $VITELITY_USERNAME = "APIUSERNAME";			
	/**
	 * The Vitelity API Password. Pretty self-explanatory.
	 * 
	 * @var VITELITY_PASSWORD
	 */
    public static $VITELITY_PASSWORD = "YOURAPIPASS";			
	/**
	 * Vitelity uses mutliple endpoints for different aspects of their services.
	 * This is for the vFax capabilities
	 * 
	 * @var VITELITY_FAXAPI
	 */
    const VITELITY_FAXAPI = "http://api.vitelity.net/fax.php";
    /**
     * The Vitelity SMS Endpoint for use with Longcode and Shortcode SMS
     * 
     * @var VITELITY_SMSAPI
     */
    const VITELITY_SMSAPI = "http://smsout-api.vitelity.net/api.php";
    /**
     * The generic Vitelity API Endpoint. Used mostly for account specific functions.
     * 
     * @var VITELITY_API
     */
	const VITELITY_API = "http://api.vitelity.net/api.php";
	/**
	 * Vitelity's Local Number Portability API Endpoint for use with porting DIDs.
	 * 
	 * @var VITELITY_LNP
	 */
	const VITELITY_LNP = "http://api.vitelity.net/lnp.php";
	
	/**
	 * Gathers all commands and parameters and uses cURL to submit to vitelity for a 
	 * response. If the command fails it will be reported to the [info] logging 
	 * facility.
	 * 
	 * @param string $url The vitelity endpoint to send the command.
	 * @param array $fields An array of parameters, including the command.
	 * @param boolean $post Either use POST or GET. Default is POST.
	 */
	public function transmit($url, array $fields = [], $post = true)
	{
	    $fields_string = null;
	    $fields['login'] = self::$VITELITY_USERNAME;
	    $fields['pass'] = self::$VITELITY_PASSWORD;
	    $fields['xml'] = 'yes';
	    $curl = new \Curl;
	    $curl->create($url);
	    $curl->post($fields);
	    $response = $curl->execute();
	    if ($curl->error_code > 0)
	        \Log::info("[vitelity] Attempted to Contact Vitelity API but got Error #{$curl->error_code} ({$curl->error_string})");
	    else
   	        $response = new \SimpleXMLElement($response);
	    if (!isset($response->status))
	    {
	        \Log::info("[vitelity] Command $fields[cmd] failed to return a readable XML element.");
	        return null; 
	    }
	    if ($response->status != 'ok' && isset($response->error))
	        \Log::info("[vitelity] Command $fields[cmd] failed with message: $response->error");
        return $response;
    }
    
    /**
     * Sends an SMS message via the longcode API
     * 
     * @param integer $destination The destination phone number
     * @param string $msg The message to send
     * @param integer $source The number to send from (the source)
     */
    static public function sendSMS($destination, $msg, $source)
    {
        $api = new Vitelity();
        $fields = [ 
                    'cmd' => 'sendsms',
                    'src' => $source,
                    'dst' => $destination,
                    'msg' => $msg
	               ];
        return $api->transmit(self::VITELITY_SMSAPI, $fields);
    }
    
    /**
     * Sends a Fax using the Vitelity vFax API. Faxes may be sent in JPG, 
     * PDF, Adobe PostScript, TIFF, Microsoft Word, Excel, CSV, 
     * HTML & Plain Text formats. 
     * 
     * @param integer $faxNum The destination fax number
     * @param string $contactName The destination contact name 
     * @param file $file The file name to send. 
     * @param integer $sourceFax The source vFax number you are sending from.
     */
    static public function sendFax($faxNum, $contactName, $file, $sourceFax)
    {
        $api = new Vitelity();
         if (!file_exists($file))
         {
             \Log::info("[vitelity] Tried to send a Fax but the file: {$file} was not found.");
             return null;
         }
        $data = base64_encode(fread(fopen($file, "r"), filesize($file)));
        $fields = [
                    'cmd' => 'sendfax',
                    'faxnum' => $faxNum,
                    'faxsrc' => $sourceFax,
                    'recname' => $contactName,
                    'file1' => $file,
                    'data1' => $data
	               ];
        return $api->transmit(self::VITELITY_FAXAPI, $fields);
    }

    /**
     * Sends a shortcode message using the Vitelity SMS API.
     * 
     * @param integer $destination The destination mobile number
     * @param string $msg The destination mobile number.
     * @param integer $source The shortcode number
     */    
    static public function shortCode($destination, $msg, $source)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'sendshort',
                    'src' => $source,
                    'dst' => $destination,
                    'msg' => $msg
	              ];
        return $api->transmit(self::VITELITY_SMSAPI, $fields);
    }
    
    /**
     * Enable a DID to Send/Receive SMS Messages.
     * 
     * @param integer $did The DID you wish to enable.
     */
    static public function enableSMS($did)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'smsenablehtt',
                    'did' => $did
	               ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Sets the URL to send incoming text messages. This is the event hook
     * url that Vitelity uses to send messages as they arrive.
     * 
     * @param string $url The URL you wish to have replies sent.
     */
    static public function setSMSURL($url)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'smsenableurl',
                    'url' => $url
	              ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Checks the DID to see if SMS is enabled or not.
     * 
     * @param integer $did The DID you wish to check.
     */
    static public function vitelity_checkDID($did)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'checksms',
                    'did' => $did
	              ];
        $result = $api->transmit(self::VITELITY_API, $fields);
        return $result;
    }

    /**
     * Create a Vitelity Sub Account to be used with SIP registrations.
     * 
     * @param string $name The subaccount name
     * @param string $secret The secret to the account.
     */
    static public function createSubAccount($name, $secret)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'addsubacc',
                    'peer' => $name,
                    'secret' => $secret
	              ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Get all available local DIDs in a particular state. 
     * @param string[2] $state The state you want DIDs for. 
     */
    static public function getAvailableLocals($state)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'listlocal',
                    'state' => $state
            	  ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Purchase a local DID for a Subaccount.
     * 
     * @param integer $did The DID you wish to purchase (found from getAvailableLocals)
     * @param string $account The Subaccount to assign this DID to.
     * @param string $type The rate at which this DID is billed (perminute, unlimited {pri_name})
     */
    static public function purchaseLocal($did, $account, $type = 'perminute')
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'getlocaldid',
                    'did' => $did,
                    'routesip' => $account,
                    'type' => $type
	              ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Removes a DID from your Vitelity account permanently.
     * 
     * @param integer $did The DID you wish to remove from your account.
     */
    static public function removeDID($did)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'removedid',
                    'did' => $did
	              ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Get a list of vFAX numbers that are available from a particular state.
     * 
     * @param string[2] $state The state in which you wish to get the numbers.
     */
    static public function getAvailableVFAX($state)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'faxlistdids',
                    'state' => $state
	              ];
        return $api->transmit(self::VITELITY_FAXAPI, $fields);
    }
    
    /**
     * Purchase vFax Number. These cannot be assigned to a subaccount as of 2013.
     * 
     * @param integer $did The vfax DID you wish to purchase.
     */
    static public function purchaseVFAX($did)
    {
        $api = new Vitelity();
        $fields = [
                    'cmd' => 'faxgetdid',
                    'did' => $did
	              ];
        return $api->transmit(self::VITELITY_FAXAPI, $fields);
    }
    
    /**
     * Get Call Detail Records from a Subaccount.
     * 
     * @param string $account The Vitelity account name
     * @param integer $start The unix timestamp of when to start
     * @param integer $end The unix timestamp of when to end
     * @param string[1] $type I for inbound, O for outbound.
     */
    static public function getCDRFromSub($account, $start, $end, $type = null)
    {
        $api = new Vitelity();
        $start = date("m-d-Y", $start);
        $end = date("m-d-Y", $end);
        $fields = [
                    'cmd' => 'subaccountcdrdetail',
                    'startdate' => $start,
                    'enddate' => $end,
                    'subaccount' => $account,
                    'type' => ($type == 'I') ? 'inbound' : 'outbound'
                   ];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * List all incoming faxes for an account.
     * 
     */
    static public function listFaxes()
    {
        $api = new Vitelity();
        $fields = [
			        'cmd' => 'listincomingfaxes',
			        'showpages' => 'yes',
			        'xmlcomply' => 'yes'
				];
		return $api->transmit(self::VITELITY_FAXAPI, $fields);
    }
    
    /**
     * Return a base64 encoded value for the faxid you obtained from listFaxes()
     * 
     * @param integer $faxid The ID of the Fax from listFaxes()
     */
    static public function getFax($faxid)
    {
        $api = new Vitelity();
        $fields = [
        			'cmd' => 'getfax',
        			'faxid' => $faxid,
        			'type' => 'base64'
				];
        return $api->transmit(self::VITELITY_FAXAPI, $fields);
    }
    
    /**
     * Reroutes a DID through another Vitelity Account. This feature allows you to transfer
     * DIDs to another customer or back into a holding area.
     * 
     * @param integer $did The DID you want to reroute
     * @param string $account The Vitelity Account to reroute to.
     */
    static public function reRoute($did, $account)
    {
        $api = new Vitelity();
        $fields = [
        			'cmd' => 'reroute',
        			'routesip' => $account,
        			'did' => $did
				];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Reroutes a DID to a secondary destination in the event there is no 
     * route to the primary device (such as a cellphone)
     * 
     * @param integer $did The DID to set the failover
     * @param integer $target The number to forward calls to in event of a failure.
     */
    static public function failover($did, $target)
    {
        $api = new Vitelity();
        $fields = [
        			'cmd' => 'failover',
        			'failnum' => $target,
        			'did' => $did
				];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Get all CDR records from Vitelity since last poll.
     */
    static public function getCDR()
    {
        $api = new Vitelity();
        $fields = ['cmd' => 'cdrlist'];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    /**
     * Check to see if a DID is portable to Vitelity. This function
     * will return if available for Fax, Voice or Both.
     * 
     * @param integer $did The 10 Digit DID to check
     */
    static public function checkLNPAvailability($did)
    {
        $api = new Vitelity();
        $fields = [
        			'cmd' => 'checkavail',
        			'did' => $did
				];
        return $api->transmit(self::VITELITY_LNP, $fields);
    }
    
    /**
     * Get the CallerID Record for a DID.
     * 
     * @param integer $did The 10 digit DID to check
     */
    
    static public function getCallerID($did)
    {
        $api = new Vitelity();
        $fields = [
        			'cmd' => 'cnam',
        			'did' => $did
				];
        return $api->transmit(self::VITELITY_API, $fields);
    }
    
    
}    