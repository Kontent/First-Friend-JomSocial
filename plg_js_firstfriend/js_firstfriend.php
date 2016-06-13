<?php
/**
 * @license             http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL see LICENSE.txt
 */
// no direct access

defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.plugin.plugin' );
require_once( JPATH_SITE . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');
require_once( JPATH_SITE . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'notification.php');
			
class plgUserSiteFriend extends JPlugin
{
	function plgUserSiteFriend (& $subject, $config)
	{	
		parent::__construct($subject, $config);					
	}
	
	function onUserAfterSave($user, $isnew, $success, $msg)
	{
		if ($isnew)
		{
			$config =& JFactory::getConfig();
			$friendid = $this->params->get( 'friendid');
			
			// Send Friend Request
			$this->sendRequest($user['id'], $friendid);
			
			//Send PM
			$vars['id'] = $this->params->get( 'friendid') ;
			$vars ['to'] = $user['id'];
			$vars['pmsubject'] = $this->params->get( 'pmsubject' , '' );
			$vars['pmbody'] = JText::sprintf($this->params->get( 'pmmessage' , '' ) , $config->getValue( 'config.sitename' ) , JURI::base());
			$this->sendPm($vars);		
		}
	}

	function sendRequest($receiver, $sender)
	{	
		$friends =& CFactory::getModel('friends');
		$friends->addFriend($receiver, $sender);	
	}
	
	function sendPm($vars)
	{
		$sendpm = $this->params->get( 'sendpm', 0);
		
		if ($sendpm != 0 )
		{  
			$db = &JFactory::getDBO();
			$my	=& JFactory::getUser($vars['id']);
			$userTo =& CFactory::getUser($vars['to']);
			$recipientName	= $userTo->getDisplayName();
			
			$search 	= array('{actor}', '{target}');
			$replace 	= array($userTo->getDisplayName(), $recipientName );
					
			$vars['pmsubject'] 	= JString::str_ireplace($search, $replace, $vars['pmsubject']);
			$vars['pmbody'] 	= JString::str_ireplace($search, $replace, $vars['pmbody']);
			
			$date	=& JFactory::getDate();
			
			$obj = new stdClass();
			$obj->id = null;
			$obj->from = $my->id;
			$obj->posted_on = $date->toFormat();
			$obj->from_name	= $my->name;
			$obj->subject	= $vars['pmsubject'];
			$obj->body		= $vars['pmbody'];
			
			$db->insertObject('#__community_msg', $obj, 'id');
			
			// Update the parent
			$obj->parent = $obj->id;
			$db->updateObject('#__community_msg', $obj, 'id');

			//Add recepient
			$this->addReceipient($obj, $vars['to']);
			
		return $obj->id;
		}
	}
	
	function addReceipient($msgObj, $recepientId)
	{
		$db = &JFactory::getDBO();
		$recepient = new stdClass();
		$recepient->msg_id = $msgObj->id;
		$recepient->msg_parent = $msgObj->parent;
		$recepient->msg_from = $msgObj->from;
		$recepient->to	= $recepientId;		
		$db->insertObject('#__community_msg_recepient', $recepient);
		
		if($db->getErrorNum()) 
		{
			JError::raiseError( 500, $db->stderr());
		}
	}
}