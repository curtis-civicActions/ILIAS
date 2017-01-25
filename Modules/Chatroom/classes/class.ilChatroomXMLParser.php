<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Xml/classes/class.ilSaxParser.php';
require_once 'Modules/Chatroom/classes/class.ilChatroom.php';

/**
 * Class ilChatroomXMLParser
 */
class ilChatroomXMLParser extends ilSaxParser
{
	/**
	 * @var ilObjChatroom
	 */
	protected $chat;

	/**
	 * @var ilChatroom
	 */
	protected $room;

	/**
	 * @var null|int
	 */
	protected $import_install_id = null;

	/**
	 * @var string
	 */
	protected $cdata = '';

	/**
	 * Constructor
	 *
	 * @param ilObjChatroom $chat
	 * @param string $a_xml_data
	 */
	public function __construct($chat, $a_xml_data)
	{
		parent::__construct();

		$this->chat = $chat;

		$this->room = ilChatroom::byObjectId($this->chat->getId());
		if(!$this->room)
		{
			$this->room = new ilChatroom();
			$this->room->setSetting('object_id', $this->chat->getId());
		}

		$this->setXMLContent('<?xml version="1.0" encoding="utf-8"?>' . $a_xml_data);
	}

	/**
	 * @param int|null $id
	 */
	public function setImportInstallId($id)
	{
		$this->import_install_id = $id;
	}

	/**
	 * @return int|null
	 */
	public function getImportInstallId()
	{
		return $this->import_install_id;
	}

	/**
	 * @inheritdoc
	 */
	public function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser, $this);
		xml_set_element_handler($a_xml_parser, 'handlerBeginTag', 'handlerEndTag');
		xml_set_character_data_handler($a_xml_parser, 'handlerCharacterData');
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_name
	 * @param $a_attribs
	 */
	public function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_name
	 */
	public function handlerEndTag($a_xml_parser, $a_name)
	{
		$this->cdata = trim($this->cdata);

		switch($a_name)
		{
			case 'Title':
				$this->chat->setTitle($this->cdata);
				break;

			case 'Description':
				$this->chat->setDescription($this->cdata);
				break;

			case 'OnlineStatus':
				$this->room->setSetting('online_status', (int)$this->cdata);
				break;

			case 'AllowAnonymous':
				$this->room->setSetting('allow_anonymous', (int)$this->cdata);
				break;

			case 'AllowCustomUsernames':
				$this->room->setSetting('allow_custom_usernames', (int)$this->cdata);
				break;

			case 'EnableHistory':
				$this->room->setSetting('enable_history', (int)$this->cdata);
				break;

			case 'RestrictHistory':
				$this->room->setSetting('restrict_history', (int)$this->cdata);
				break;

			case 'PrivateRoomsEnabled':
				$this->room->setSetting('private_rooms_enabled', (int)$this->cdata);
				break;

			case 'DisplayPastMessages':
				$this->room->setSetting('display_past_msgs', (int)$this->cdata);
				break;

			case 'AllowPrivateRooms':
				$this->room->setSetting('allow_private_rooms', (int)$this->cdata);
				break;

			case 'AutoGeneratedUsernameSchema':
				$this->room->setSetting('autogen_usernames', $this->cdata);
				break;

			case 'Chatroom':
				$this->chat->update();
				$this->room->save();
				break;
		}

		$this->cdata = '';
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_data
	 */
	public function handlerCharacterData($a_xml_parser, $a_data)
	{
		if($a_data != "\n")
		{
			$this->cdata .= preg_replace("/\t+/"," ",$a_data);
		}
	}
}