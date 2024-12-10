<?php

namespace tobimori\DreamForm\Actions;

use Kirby\Toolkit\V;

class LoopsTransactionalAction extends LoopsBaseAction
{
	/**
	 * Returns the Blocks fieldset blueprint for the actions' settings
	 */
	public static function blueprint(): array
	{
		return [
			'name' => t('dreamform.actions.loops.transactional.name'),
			'preview' => 'fields',
			'wysiwyg' => true,
			'icon' => 'loops',
			'tabs' => [
				'settings' => [
					'label' => t('dreamform.settings'),
					'fields' => [
						'transactionalId' => [
							'label' => t('dreamform.actions.loops.transactional.transactionalId.label'),
							'type' => 'text',
							'required' => true,
							'width' => '1/3'
						],
						'email' => [
							'label' => t('email'),
							'type' => 'dreamform-dynamic-field',
							'required' => true,
							'width' => '1/3'
						],
						'addToAudience'	=> [
							'label' => t('dreamform.actions.loops.transactional.addToAudience.label'),
                            'help' => t('dreamform.actions.loops.transactional.addToAudience.help'),
							'type' => 'toggle',
							'width' => '1/3',
							'default' => false
						],
						'dataVariables' => [
							'label' => t('dreamform.actions.loops.transactional.dataVariables.label'),
							'type' => 'structure',
							'fields' => [
								'key' => [
									'label' => t('dreamform.actions.loops.transactional.dataVariables.key.label'),
									'type' => 'text',
									'required' => true
								],
								'value' => [
									'label' => t('dreamform.actions.loops.transactional.dataVariables.value.label'),
									'type' => 'dreamform-dynamic-field',
									'required' => true
								]
							]
						]
					]
				]
			]
		];
	}

	/**
	 * Add the user to your contacts in Loops
	 */
	public function run(): void
	{
		$transactionalId = $this->block()->transactionalId()->value();
		$email = $this->submission()->valueForDynamicField($this->block()->email())?->value();

		if (!V::email($email)) {
			$this->cancel('dreamform.submission.error.email', public: true);
		}

		$dataVariables = [];
		foreach ($this->block()->dataVariables()->toStructure() as $pair) {
			$key = $pair->key()->value();
			$value = $this->submission()->valueForDynamicField($pair->value())?->value();
			$dataVariables[$key] = $value;
		}

		$request = static::request('POST', "/transactional", [
			'email' => $email,
			'transactionalId' => $transactionalId,
			'dataVariables' => $dataVariables
		]);

		if ($request->code() > 299) {
			$this->cancel($request->json()['error']['reason'] ?? "dreamform.submission.error.email", public: false);
		}

		$this->log(
			[
				'template' => [
					'email' => $email,
					'transactionalId' => $transactionalId,
				]
			],
			type: 'none',
			icon: 'loops',
			title: 'dreamform.actions.loops.transactional.log.success'
		);
	}
}
