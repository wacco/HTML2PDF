<?php

require __DIR__ . '/html2pdf.php';

try {
	$pdf = (new Html2Pdf\Api('<api key>'))
		->setPageSize('A4')
		->setPageOrientation('landscape')
		//->setPageOrientation('portraid')
		->setMargins(10, 10, 10, 10, 'mm')
		->setGrayscale(false)
		->setJavaScript(true)
		//->setJavaScriptDelay(300)
		//->setImages(true)
		//->setBackground(true)
		//->setHeader('header <b>html</b>')
		//->setFooter('<center><b>footer</b></center>')
		->createFromURL('www.google.com')
		//->createFromFile(__DIR__ . '/test.html')
		//->display()
		->save(__DIR__ . '/test.pdf');

} catch (Html2Pdf\Exception $e) {
	echo $e->getMessage() . PHP_EOL;
	echo $e->getCode() . PHP_EOL;
}