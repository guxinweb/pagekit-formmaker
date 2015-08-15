<?php

namespace Pagekit\Formmaker\Submission;

use Pagekit\Application as App;
use Pagekit\Util\ArrObject;
use Pagekit\Formmaker\Model\Form;
use Pagekit\Formmaker\Model\Submission;

class CsvHelper {

	const CSV_SEPARATOR = ';';

	const CSV_ENCLOSER = '';

	const CSV_VALUESEP = ',';

	const CSV_NEWLINE = "\n";

	/**
	 * @var Submission[]
	 */
	protected $submissions;

	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @var ArrObject
	 */
	protected $options;

	/**
	 * CsvHelper constructor.
	 * @param Submission[] $submissions
	 * @param  Form        $form
	 * @param  ArrObject   $options
	 */
	public function __construct ($submissions, $form, ArrObject $options) {
		$this->submissions = $submissions;
		$this->form = $form;
		$this->form->fields = $form->getFields();
		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function toCsv () {
		$output = [$this->getHeaders()];
		foreach ($this->submissions as $submission) {
			$submission->form = $this->form;
			if (!$submission->fieldsubmissions) {
				$submission->getFieldsubmissions();
			}
			$arrData = $submission->toArray();
			$data = [];
			//todo error checking
			foreach ($this->options->get('datafields', []) as $datafield) {
				$data[] = $arrData[$datafield];
			}
			foreach ($this->options->get('field_ids', []) as $field_id) {
				$slug = $this->form->fields[$field_id]->slug;
				$data[] = implode(self::CSV_VALUESEP, isset($arrData['fieldsubmissions'][$slug]) ? $arrData['fieldsubmissions'][$slug]['value'] : []);
			}

			$output[] = $this->csvString($data);
		}

		return implode(self::CSV_NEWLINE, $output);
	}

	/**
	 * @return string
	 */
	protected function getHeaders () {
		$headers = [];
		foreach ($this->options->get('datafields', []) as $datafield) {
			$headers[] = __($datafield);
		}
		//todo error checking
		foreach ($this->options->get('field_ids', []) as $field_id) {
			$headers[] = __($this->form->fields[$field_id]->label);
		}
		return $this->csvString($headers);
	}

	/**
	 * @return string
	 */
	protected function csvString ($data) {
		return self::CSV_ENCLOSER . implode(self::CSV_ENCLOSER . self::CSV_SEPARATOR . self::CSV_ENCLOSER, $data) . self::CSV_ENCLOSER;
	}

}