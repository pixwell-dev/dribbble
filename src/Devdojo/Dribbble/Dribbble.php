<?php
/**
 * PHP wrapper for the Dribbble API.
 *
 * @author   DevDojo compliments of Martin Bean <martin@martinbean.co.uk> for original wrapper
 * @license  MIT License
 * @version  1.0
 */

namespace Devdojo\Dribbble;

use GuzzleHttp\Client;

/**
 * The core Dribbble API PHP wrapper class.
 */
class Dribbble
{
    private $access_token;
    private $http;
    
    /**
     * The API endpoint.
     *
     * @var string
     */
    protected $baseUri = 'https://api.dribbble.com/v1/';
    
    public function __construct(array $config = [])
    {
        $this->access_token = array_get($config, 'access_token', null);
        
        $this->http = new Client([
            'base_uri' => $this->baseUri,
        ]);
    }
    
    public function getAccessToken()
    {
        return $this->access_token;
    }
    
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }
    
    /**
     * @param $uri
     * @param string $method
     * @param array $query
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException
     */
    private function request($uri, $method = 'GET', array $query = [])
    {
        $response = $this->http->request($method, $uri, [
            'query' => array_merge([
                'access_token' => $this->getAccessToken(),
            ], $query),
        ])->getBody()->getContents();
        
        
        return collect(json_decode($response, true));
    }
    
    public function getTeamShots($team_name)
    {
        return $this->request('teams/' . $team_name . '/shots');
    }
    
    public function getUser()
    {
        return $this->request('user');
    }
    
    public function getUserProjects($id = null)
    {
        $user_projects_url = 'user/projects';
        
        if ($id) {
            $user_projects_url = 'users/' . $id . '/projects';
        }
        
        return $this->request($user_projects_url);
    }
    
    public function getUserShots()
    {
        return $this->request('user/shots');
    }
}
