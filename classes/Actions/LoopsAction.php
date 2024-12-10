<?php

namespace tobimori\DreamForm\Actions;

use Kirby\Content\Field;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\V;

class LoopsAction extends LoopsBaseAction
{
	/**
	 * Returns the Blocks fieldset blueprint for the actions' settings
	 */
	public static function blueprint(): array
	{
		return [
			'name' => t('dreamform.actions.loops.contact.name'),
			'preview' => 'fields',
			'wysiwyg' => true,
			'icon' => 'loops',
			'tabs' => [
				'settings' => [
					'label' => t('dreamform.settings'),
					'fields' => [
						'lists' => [
							'label' => t('dreamform.actions.loops.contact.lists.label'),
							'type' => 'multiselect',
							'options' => A::reduce(static::getLists(), fn($prev, $list) => A::merge($prev, [
								$list['id'] => $list['name']
							]), []),
							'help' => t('dreamform.actions.loops.contact.lists.help'),
							'width' => '2/3',
						],
						'subscribed'	=> [
							'label' => t('dreamform.actions.loops.contact.subscribed.label'),
							'help' => t('dreamform.actions.loops.contact.subscribed.help'),
							'type' => 'toggles',
							'default' => '',
							'width' => '1/3',
							'options' => [
								'' => t('dreamform.actions.loops.contact.subscribed.unset'),
								'true' => t('dreamform.actions.loops.contact.subscribed.yes'),
								'false' => t('dreamform.actions.loops.contact.subscribed.no'),
							]
						],
						'fields' => [
							'label' => t('dreamform.actions.loops.contact.fields.label'),
							'type' => 'object',
							'required' => true,
							'fields' => static::getFieldsBlueprint(),
							'default' => [null]
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
		$fields = $this->block()->fields()->toObject();
		$email = $this->submission()->valueForDynamicField($fields->email())?->value();

		if (!V::email($email)) {
			$this->cancel('dreamform.submission.error.email', public: true);
		}

		// Loops API expects the field keys with same casing
		// kirby always stores the field keys in lowercase
		$casedKeys = array_keys(static::getFieldsBlueprint());
		$values = [];
		foreach ($fields->data() as $key => $field) {
			if ($key === 'email') {
				continue;
			}

			$value = $this->submission()->valueForDynamicField(new Field($this->form(), $key, $field));

			if ($value) {
				$casedKey = A::find($casedKeys, fn($k) => Str::lower($k) === Str::lower($key));
				if (!$casedKey) continue;
				$values[$casedKey] = $value->value();
			}
		}


		// get data for merge fields from the submission
		$mailingLists = [];
		foreach ($this->block()->lists()->split() as $id) {
			$mailingLists[$id] = true;
		}

		// subscribe or update the user
		$request = static::request('PUT', "/contacts/update", array_merge([
			'email' => $email,
			'subscribed' => $this->block()->subscribed()->toBool() ?? null,
			'mailingLists' => $mailingLists
		], $values));

		if ($request->code() > 299) {
			$this->cancel($request->json()['message'] ?? "dreamform.submission.error.email");
		}

		$this->log(
			[
				'template' => [
					'email' => $email,
				]
			],
			type: 'none',
			icon: 'loops',
			title: 'dreamform.actions.loops.contact.log.success'
		);
	}
}
