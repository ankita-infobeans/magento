<?php 

class Tm_Instagram_Model_Instagram
{
	public $username;
	public $client_id;
	public $tag;

	function __construct($params)
	{
		$this->username = $params['username'];
		$this->client_id = $params['client_id'];
		$this->tag = $params['tag'];
	}

	public function getUserId()
	{	
		$user_json = file_get_contents('https://api.instagram.com/v1/users/search?q='. $this->username .'&client_id='. $this->client_id);
		$user_array = json_decode($user_json, true);
		$user_data = $user_array['data']['0'];

		return $user_data['id'];
	}

	public function userMediaRecent()
	{
		$media_json = file_get_contents('https://api.instagram.com/v1/users/'. $this->getUserId() .'/media/recent/?client_id='. $this->client_id);
		$media_array = json_decode($media_json, true);

		// var_dump($media_array['data']['0']['images']);

		$media_items = '';
		foreach ($media_array['data'] as $items) {
			$media_items[] = $items;
		}
		
		return $media_items;
	}

	public function tagMediaRecent()
	{
		$media_json = file_get_contents('https://api.instagram.com/v1/tags/'. $this->tag .'/media/recent/?client_id='. $this->client_id);
		$media_array = json_decode($media_json, true);

		$media_items = '';
		foreach ($media_array['data'] as $items) {
			$media_items[] = $items;
		}
		
		return $media_items;
	}
}