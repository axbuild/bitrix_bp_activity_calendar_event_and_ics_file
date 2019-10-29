<?php
namespace KT\Tools;

class Ics {
	
	const DT_FORMAT = 'Ymd\THis\Z';
	protected $properties = [];
	private $available_properties = [
		'description',
		'dtend',
		'dtstart',
		'location',
		'summary',
		'url'
	];
	
	public function __construct($props) 
	{
		$this->set($props);
	}
	
	public function set($key, $val = false)
	{
		if (is_array($key)) 
		{
			foreach ($key as $k => $v)
			{
				$this->set($k, $v);
			}
		} 
		else
		{
			if (in_array($key, $this->available_properties)) 
			{
				$this->properties[$key] = $this->sanitizeVal($val, $key);
			}
		}
	}
	
	public function __toString()
	{
		return implode("\r\n", $this->buildProps());
	}
	
	private function buildProps()
	{
		$ics_props = [ 
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
			'CALSCALE:GREGORIAN',
			'BEGIN:VEVENT'
		];
		
		$props = [];
		foreach($this->properties as $k => $v)
		{
			$props[strtoupper($k . ($k === 'url' ? ';VALUE=URI' : ''))] = $v;
		}
		$props['DTSTAMP'] = $this->formatTimestamp('now');
		$props['UID'] = uniqid();
		foreach ($props as $k => $v) 
		{
			$ics_props[] = "$k:$v";
		}
		
		$ics_props[] = 'END:VEVENT';
		$ics_props[] = 'END:VCALENDAR';
		return $ics_props;
	}
	
	private function sanitizeVal($val, $key = false) 
	{
		switch($key)
		{
			case 'dtend':
			case 'dtstamp':
			case 'dtstart': $val = $this->formatTimestamp($val);
			break; 
			default:
			$val = $this->escapeString($val);
		}
		return $val;
	}
	
	private function formatTimestamp($timestamp)
	{
		return (new \DateTime($timestamp))
			->format(self::DT_FORMAT);
	}
	
	private function escapeString($str)
	{
		return preg_replace('/([\,;])/','\\\$1', $str);
	}
}