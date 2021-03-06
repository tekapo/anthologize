<?php
/**
* TeiPdf - Generates PDF from internal, hybridized TEI.
*
* This file is part of Anthologize {@link http://anthologize.org}.
*
* @author One Week | One Tool {@link http://oneweekonetool.org/people/}
*
* Last Modified: Thu Aug 05 15:06:19 CDT 2010
*
* @copyright Copyright (c) 2010 Center for History and New Media, George Mason
* University.
*
* Anthologize is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3, or (at your option) any
* later version.
*
* Anthologize is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
* for more details.
*
* You should have received a copy of the GNU General Public License
* along with Anthologize; see the file license.txt.  If not see
* {@link http://www.gnu.org/licenses/}.
*
* @package anthologize
*/

$eng = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'anthologize' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'eng.php';
$tcpdf = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'anthologize' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
$class_pdf = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'anthologize' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'class-tei.php';
$pdf_html_filter = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'anthologize' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR .  'pdf-html-filter.php';

require_once($eng);
require_once($tcpdf);
require_once($class_pdf);
require_once($pdf_html_filter);

define('TEI', 'http://www.tei-c.org/ns/1.0');
define('HTML', 'http://www.w3.org/1999/xhtml');
define('ANTH', 'http://www.anthologize.org/ns');

class TeiPdf {

	public $tei;
	public $pdf;
	public $xpath;

	function __construct($tei_master) {

		$this->tei = $tei_master;

		$paper_size = $this->tei->get_paper_size();

		$this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $paper_size, true, 'UTF-8', false);

		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$this->set_docinfo();
		$this->set_font();
		$this->set_margins();

	}

	public function write_pdf() {

		$book_title = $this->tei->get_book_title();
		$book_subtitle = $this->tei->get_book_title('sub');
		$book_author = $this->tei->get_book_author();



		// Title Page
		$this->pdf->AddPage();
		$this->set_title($book_title);
		if ($book_subtitle != '') { $this->set_sub_title($book_subtitle); }
		$this->set_title_author($book_author);



		// Copyright page
		$this->pdf->AddPage();
		$rights_html = "<div style=\"text-align: center;\"><p><em>".$book_title;
		if ($book_subtitle != ''){
			$rights_html .= ": ".$book_sub_title;
		}
		$rights_html .= "</em><br />";

		$book_availability = $this->tei->get_availability();
		$rights_html .= $book_availability."</p>";

		$rights_html .= "<p><em>Generated by <a href=\"http://www.anthologize.org/\">Anthologize</a></em></p></div>";

		$this->pdf->WriteHTML('<div>' . $rights_html . '</div>', true, 0, true, 0);

		// Main content
		$this->pdf->AddPage();
		// Create a nodeList containing all parts.
		$parts = $this->tei->get_parts();

		foreach ($parts as $part) {
			// Grab the main title for each part and render it as
			// a "chapter" title.
			$title = $this->tei->get_title($part);

			$html = "<h1>" . $title . "</h1>";

			// Create a nodeList containing all libraryItems
			$library_items = $this->tei->get_div("libraryItem", $part);

			foreach ($library_items as $item) {

				// Grab the main title for each libraryItem and render it
				// as a "sub section" title.
				$sub_title = $this->tei->get_title($item);

				$html = $html . "<h3>" . $sub_title . "</h3>";

				// All content below <html:body>
				$post_content = $this->tei->get_html($item);
				$post_conent  = filter_html($post_content);

				$html .= $post_content;

			} // foreach item
			$this->pdf->Bookmark($title);
			$this->pdf->WriteHTML($html, true, 0, true, 0);
		} // foreach part

		// add a new page for TOC
		$this->pdf->addTOCPage();

		// write the TOC title
		$this->pdf->WriteHTML("<h3>Table of Contents</h3>", true, 0, true, 0);

		// add TOC at page 3
		$this->pdf->addTOC(3);

		// // end of TOC page
		$this->pdf->endTOCPage();

		//echo get_class($html); // DEBUG
		$book_title = $this->tei->get_book_title();
		$filename = $book_title . ".pdf";
		$this->pdf->Output($filename, 'I');

	} // writePDF

	public function set_title($book_title) {

		$title_html = '<h1 style="text-align: center">' . $book_title . '</h1>';
		$this->pdf->WriteHTML($title_html, true, 0, true, 0);

	}

	public function set_sub_title($book_subtitle) {	

		$subtitle_html = '<h2 style="text-align: center">' . $book_subtitle . '</h2>';
		$this->pdf->WriteHTML($subtitle_html, true, 0, true, 0);

	}

	public function set_title_author($book_author) {

		$title_author_html = '<h3 style="text-align: center">' . $book_author . '</h3>';
		$this->pdf->WriteHTML($subtitle_html, true, 0, true, 0);

	}

	public function set_header() {

		// set default header data
		$this->pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);

	}

	public function set_footer() {

		// set header and footer fonts
		$this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

	}

	public function set_docinfo() {

		$book_author = $this->tei->get_book_author();
		$book_title = $this->tei->get_book_title();

		$this->pdf->SetCreator("Anthologize: A One Week | One Tool Production");
		$this->pdf->SetAuthor($book_author);
		$this->pdf->SetTitle($book_title);
		//$this->pdf->SetSubject('Barbecue');
		//$this->pdf->SetKeywords('Boone, barbecue, oneweek, pants');

	}

	public function set_font() {

		// set default monospaced font
		$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set default font subsetting mode
		$this->pdf->setFontSubsetting(true);

		$font_family = $this->tei->get_font_family();
		$font_size   = $this->tei->get_font_size();

		$this->pdf->SetFont($font_family, '', $font_size, '', true);

	}

	public function set_margins() {

		$this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

	}

} // TeiPdf

?>
