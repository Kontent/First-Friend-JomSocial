<?php
/**
 * @version             $Id: sitefriend.php 02.11.2012  SuburbaNS Team
 * @package             Site Friend
 * @subpackage  		User
 * @copyright   		Copyright (C) 2009 - 2012 SuburbaNS Solutions. All rights reserved.
 * @license             http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL see LICENSE.txt
 * Profile Viewers is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
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
		
			// To do: Send Joomla Email
			//$this->sendWelcomeEmail($user['id']);
			
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
	
	//To Do: Joomla email
	/*function sendWelcomeEmail($receiver)
	{
		$sendwelcome = $this->params->get( 'sendwelcome', 0);
		
		if ($sendwelcome != 0 )
		{
			$sender = $this->params->get( 'friendid');
			$subject = $this->params->get( 'subject' , '' );
			$message = $this->params->get( 'message' , '' );
			$notify = new CNotificationLibrary();
			$notify->add( 'system.welcome' , $sender , $receiver , $subject , $message );
		}
	}*/
	
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