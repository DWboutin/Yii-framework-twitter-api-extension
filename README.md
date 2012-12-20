yii-twitter-OAuth
=================

Simple Yii framework extension for the new Twitter api with the tmhOAuth php sdk

Note
======================

This extension is made to use the twitter action i needed too. I just wanted to share it with you! Feel free to improve it. Please let me know what you added in if you do ^^. Have fun

Install
======================

Put all the yii-twitter-OAuth folder in the extension folder

After add this in the config file

    'components'=>array(
		[...]
		'twitter' => array(
			'class' => 'ext.yii-twitter-OAuth.STwitter',
			'consumer_key' => 'nBIHM6u1P2TEiltK4C3uxw',
			'consumer_secret' => '76JByttK7saru1KfC5j6BsueA8YbzpVGSkXvbABCQ',
			'callback' => 'http://localhost/twitterExt',
			'signinParams' => array('force_write'),
		),
		[...]
		

Functions
======================

	Yii::app()->twitter->signUrl;											Get the sign in url
	Yii::app()->twitter->credential;										Get the user credential
	Yii::app()->twitter->friends;											Get informations about all followers(need to set user_token and user_secret before)
	Yii::app()->twitter->access_token;										Get the user access token
	Yii::app()->twitter->tweet($msg);										Send a tweet (need to set user_token and user_secret before)
	Yii::app()->twitter->tweetPicture($msg,$imagePath);						Send a tweet with a picture (need to set user_token and user_secret before)
	Yii::app()->twitter->rssFeed($feed,$params = null)						Get the rss feed ($feed can be 'user' or 'home') (need to set user_token and user_secret before)
	Yii::app()->twitter->friendships($method,$firendship,$params = null)	Play with the friendship zone ($method = 'Post' or 'Get') (need to set user_token and user_secret before) Check https://dev.twitter.com/search/apachesolr_search/friendship
	
/************** Set user_token and user_secret ********************/

	Yii::app()->twitter->user_token = '***********';
	Yii::app()->twitter->user_secret = '***********';
	
	
/*********** Connection exemple in a controller *****************/

		Yii::app()->session->open();		/**** Must be open ****/
		if(!isset($_SESSION['oauth']) && !isset($_SESSION['access_token'])){
			echo Yii::app()->twitter->signUrl;
		}
		if(isset($_SESSION['oauth_token']) && isset($_REQUEST['oauth_verifier'])){
			Yii::app()->twitter->access_token;
		}
		if(isset($_SESSION['access_token'])){
			echo '<pre>';
			print_r(Yii::app()->twitter->credential);
		}
		
