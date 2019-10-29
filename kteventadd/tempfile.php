<?php
namespace KT\Tools;

class TempFile
{
	public $filename;

	public function __construct()
	{
		$this->filename = tempnam(sys_get_temp_dir(), 'ics');

		register_shutdown_function(function () {
			@unlink($this->filename);
		});
	}

	public function __toString()
	{
		return $this->filename;
	}

	public function setContent($content)
	{
		file_put_contents($this->filename, $content);
	}
}