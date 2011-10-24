<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * A message library for giving feedback to the user
 *
 * @author	Adam Jackett
 * @url		http://www.darkhousemedia.com/
 * @version	1.2.0
 */
class CI_Message {
	
	/**
	 * Holds the CI super object
	 *
	 * @var object
	 */
	public $CI;
	
	/**
	 * Contains all messages that have to be outputted
	 *
	 * @var	array
	 */
	public $messages = array();
	
	/**
	 * The wrapper to be appended and prepended to the messages
	 *
	 * @var	array
	 */
	public $wrapper = array('', '');

	/**
	 * Constructor
	 */
	public function __construct($config = NULL)
	{    
		
		$this->CI =& get_instance();        
		$this->CI->load->library('session');
		
		if ($this->CI->session->flashdata('_messages'))
		{ 
			$this->messages = $this->CI->session->flashdata('_messages');
		}
		
		if (is_array($config))
		{
			$this->initialize($config);
		}
		
	}
	
	/**
	 * Initialize configuration
	 *
	 * Loops the config array and assign the values to the respective object property
	 * @param 	array 	configuration values
	 * @return 	bool
	 */
	public function initialize($config)
	{
		
		if ( ! is_array($config)) 
		{
			return FALSE;
		}
		
		foreach ($config as $key => $val)
		{
			$this->$key = $val;
		}
	}
	
	/**
	 * Set a new message
	 *
	 * Create a new message object and assigns is to the session
	 * @param 	string	type of message
	 * @param	mixed	actual message(s)
	 * @param	bool	whether to preserve the message for an additional request
	 * @param	mixed	message group
	 * @return 	bool
	 */
	public function set($type, $message, $flash = FALSE, $group = FALSE)
	{
		
		if ( ! is_array($message)) 
		{
			$message = array($message);
		}
		
		foreach ($message as $msg)
		{
			$obj = new stdClass();
			$obj->message = $msg;
			$obj->type = $type;
			$obj->flash = $flash;
			$obj->group = $group;
			$this->messages[] = $obj;
		}
		
		$flash_messages = array();
		
		foreach ($this->messages as $msg)
		{
			if ($msg->flash)
			{
				$flash_messages[] = $msg;
			}
		}
		
		if (count($flash_messages)) 
		{
			$this->CI->session->set_flashdata('_messages', $flash_messages);
			return TRUE;
		}
		
		return FALSE;
		
	}
	
	/**
	 * Display messages
	 *
	 * Proxy for quickly displaying a bunch of messages
	 * @param	mixed	name of a message group you want to display
	 * @param	mixed	wrapper to be appended and prepend to the messages
	 * @return 	void
	 */
	public function display($group = FALSE, $wrapper = FALSE)
	{
		echo $this->get($group, $wrapper);
	}
	
	/**
	 * Get messages
	 *
	 * Creates a string of all messages
	 * @param	mixed	name of a message group you want to display
	 * @param	mixed	wrapper to be appended and prepend to the messages
	 * @return 	string	
	 */
	public function get($group = FALSE, $wrapper = FALSE)
	{
		$content = '';
		$form_val = $this->validation_errors();
		
		if (count($this->messages) OR count($form_val))
		{
			
			$output = array();
			$messages = array_merge($this->messages, $form_val);
			
			foreach ($messages as $msg)
			{
				if ($msg->group == $group OR $group === FALSE)
				{
					if ( ! isset($output[$msg->type])) 
					{
						$output[$msg->type] = array();
					}
					
					$output[$msg->type][] = $msg->message;
				}
			}
			
			$content .= ($wrapper !== FALSE ? $wrapper[0] : $this->wrapper[0])."\r\n";
			foreach ($output as $type => $messages)
			{
				$content .= '<div class="message message-'.$type.'">'."\r\n";
				
				foreach ($messages as $msg)
				{
					$content .= '<p>'.$msg.'</p>'."\r\n";
				}
				
				$content .= '</div>'."\r\n";
			}
			
			$content .= ($wrapper !== FALSE ? $wrapper[1] : $this->wrapper[1])."\r\n";
		}
		
		return $content;
	}
	
	/**
	 * Set validation errors
	 *
	 * Allows you to manually write validation errors to the session
	 * instead of calling validation_errors() in get(). Handy if you 
	 * redirect before calling get().
	 * @return 	void
	 */
	public function set_validation_errors()
	{
						
		foreach ($this->validation_errors() as $error)
		{
			$this->set('error', $error->message, TRUE, 'form_validation');
		}
		
	}

	/**
	 * Validation errors
	 *
	 * Gets all validation errors from the form validation library and 
	 * put these in an array of message objects.
	 * @return 	array
	 */
	public function validation_errors()
	{
		
		if ( ! function_exists('validation_errors')) 
		{
			$this->CI->load->helper('form');
		}
		
		$temp_errors = explode("\n", strip_tags(validation_errors()));
		$errors = array();
		foreach ($temp_errors as $e)
		{
			if ( ! empty($e)) 
			{	
				$error = new StdClass;
				$error->message = $e;
				$error->type = 'error';
				$error->flash = FALSE;
				$error->group = 'form_validation';
				$errors[] = $error;
			}
		}
		
		return $errors;
	}

	/**
	 * Keep messages
	 *
	 * Preserve the messages for an additional request
	 * @return 	void
	 */
	public function keep()
	{
		$this->CI->session->keep_flashdata('_messages');
	}

}