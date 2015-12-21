<?php

namespace Bixie\Formmaker;

use Pagekit\Application as App;
use Bixie\Formmaker\Plugin\FormmakerPlugin;
use Pagekit\Module\Module;
use Bixie\Formmaker\Model\Form;
use Bixie\Formmaker\Type\TypeBase;

class FormmakerModule extends Module {
	/**
	 * @var \Bixie\Framework\FrameworkModule
	 */
	protected $framework;
	/**
	 * @var array
	 */
	protected $types;

	/**
	 * {@inheritdoc}
	 */
	public function main (App $app) {
		if (!in_array('bixie/framework', App::system()->config('extensions'))) {
			throw new \RuntimeException('Bixie Framework required for Formmaker');
		}

		$app->on('boot', function () {
			$this->framework = App::module('bixie/framework');
		});

		$app->subscribe(
			new FormmakerPlugin()
		);

//		$app['field'] = function ($app) {
//			if ($id = $app['request']->attributes->get('_field') and $field = Form::find($id)) {
//				return $field;
//			}
//
//			return new Form;
//		};
	}

	/**
	 * @param  string $type
	 * @return TypeBase
	 */
	public function getFieldType ($type) {
		$types = $this->getFieldTypes();

		return isset($types[$type]) ? $types[$type] : null;
	}

	/**
	 * @return array
	 */
	public function getFieldTypes () {
		if (!$this->types) {
			$this->types = $this->framework->getFieldTypes();
		}

		return $this->types;
	}

	public function renderForm (App $app, $form_id, $options = [], $view = null) {

		$user = $app->user();
		/** @var Form $form */
		if (!$form = Form::where(['id = ?'], [$form_id])->where(function ($query) use ($user) {
			if (!$user->isAdministrator()) $query->where('status = 1');
		})->related('fields')->first()
		) {
			throw new App\Exception('Form not found', 404) ;
		}
		foreach ($options as $key => $value) {
			$form->set($key, $value);
		}

		$form->prepareView($app, $this);
		$formmaker = $this;
		$app->on('view.data', function ($event, $data) use ($form, $formmaker) {
			$data->add('$formmaker', [
				'config' => $this->publicConfig(),
				'formitem' => $form,
				'fields' => array_values($form->getFields())
			]);
		});

		return $app->view($view ?: 'bixie/formmaker/form.php');
	}

	/**
	 * public accessable config
	 * @return array
	 */
	public function publicConfig () {
		$config = static::config();
		unset($config['recaptha_secret_key']);
		return $config;
	}
}
