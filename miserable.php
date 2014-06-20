<?php

if(!defined("IN_MYBB"))
{
	exit;
}

$plugins->add_hook('global_end', 'miserable_global');

function miserable_info()
{
	return array(
		'name'			=> 'Miserable Users',
		'description'	=> 'An alternative to banning that deters users away by making it painful for them to use your forum.',
		'website'		=> '',
		'author'		=> 'Mattbox Solutions',
		'authorsite'	=> 'http://community.mybb.com/user-79350.html',
		'version'		=> '1.0',
		'guid' 			=> '',
		'compatibility' => '*'
	);
}

function miserable_global()
{
	$miserable = new Miserable_User();
	$miserable->make_miserable();
}

function miserable_activate()
{
	$miserable = new Miserable_User();
	$miserable->activate();
}

function miserable_deactivate()
{
	$miserable = new Miserable_User();
	$miserable->deactivate();
}

/**
 *	Miserable User Class
 *
 *	Developed by Mattbox Solutions
 *	6/19/2014
 *
 */
class Miserable_User
{
	public $mybb;
	public $db;
	public $settings;
	public $lang;
	public $miserable_groups = array();
	public $miserable_users = array();
	public $debug = false;

	public function __construct()
	{
		global $mybb, $db, $settings, $footer, $lang;

		$this->mybb =& $mybb;
		$this->db =& $db;
		$this->settings =& $settings;
		$this->lang =& $lang;
		$this->miserable_groups = explode(',', $this->settings['miserable_usergroups']);
		$this->miserable_users = explode(',', $this->settings['miserable_userids']);
	}

	public function make_miserable()
	{
		if (!in_array($this->mybb->user['usergroup'], $this->miserable_groups) && !in_array($this->mybb->user['uid'], $this->miserable_users))
		{
			return;
		}

		$wait_min = $this->settings['miserable_response_delay_min'];
		$wait_max = $this->settings['miserable_response_delay_max'];
		$wait_time = rand($wait_min, $wait_max);

		set_time_limit($wait_time + 30);
		sleep($wait_time);

		if (rand(1, 100) <= $this->settings['miserable_busy'])
		{
			error($this->lang->error_loadlimit);
		}

		if (rand(1, 100) <= $this->settings['miserable_search'])
		{
			$this->mybb->usergroup['cansearch'] = '0';
		}

		if (rand(1, 100) <= $this->settings['miserable_redirect'])
		{
			header('location:' . $this->settings['miserable_redirect_url']);
			exit;
		}

		if (rand(1, 100) <= $this->settings['miserable_blank'])
		{
			die();
		}
	}

	public function activate()
	{
		$prefix = 'miserable';

		$this->db->insert_query('settinggroups', array(
			'gid' => '0',
			'name' => $prefix,
			'title' => 'Miserable Users',
			'description' => 'This section allows you to manage the various settings of the Miserable Users plugin.',
			'disporder' => '6',
			'isdefault' => '1'
		));

		$settings = array(
			array(
				'name' => 'usergroups',
				'title' => 'Miserable Usergroups',
				'description' => 'Enter a list of usergroup ID&#39;s in which all members will be treated as miserable users. (separate each usergroup ID with a comma)',
				'optionscode' => 'text',
				'value' => ''
			),
			array(
				'name' => 'userids',
				'title' => 'Miserable Users',
				'description' => 'Alternatively, specifically enter a list of user ID&#39;s who will be treated as miserable users. (separate each user ID with a comma)',
				'optionscode' => 'text',
				'value' => ''
			),
			array(
				'name' => 'response_delay_min',
				'title' => 'Minimum Page Response Delay',
				'description' => 'Enter the minimum amount of time (in seconds) a user should have to wait for the page to load.',
				'optionscode' => 'text',
				'value' => '20'
			),
			array(
				'name' => 'response_delay_max',
				'title' => 'Maximum Page Response Delay',
				'description' => 'Enter the maximum amount of time (in seconds) a user should have to wait for the page to load.',
				'optionscode' => 'text',
				'value' => '60'
			),
			array(
				'name' => 'busy',
				'title' => 'Server Busy Percentage',
				'description' => 'Enter the percent chance that the user will get a "Server Busy" error message after waiting. (default is 50%)',
				'optionscode' => 'text',
				'value' => '50'
			),
			array(
				'name' => 'search',
				'title' => 'Denied Search Percentage',
				'description' => 'Enter the percent chance that the user will be denied upon making a forum search after waiting. (default is 75%)',
				'optionscode' => 'text',
				'value' => '75'
			),
			array(
				'name' => 'redirect_url',
				'title' => 'Redirect URL',
				'description' => 'Enter the URL in which a user may be randomly redirected to after waiting. (default is your forum homepage)',
				'optionscode' => 'text',
				'value' => $this->settings['bburl']
			),
			array(
				'name' => 'redirect',
				'title' => 'Page Redirect Percentage',
				'description' => 'Enter the percent chance that the user will be redirected to the page specified above. (default is 25%)',
				'optionscode' => 'text',
				'value' => '25'
			),
			array(
				'name' => 'blank',
				'title' => 'Blank Page Percentage',
				'description' => 'Enter the percent chance that the user will load a blank page after waiting. (default is 25%)',
				'optionscode' => 'text',
				'value' => '25'
			)
		);

		$sgid = $this->db->insert_id();

		// Insert Settings
		for ($i = 0; $i < count($settings); $i++)
		{
			$settings[$i]['sid'] = '0';
			$settings[$i]['name'] = $prefix . '_' . $settings[$i]['name'];
			$settings[$i]['disporder'] = ($i + 1);
			$settings[$i]['isdefault'] = '1';
			$settings[$i]['gid'] = $sgid;
			// debug
			if ($this->debug)
			{
				$settings[$i]['title'] .= ' | $this->settings[\\\'' . $settings[$i]['name'] . '\\\']';
			}
			$this->db->insert_query('settings', $settings[$i]);
		}

		rebuild_settings();
	}

	public function deactivate()
	{
		$this->db->delete_query("settinggroups WHERE name = 'miserable';");
		$this->db->delete_query("settings WHERE name LIKE '%miserable_%';");
		rebuild_settings();
	}
}

?>