<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Loader;
use Bitrix\Main\Config;
use Bitrix\Main\Mail;
use KT\Tools;

require_once 'tempfile.php';
require_once 'ics.php';

class CBPKtEventAdd extends CBPActivity
{
	private $author;
	private $charset = SITE_CHARSET;
	private $errors = [];

	public function __construct($name)
	{
		global $USER;
		parent::__construct($name);
		$this->arProperties = [
			"CalendarEventName"           => "",
			"CalendarEventDescription"    => "",
			"CalendarEventId"             => "",
			"CalendarEventDateFrom"       => "",
			"CalendarEventDateTo"         => "",
			"CalendarEventRemindInterval" => "",
			"CalendarEventCreateIcsFile"  => "",
			"CalendarEventParticipants"   => "",

			//return values
			"EventId" => "",
			"FileId" => ""
		];

		$this->author = $USER;
		Loader::includeModule('calendar');
	}

	public function Execute()
	{
		$attendees = CBPHelper::ExtractUsers(
			$this->CalendarEventParticipants, 
			$this
				->GetRootActivity()
				->GetDocumentId()
		);

		$this->addToCalendar($attendees);

		if(!$this->EventId) 
			$this->WriteToTrackingService(
				GetMessage("BPCAL_TRACKING_EVENT_ERROR_MESSAGE_1"), 0, CBPTrackingType::Report
		);
		
		if($this->EventId && $this->CalendarEventCreateIcsFile == 'Y')
		{
			foreach($attendees as $userId)
			{
				$this->mail($userId);
			}
		}

		if(count($this->errors) > 0)
			\CEventLog::Add(
				[
					'SEVERITY'      => 'ERROR',
					'AUDIT_TYPE_ID' => __CLASS__,
					'MODULE_ID'     => 'kt.api',
					'ITEM_ID'       => __FUNCTION__,
					'DESCRIPTION'   => json_encode($this->errors, JSON_UNESCAPED_UNICODE)
				]
			);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		if (!$arTestProperties["CalendarEventName"])
		{
			$arErrors[] = [
				"code" => "emptyText",
				"message" => GetMessage("BPCAL_EMPTY_TEXT_CALENDAR_EVENT_NAME"),
			];
		}

		if (!$arTestProperties["CalendarEventDescription"])
		{
			$arErrors[] = [
				"code" => "emptyText",
				"message" => GetMessage("BPCAL_EMPTY_TEXT_CALENDAR_EVENT_DESCRIPTION"),
			];
		}

		if (!$arTestProperties["CalendarEventDateFrom"])
		{
			$arErrors[] = [
				"code" => "emptyText",
				"message" => GetMessage("BPCAL_EMPTY_TEXT_CALENDAR_EVENT_DATE_FROM"),
			];
		}
		if (!$arTestProperties["CalendarEventDateTo"])
		{
			$arErrors[] = [
				"code" => "emptyText",
				"message" => GetMessage("BPCAL_EMPTY_TEXT_CALENDAR_EVENT_DATE_TO"),
			];
		}
		if (!$arTestProperties["CalendarEventParticipants"])
		{
			$arErrors[] = [
				"code" => "emptyText",
				"message" => GetMessage("BPCAL_EMPTY_TEXT_CALENDAR_EVENT_PARTICIPANTS"),
			];
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "",  $popupWindow = null, $siteId = '')
	{
		if (!is_array($arCurrentValues))
		{
			$arCurrentValues = [];

			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				$properties = $arCurrentActivity["Properties"];

				$arCurrentValues["calendar_event_name"           ] = $properties["CalendarEventName"];
				$arCurrentValues["calendar_event_description"    ] = $properties["CalendarEventDescription"];
				$arCurrentValues["calendar_event_id"             ] = $properties["CalendarEventId"];
				$arCurrentValues["calendar_event_date_from"      ] = $properties["CalendarEventDateFrom"];
				$arCurrentValues["calendar_event_date_to"        ] = $properties["CalendarEventDateTo"];
				$arCurrentValues["calendar_event_remind_interval"] = $properties["CalendarEventRemindInterval"];
				$arCurrentValues["calendar_event_create_ics_file"] = $properties["CalendarEventCreateIcsFile" ];
				$arCurrentValues["calendar_event_participants"   ] = CBPHelper::UsersArrayToString($properties["CalendarEventParticipants"], $arWorkflowTemplate, $documentType);
			}
		}

		return CBPRuntime::GetRuntime()
			->ExecuteResourceFile(
				__FILE__,
				"properties_dialog.php",
				[
					"arCurrentValues" => $arCurrentValues,
					"formName" => $formName,
				]
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$errors = [];
		$properties = [
			"CalendarEventName"           => htmlspecialcharsEx($arCurrentValues["calendar_event_name"]),
			"CalendarEventDescription"    => $arCurrentValues["calendar_event_description"],
			"CalendarEventId"             => (int)$arCurrentValues["calendar_event_id"],
			"CalendarEventDateFrom"       => $arCurrentValues["calendar_event_date_from"],
			"CalendarEventDateTo"         => $arCurrentValues["calendar_event_date_to"],
			"CalendarEventRemindInterval" => (int)$arCurrentValues["calendar_event_remind_interval"],
			"CalendarEventCreateIcsFile"  => $arCurrentValues["calendar_event_create_ics_file"],
			"CalendarEventParticipants"   => CBPHelper::UsersStringToArray($arCurrentValues["calendar_event_participants"], $documentType, $arErrors)
		];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if (count($errors) > 0) return false;

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	private function addToCalendar($attendees)
	{
		$params = [
			"arFields" => [
				"ID"              => $this->CalendarEventId,
				"DT_FROM_TS"      => strtotime($this->CalendarEventDateFrom),
				"DT_TO_TS"        => strtotime($this->CalendarEventDateTo),
				"NAME"            => $this->CalendarEventName,
				"DESCRIPTION"     => $this->CalendarEventDescription,
				"SECTION_ID"      => $this->CalendarEventId,
				"SKIP_TIME"       => 'N',
				"CAL_TYPE"        => "user",
				"OWNER_ID"        => $this->author->GetID(),
				"VERSION"         => 1,
				"DT_FROM"         => date('d.m.Y H:i:s', strtotime($this->CalendarEventDateFrom) ),
				"DT_TO"           => date('d.m.Y H:i:s', strtotime($this->CalendarEventDateTo) ),
				"ATTENDEES_CODES" => $this->addUserPrefixCode($attendees),
				"IS_MEETING"      => true
			],
			"userId" => $this->author->GetID(),
			"path" => "/company/personal/user/{$this->author->GetID()}/calendar/",
		];

		$this->EventId = \CCalendarEvent::Edit($params);
	}

	private function mail($userId)
	{
		$context = new Mail\Context();
		$context->setCategory(Mail\Context::CAT_EXTERNAL);
		$context->setPriority(Mail\Context::PRIORITY_LOW);

		$attachment = $this->getAttachment();
		$mail = (new \CUser($userId))->GetEmail();

		if(!$mail)  $this->errors[] = "user {$userId} hasn't email";

		if(count($this->errors) <= 0)
			$result = Mail\Mail::send(
				[
					'CHARSET'      => $this->charset,
					'CONTENT_TYPE' => "plain",
					'ATTACHMENT'   => $attachment,
					'TO'           => $mail,
					'SUBJECT'      => $this->CalendarEventName,
					'BODY'         => $this->CalendarEventDescription,
					'HEADER'       => [
						'From'          => Config\Option::get('main', 'email_from'),
						'Content-class' => "urn:content-classes:calendarmessage"
					],
					'CONTEXT' => $context,
				]
			);

		if($result) return true;

		$this->WriteToTrackingService(
				GetMessage("BPCAL_TRACKING_EVENT_ERROR_MESSAGE_2"), 0, CBPTrackingType::Report
		);

		$this->errors[] = "mail failed send to user {$userId}";

		return false;
	}

	private function getAttachment()
	{
		try
		{
			$tmpFile = new Tools\TempFile();
			$tmpFile->setContent(
				(
					new Tools\Ics(
						[
							"dtstart"     => $this->CalendarEventDateFrom,
							"dtend"       => $this->CalendarEventDateTo,
							'summary'     => $this->CalendarEventName,
							'description' => $this->CalendarEventName,
							'location'    => $location
						]
					)
				)->__toString()
			);

			$file = \CFile::MakeFileArray($tmpFile->__toString());
			
			$this->FileId = \CFile::SaveFile($file, '/mail_attach');
	
			$contentId = sprintf(
				'bxacid.%s@mailactivity.bizproc',
				hash('crc32b', $file['external_id'].$file['size'].$file['name'])
			);
	
			return 
			[
				[
					'ID'           => $contentId,
					'NAME'         => 'ical.ics',
					'PATH'         => $file['tmp_name'],
					'CONTENT_TYPE' => 'text/calendar',
				]
			];
		}
		catch(\Exception $e)
		{
			$this->errors[] = $e->getMessage();
		}

		return false;
	}

	private function addUserPrefixCode($users)
	{
		return preg_filter('/^/', 'U', $users);
	}

}
