<?php
/**
 * PHP wrapper for the Dribbble API.
 * 
 * @author   DevDojo compliments of Martin Bean <martin@martinbean.co.uk> for original wrapper
 * @license  MIT License
 * @version  1.0
 */

namespace Devdojo\Dribbble;

/**
 * The core Dribbble API PHP wrapper class.
 */
class Dribbble
{
    /**
     * Default options for cURL requests.
     *
     * @var array
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'dribbble-api-php-wrapper'
    );
    
    /**
     * The API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.dribbble.com/v1/';
    protected $auth_endpoint = 'https://dribbble.com/oauth/authorize';
    protected $token_endpoint = 'https://dribbble.com/oauth/token';
    
    
    public function __construct(array $config = array()){
        $this->_client_id = $config['client_id'];
        $this->_client_secret = $config['client_secret'];
        //$this->_access_token = null; //$config['access_token'];
    }
    
    /**
     * Returns profile details for authenticated user/player.
     *
     */
    public function getUser(){
        return $this->request('user', null, 'GET');
    }
    
    
    /**
	   * Returns projects for user/player, if no id is provided assumes authenticated user/player.
	   *
	   * @param int $per_page
	   * @param int	$id
	   * @return object
	   */
	  public function getUserProjects($per_page, $id = null){
		  if($id){
			  $user_projects_url = 'users/'.$id.'/projects';
		  } else {
			  $user_projects_url = 'user/projects';
		  }
		  return $this->request($user_projects_url,array(
			  'per_page' => intval($per_page)
		  ), 'GET');
	  }
		
		/**
		 * Returns the shots for a given project
		 *
		 * @param int $per_page
		 * @param int $project_id
		 * @return object
		 */
		public function getProjectShots($per_page = 50, $project_id){
			if(!$project_id){
				return false;
			}
			return $this->request('projects/'.$project_id.'/shots', array(
				'per_page' => intval($per_page)
			), 'GET');
		}
		
    /**
     * Build the url that your user 
     * 
     * @param  string $redirect_uri The redirect url that you have configured on your app page
     * @param  string $scope        An array of scopes that your final access token needs to access
     * @param  string $state        A random variable that will be returned on your redirect url. You should validate that this matches
     * @return string
     */
    public function buildAuthorizationEndpoint($redirect_uri, $scope = 'public', $state = null){
        $query = array(
            "client_id" => $this->_client_id,
            "redirect_uri" => $redirect_uri
        );

        $query['scope'] = $scope;
        if (empty($scope)) {
            $query['scope'] = 'public';
        } elseif (is_array($scope)) {
            $query['scope'] = implode(' ', $scope);
        }

        if (!empty($state)) {
            $query['state'] = $state;
        }

        return $this->auth_endpoint . '?' . http_build_query($query);
    }

    /**
     * Request the access token associated with this lib
     * @return string
     */
    public function getToken()
    {
        return $this->_access_token;
    }

    /**
     * Assign a new access token to this lib
     * 
     * @param string $access_token the new access token
     */
    public function setToken($access_token)
    {
        $this->_access_token = $access_token;
    }
    
    /**
     * Request an access token. This is the final step of the OAuth 2 workflow, and should be called from your redirect url.
     * 
     * @param  string $code         The authorization code that was provided to your redirect url
     * @param  string $redirect_uri The redirect_uri that is configured on your app page, and was used in buildAuthorizationEndpoint
     * @return array This array contains three keys, 'status' is the status code, 'body' is an object representation of the json response body, and headers are an associated array of response headers
     */
    public function accessToken($code, $redirect_uri){
        return $this->request($this->token_endpoint, array(
            'client_id' => $this->_client_id,
            'client_secret' => $this->_client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri
        ), "POST", true);
    }

    /**
     * Convert the raw headers string into an associated array
     * 
     * @param  string $headers
     * @return array
     */
    public static function parse_headers($headers){
        $final_headers = array();
        $list = explode("\n", trim($headers));

        $http = array_shift($list);

        foreach ($list as $header) {
            $parts = explode(':', $header);
            $final_headers[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
        }

        return $final_headers;
    }

    /**
     * Makes a HTTP request.
     * This method can be overriden by extending classes if required.
     *
     * @param  string $url
     * @param  string $method
     * @param  array  $params
     * @return object
     * @throws Exception
     */

    public function request($url, $params = array(), $method = 'GET', $json_body = true){
        // add accept header hardcoded to version 3.0
        $headers = [];
        
        // add bearer token, or client information
        if(!empty($this->_access_token)){
            $headers[] = 'Authorization: Bearer ' . $this->_access_token;
        }
        
        if($url == $this->token_endpoint || $url == $this->auth_endpoint){
	        $prepped_url = $url;
        } else {
	        $prepped_url = $this->endpoint . $url;
        }
        
        //  Set the methods, determine the URL that we should actually request and prep the body.
        $curl_opts = array();
        switch (strtoupper($method)) {
            case 'GET' :
                if (!empty($params)) {
                    $query_component = '?' . http_build_query($params, '', '&');
                } else {
                    $query_component = '';
                }

                $curl_url = $prepped_url . $query_component;
                break;

            case 'POST' :
            case 'PATCH' :
            case 'PUT' :
            case 'DELETE' :
                if ($json_body && !empty($params)) {
                    $headers[] = 'Content-Type: application/json';
                    $body = json_encode($params);
                } else {
                    $body = http_build_query($params, '', '&');
                }

                $curl_url = $prepped_url;
                $curl_opts = array(
                    CURLOPT_POST => true,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $body
                );
                break;
        }
				
				//debug($headers);
        //  Set the headers
        $curl_opts[CURLOPT_HTTPHEADER] = $headers;

        $response = $this->_request($curl_url, $curl_opts);
        $response['body'] = json_decode($response['body'], true);

        return $response;
    }

    /**
     *  Internal function to handle requests, both authenticated and by the upload function.
     */
    private function _request($url, $curl_opts = array()) {
       	//debug($url);
	      //  Apply the defaults to the curl opts.
        $curl_opt_defaults = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30);

        //  Can't use array_merge since it would reset the numbering to 0 and lose the CURLOPT constant values.
        //  Insetad we find the overwritten ones and manually merge.
        $overwritten_keys = array_intersect(array_keys($curl_opts), array_keys($curl_opt_defaults));
        foreach ($curl_opt_defaults as $setting => $value) {
            if (in_array($setting, $overwritten_keys)) {
                break;
            }
            $curl_opts[$setting] = $value;
        }

        // Call the API
        $curl = curl_init($url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);
        curl_close($curl);

        //  Retrieve the info
        $header_size = $curl_info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        //  Return it raw.
        return array(
            'body' => $body,
            'status' => $curl_info['http_code'],
            'headers' => self::parse_headers($headers)
        );
    }
}
