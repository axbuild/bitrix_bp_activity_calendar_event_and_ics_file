<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_NAME") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'calendar_event_name', $arCurrentValues['calendar_event_name'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_DESCRIPTION") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'calendar_event_description', $arCurrentValues['calendar_event_description'], ['rows'=>'5'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_ID") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("int", 'calendar_event_id', $arCurrentValues['calendar_event_id'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_DATE_FROM") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("datetime", 'calendar_event_date_from', $arCurrentValues['calendar_event_date_from'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_DATE_TO") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("datetime", 'calendar_event_date_to', $arCurrentValues['calendar_event_date_to'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_REMIND_INTERVAL") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("int", 'calendar_event_remind_interval', $arCurrentValues['calendar_event_remind_interval'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_CREATE_ICS_FILE") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("bool", 'calendar_event_create_ics_file', $arCurrentValues['calendar_event_create_ics_file'], ['rows'=>'1'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCRU_PD_CALENDAR_EVENT_PARTICIPANTS") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("user", 'calendar_event_participants', $arCurrentValues['calendar_event_participants'], ['rows'=>'1'])?>
	</td>
</tr>
