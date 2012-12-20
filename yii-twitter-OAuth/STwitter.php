<?php

Yii::import("ext.yii-twitter-OAuth.inc.tmhOAuth");
Yii::import("ext.yii-twitter-OAuth.inc.tmhUtilities");

class STwitter extends CApplicationComponent
{
	private $_twitter;
	
	public $consumer_key;
	public $consumer_secret;
	public $user_token;
	public $user_secret;
	# for signinParams -> 
				# force_write = to force write only permissions
				# force_read = to force write only permissions
				# authenticate = to authenticate or it will authorize
				# force = to force login, default = it won't
	public $signinParams;
	# Can be 'oob' or the callback url
	public $callback;
	
	protected function _getTwitter()
    {
        if (is_null($this->_twitter)) {
			
            if ($this->consumer_key && $this->consumer_secret && $this->user_token == null && $this->user_secret == null) {
                $this->_twitter = new tmhOAuth(array(
					'consumer_key' => $this->consumer_key,
					'consumer_secret' => $this->consumer_secret,
				));
            }else if($this->consumer_key && $this->consumer_secret){
				$this->_twitter = new tmhOAuth(array(
					'consumer_key' => $this->consumer_key,
					'consumer_secret' => $this->consumer_secret,
					'user_token'      => $this->user_token,
					'user_secret'     => $this->user_secret,
				));
			} else {
                if (!$this->consumer_key)
                    throw new CException('Twitter application consumer key is not specified.');
                elseif (!$this->consumer_secret)
                    throw new CException('Twitter application consumer secret is not specified.');
            }
        }
        if(!is_object($this->_twitter)) {
            throw new CException('Twitter API could not be initialized.');
        }
        return $this->_twitter;
    }
	
	public function getSignUrl()
	{
		$code = $this->_getTwitter()->request('POST', $this->_getTwitter()->url('oauth/request_token', ''), $this->getSignParams());

		if ($code == 200) {
			$_SESSION['oauth'] = $this->_getTwitter()->extract_params($this->_getTwitter()->response['response']);
			$method = in_array('authenticate',$this->signinParams) ? 'authenticate' : 'authorize';
			$force  = in_array('force',$this->signinParams) ? '&force_login=1' : '';
			$authurl = $this->_getTwitter()->url("oauth/{$method}", '') .  "?oauth_token={$_SESSION['oauth']['oauth_token']}{$force}";
			return $authurl;
		} else {
			return null;
		}
	}
	
	private function getSignParams()
	{
		if(!is_array($this->signinParams))
			throw new CException('Twitter "signinParams" must be an array');
		
		if(empty($this->callback))
			throw new CException('Twitter "callback" is empty, you must specify either oob or the callback url');
		
		$params = array(
			'oauth_callback' => $this->callback
		);
		
		if (in_array('force_write',$this->signinParams)){
			$params['x_auth_access_type'] = 'write';
		}else if (in_array('force_read',$this->signinParams)){
			$params['x_auth_access_type'] = 'read';
		}
		
		return $params;
	}
	
