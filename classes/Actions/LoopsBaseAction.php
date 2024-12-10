<?php

namespace tobimori\DreamForm\Actions;

use Kirby\Data\Json;
use Kirby\Http\Remote;
use Kirby\Toolkit\A;
use tobimori\DreamForm\DreamForm;

abstract class LoopsBaseAction extends Action
{
	public static function getFieldsBlueprint(): array
	{
		$fields = [
			'email' => [
				'label' => t('email'),
				'type' => 'dreamform-dynamic-field',
				'required' => true,
			],
			'firstName' => [
				'label' => t('dreamform.common.firstName'),
				'type' => 'dreamform-dynamic-field',
			],
			'lastName' => [
				'label' => t('dreamform.common.lastName'),
				'type' => 'dreamform-dynamic-field',
			],
			'source' => [
				'label' => t('dreamform.common.source'),
				'type' => 'dreamform-dynamic-field',
			],
			'userGroup' => [
				'label' => t('dreamform.actions.loops.contact.userGroup.label'),
				'help' => t('dreamform.actions.loops.contact.userGroup.help'),
				'type' => 'dreamform-dynamic-field',
			],
			'userId' => [
				'label' => t('dreamform.actions.loops.contact.userId.label'),
				'help' => t('dreamform.actions.loops.contact.userId.help'),
				'type' => 'dreamform-dynamic-field',
			]
		];

		foreach (
			static::cache(
				'customfields',
				fn() => static::request('GET', '/contacts/customFields')->json()
			) as $field
		) {
			if ($field['type'] === 'date' || $field['type'] === 'boolean') {
				continue;
			}

			$fields[$field['key']] = [
				'label' => $field['label'],
				'limitType' => $field['type'] === 'number' ? 'number' : null,
				'type' => 'dreamform-dynamic-field',
			];
		}

		return $fields;
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

		return static::cache(
			['api-key', hash('md5', static::apiKey())],
			fn() => static::request('GET', '/api-key')?->json()
		)["success"] === true;
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
			'title' => 'dreamform.actions.loops.contact.name'
		];
	}
}
