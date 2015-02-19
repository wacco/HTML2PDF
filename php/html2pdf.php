<?php

namespace Html2Pdf;

class Exception extends \Exception {}

class Api {

	private $apikey, $version = '1.0', $filename = 'MyPDF.pdf', $tempfile = null, $options = array();
	private $timeout = 20;
	private $service = 'http://html2pdf.tools/api';
	
	public function __construct($apikey) {
		$this->apikey = $apikey;
	}
	
	public function setTimeOut($seconds) {
		$this->timeout = intVal($seconds);
		return $this;
	}
	
	public function setPageSize($ps) {
		$allowed = array("A0","A1","A2","A3","A4","A5","A6","A7","A8","A9","B0","B1","B2","B3","B4","B5","B6","B7","B8","B9","B10","C5E","Comm10E","DLE","Executive","Folio","Ledger","Legal","Letter","Tabloid");
		if (array_search($ps, $allowed)) {
			$this->options['page-size'] = $ps;
			unset($this->options['page-width'], $this->options['page-height']);
		}
		return $this;
	}

	public function setPageDimensions($width, $height, $unit) {
		$allowed = array("in","mm","pt");
		$unit = strtolower($unit);
		if (array_search($unit, $allowed) && is_numeric($width) && is_numeric($height)) {
			$this->options['page-width'] = $width . $unit;
			$this->options['page-height'] = $height . $unit;
			unset($this->options['page-size']);
		}
		return $this;
	}

	public function setPageOrientation($po) {
		$po = strtolower($po);
		if (($po == 'portrait') || ($po == 'landscape')) {
			$this->options['orientation'] = $po;
		}
		return $this;
	}
	
	public function setMargins($top, $bottom, $left, $right, $unit) {
		$allowed = array("in","mm","pt");
		$unit = strtolower($unit);
		if (array_search($unit, $allowed) && is_numeric($top) && is_numeric($bottom) && is_numeric($left) && is_numeric($right)) {
			$this->options['margin-top'] = $top . $unit;
			$this->options['margin-bottom'] = $bottom . $unit;
			$this->options['margin-left'] = $left . $unit;
			$this->options['margin-right'] = $right . $unit;
		}
		return $this;
	}

	public function setGrayscale($switch) {
		if ($switch) {
			$this->options['grayscale'] = null;
		} else {
			unset($this->options['grayscale']);
		}
		return $this;
	}
	
	public function setJavaScript($switch) {
		if ($switch) {
			$this->options['enable-javascript'] = null;
			unset($this->options['disable-javascript']);
		} else {
			$this->options['disable-javascript'] = null;
			unset($this->options['enable-javascript']);
		}
		return $this;
	}

	public function setJavaScriptDelay($msec) {
		$ms = intVal($msec);
		if ($ms > 0) {
			$this->options['javascript-delay'] = $ms;
			$this->options['enable-javascript'] = null;
			unset($this->options['disable-javascript']);
		}
		return $this;
	}

	public function setImages($switch) {
		if (!$switch) {
			$this->options['no-images'] = null;
		} else {
			unset($this->options['no-images']);
		}
		return $this;
	}

	public function setBackground($switch) {
		if (!$switch) {
			$this->options['no-background'] = null;
		} else {
			unset($this->options['no-background']);
		}
		return $this;
	}

	public function setPrintMediaType($switch) {
		if ($switch) {
			$this->options['print-media-type'] = null;
			unset($this->options['no-print-media-type']);
		} else {
			$this->options['no-print-media-type'] = null;
			unset($this->options['print-media-type']);
		}
		return $this;
	}
	
	public function setHeader($headerhtml) {
		$html = trim($headerhtml);
		if (strlen($html)) {
			$this->options['header-html'] = $html;
		}
		return $this;
	}

	public function setFooter($footerhtml) {
		$html = trim($footerhtml);
		if (strlen($html)) {
			$this->options['footer-html'] = $html;
		}
		return $this;
	}
	
	public function setPageOffset($offset) {
		$this->options['page-offset'] = intVal($offset);
		return $this;
	}

	public function setHttpAuthentication($username, $password) {
		if (strlen(trim($username)) && strlen(trim($password))) {
			$this->options['username'] = trim($username);
			$this->options['password'] = trim($password);
		}
		return $this;
	}

	public function createFromURL($url) {
		$u = trim($url);
		if (substr($u, 0, 6) != 'http://') {
			$u = 'http://' . $u;
		}
		if ($this->checkURL($u)) {
			$this->filename = str_replace('.', '_', $u) . '.pdf';
			$this->callServer($u);
		} else {
			throw new Exception ("URL is invalid", 900); 
		}
		return $this;
	}
	
	public function createFromHTML($html) {
		if (strlen(trim($html))) {
			$this->callServer($html);
		} else {
			throw new Exception ("HTML string can not be empty", 901); 
		}	
		return $this;
	}

	public function createFromFile($filename) {
		$filename = trim($filename);
		if (!file_exists($filename)) {
			throw new Exception ("Filename does not exist", 902);
		}
		if (!filesize($filename)) {
			throw new Exception ("File can not be empty", 903);
		}
		return $this->createFromHTML(file_get_contents($filename));
	}

	public function save($filename) {
		if ($this->tempfile === null) {
			throw new Exception("Not possible to save PDF file", 920);
		}
		file_put_contents($filename, $this->tempfile);
		return $this;
	}

	public function display($filename = null) {
		if ($this->tempfile === null) {
			throw new Exception("Not possible to display PDF file", 921);
		} else {
			if ($filename === null) {
				$filename = $this->filename;
			}
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Type: application/pdf');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($this->tempfile));
			header("Content-Disposition: attachment; filename=\"$filename\"");
			header('Content-Description: File Transfer');
			echo $this->tempfile;
			exit;
		}
	}
	
	private function checkURL($u) {
		if ((strpos($u,'http://') === 0) or (strpos($u,'https://') === 0))
			return filter_var($u, FILTER_VALIDATE_URL);
		else
			return filter_var('http://' . $u, FILTER_VALIDATE_URL);
	}

	private function callServer($data, $parameters = array()) {
		if ( !function_exists("curl_init") || !function_exists("curl_setopt") || !function_exists("curl_exec") || !function_exists("curl_close") ) {
			throw new Exception("cURL must be installed!", 930);
			return false;
		} else {
			$fields = array(
					'apikey' => $this->apikey,
					'version'=> $this->version,
					'data' => $data,
					'params' => json_encode($parameters),
					'options' => json_encode($this->options),
			);

			//print_r($fields);

			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $this->service);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_HEADER, FALSE); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 

			$this->tempfile = curl_exec($ch);
			//print_r($this->tempfile);

			$errortext = curl_error($ch);
			$errorcode = curl_errno($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpcode != 200 || $errorcode) {
				$this->tempfile = null;
			}
			
			if ($errorcode != 0) {
				throw new Exception('Curl error: ' . $errorcode . ' ' . $errortext, 800+$errorcode);    
			} elseif ($httpcode == 200) { 
				return true;
			} else {
				throw new Exception('HTTP error: ' . $httpcode, $httpcode);
        	}
        }
	}
}