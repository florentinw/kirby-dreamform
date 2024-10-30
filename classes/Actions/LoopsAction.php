<?php

namespace tobimori\DreamForm\Actions;

use Kirby\Data\Json;
use Kirby\Http\Remote;
use Kirby\Toolkit\A;
use Kirby\Toolkit\V;
use tobimori\DreamForm\DreamForm;

class LoopsAction extends Action
{
	/**
	 * Returns the Blocks fieldset blueprint for the actions' settings
	 */
	public static function blueprint(): array
	{
		return [
			'name' => t('dreamform.actions.loops.name'),
			'preview' => 'fields',
			'wysiwyg' => true,
			'icon' => 'loops',
			'tabs' => [
				'settings' => [
					'label' => t('dreamform.settings'),
					'fields' => [
						'emailField' => [
							'label' => t('dreamform.actions.loops.emailField.label'),
							'required' => true,
							'extends' => 'dreamform/fields/field',
							'width' => '1/3'
						],
						'lists' => [
							'label' => t('dreamform.actions.loops.lists.label'),
							'type' => 'multiselect',
							'options' => A::reduce(static::getLists(), fn($prev, $list) => A::merge($prev, [
								$list['id'] => $list['name']
							]), []),
							'required' => true,
							'help' => t('dreamform.actions.loops.lists.help'),
							'width' => '1/3',
						],
						'sourceField' => [
							'label' => t('dreamform.actions.loops.sourceField.label'),
							'type' => "text",
							'help' => t('dreamform.actions.loops.sourceField.help'),
							'width' => '1/3'
						],
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
		// check if email is valid
		$emailField = $this->block()->emailField()->value();
		$email = $this->submission()->valueForId($emailField)?->value();
		if (!$email) {
			return;
		}

		if (!V::email($email)) {
			$this->cancel('dreamform.submission.error.email', public: true);
		}

		$lists = $this->block()->lists()->toArray();
		$sourceField = $this->block()->sourceField()->value();

		// get data for merge fields from the submission
		$mailingLists = [];
		foreach ($mailingLists as $id) {
			$mailingLists[$id] = true;
		}

		// subscribe or update the user
		$request = static::request('PUT', "/contacts/update", [
			'email' => $email,
			'source' => $sourceField,
			'subscribed' => true,
			'mailingLists' => $mailingLists
		]);

		if ($request->code() > 299) {
			$this->cancel($request->json()['message'] ?? "dreamform.submission.error.email");
		}

		$this->log(
			[
				'template' => [
					'email' => $email,
					'list' => A::find(static::getLists(), fn($entry) => $entry['id'] === $lists)['name']
				]
			],
			type: 'none',
			icon: 'loops',
			title: 'dreamform.actions.loops.log.success'
		);
	}


	/**
	 * Returns an array of available lists in the Loops account
	 */
	protected static function getLists(): array
	{
		return static::cache(
			'lists',
			fn() => static::request('GET', '/lists')?->json()
		);
	}

	/**
	 * Get the API key for the Loops API
	 **/
	protected static function apiKey(): string|null
	{
		return DreamForm::option('actions.loops.apiKey');
	}


	/**
	 * Send a Loops API request
	 */
	public static function request(string $method, string $url, array $data = []): Remote
	{
		if ($method !== 'GET') {
			$params = [
				'data' => Json::encode(A::filter($data, fn($value) => $value !== null)),
				'headers' => [
					'Content-Type' => 'application/json',
				]
			];
		}

		return Remote::$method("https://app.loops.so/api/v1" . $url, A::merge(
			$params ?? [],
			[
				'headers' => [
					'Accept' => 'application/json',
					'Authorization' => 'Bearer ' . static::apiKey()
				]
			]
		));
	}


	/**
	 * Returns true if the Loops action is available
	 */
	public static function isAvailable(): bool
	{
		if (!static::apiKey()) {
			return false;
		}
		return static::cache('api-key', fn() => static::request('GET', '/api-key')?->json())["success"] === true;
	}

	/**
	 * Returns the actions' blueprint group
	 */
	public static function group(): string
	{
		return 'newsletter';
	}

	/**
	 * Returns the base log settings for the action
	 */
	protected function logSettings(): array|bool
	{
		return [
			'icon' => 'loops',
			'title' => 'dreamform.actions.loops.name'
		];
	}
}