	public function getCredential()
	{
		$this->_getTwitter()->config['user_token']  = $_SESSION['access_token']['oauth_token'];
		$this->_getTwitter()->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
	
		$code = $this->_getTwitter()->request(
			'GET',
			$this->_getTwitter()->url('1/account/verify_credentials')
		);

		if ($code == 200) {
			return json_decode($this->_getTwitter()->response['response']);
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
	}
	
	public function getAccess_token() 
	{
		$this->_getTwitter()->config['user_token']  = $_SESSION['oauth']['oauth_token'];
		$this->_getTwitter()->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
       
		$code = $this->_getTwitter()->request(
			'POST',
			$this->_getTwitter()->url('oauth/access_token', ''),
			array(
				'oauth_verifier' => $_REQUEST['oauth_verifier']
			)
		);
		if ($code == 200) {
			$_SESSION['access_token'] = $this->_getTwitter()->extract_params($this->_getTwitter()->response['response']);
            $this->user_token = $_SESSION['access_token']['oauth_token'];
            $this->user_secret = $_SESSION['access_token']['oauth_token_secret'];
			unset($_SESSION['oauth']);
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
    }
    
    public function getUser_token()
    {
        return $this->user_token;
    }
    
    public function getUser_secret()
    {
        return $this->user_secret;
    }
	
	public function tweet($msg)
	{	
		$code = $this->_getTwitter()->request('POST', $this->_getTwitter()->url('1/statuses/update'), array(
		  'status' => $this->tweetFormat($msg)
		));
		if ($code == 200) {
			return json_decode($this->_getTwitter()->response['response']);
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
	}
	
	public function tweetPicture($msg,$image)
	{
		$code = $this->_getTwitter()->request('POST','https://upload.twitter.com/1/statuses/update_with_media.json',array(
				'media[]'  => "@{$image};type=".$image['type'].";filename={$image}",
				'status'   => $this->tweetFormat($msg),
			),
			true, // use auth
			true  // multipart
		);
		if ($code == 200) {
			return json_decode($this->_getTwitter()->response['response']);
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
	}
	
	public function rssFeed($feed, $params)
	{
		$feed = ($feed == 'user') ? 'user' : 'home';
		
		$code = $this->_getTwitter()->request('GET', $this->_getTwitter()->url('1/statuses/'.$feed.'_timeline', 'rss'),$params);

		if ($code == 200) {
			header('Content-Type: application/rss+xml; charset=utf-8');	
			return $this->_getTwitter()->response['response'];
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
	}
	
	public function friendships($method,$friendship,$params = null)
	{
		$code = $this->_getTwitter()->request($method, $this->_getTwitter()->url('1/friendships/'.$friendship),$params);
		if ($code == 200) {
			return json_decode($this->_getTwitter()->response['response']);
		} else {
			throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()));
		}
	}
	
	public function getFriends()
	{
		define('LOOKUP_SIZE', 100);
		$cursor = '-1';
		$ids = array();
		while (true) :
			if ($cursor == '0')
			break;

			$this->_getTwitter()->request('GET', $this->_getTwitter()->url('1/friends/ids'), array(
			'cursor' => $cursor
			));

			// check the rate limit
			$this->check_rate_limit($this->_getTwitter()->response);
			if ($this->_getTwitter()->response['code'] == 200) {
				$data = json_decode($this->_getTwitter()->response['response'], true);
				$ids = array_merge($ids, $data['ids']);
				$cursor = $data['next_cursor_str'];
			} else {
				throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()->response['response']));
			break;
			}
			usleep(500000);
		endwhile;
		
		$paging = ceil(count($ids) / LOOKUP_SIZE);
		$users = array();
		for ($i=0; $i < $paging ; $i++) {
			$set = array_slice($ids, $i*LOOKUP_SIZE, LOOKUP_SIZE);

			$this->_getTwitter()->request('GET', $this->_getTwitter()->url('1/users/lookup'), array(
			'user_id' => implode(',', $set)
			));

			// check the rate limit
			$this->check_rate_limit($this->_getTwitter()->response);

			if ($this->_getTwitter()->response['code'] == 200) {
			$data = json_decode($this->_getTwitter()->response['response'], true);
			$users = array_merge($users, $data);
			} else {
				throw new CException('Twitter api - Code '.$code.' '.$this->outputError($this->_getTwitter()->response['response']));
			break;
			}
		}
		return $users;
	}
	
	private function tweetFormat($msg)
	{
		if(strlen($msg) > 140)
			return utf8_encode(substr($msg,0,strrpos(substr($msg,0,137),' ')).'...');
		else
			return utf8_encode($msg);
	}
	
	private function check_rate_limit($response) {
		$headers = $response['headers'];
		if ($headers['x_ratelimit_remaining'] == 0) :
			$reset = $headers['x_ratelimit_reset'];
			$sleep = time() - $reset;
			sleep($sleep);
		endif;
	}
	
	/* peut-etre � enlever */
	private function outputError($tmhOAuth) {
		return 'Error: ' . $tmhOAuth->response['response'] . PHP_EOL;
	}
}