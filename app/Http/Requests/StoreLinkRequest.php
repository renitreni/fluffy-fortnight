<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidUrlException;
use App\Rules\NotMaliciousUrl;
use App\Rules\NotReservedAlias;
use App\Services\UrlNormalizerService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and authorizes the request to create a new shortened link.
 *
 * Validation rules:
 *   - `original_url` — required, max 2048 characters, must pass UrlNormalizerService validation.
 *   - `title`        — optional, max 255 characters.
 *   - `custom_alias` — optional, max 255 characters, must be unique, alpha-dash, and not a reserved word.
 *   - `expires_at`   — optional, must be a valid future date/time.
 *   - `password`     — optional, 4–72 characters; stored as bcrypt hash via model cast.
 *
 * Any user who is authenticated and email-verified is authorized to shorten links.
 */
class StoreLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the route level via the `auth` and `verified`
     * middleware, so here we simply confirm the user is authenticated.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'original_url' => ['required', 'string', 'max:2048', $this->urlValidationRule(), new NotMaliciousUrl],
            'title' => ['nullable', 'string', 'max:255'],
            'custom_alias' => [
                'nullable',
                'string',
                'alpha_dash',
                'min:3',
                'max:255',
                'unique:links,short_code',
                new NotReservedAlias,
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'password' => ['nullable', 'string', 'min:4', 'max:72'],
            'ios_deep_link' => ['nullable', 'string', 'max:2048'],
            'android_deep_link' => ['nullable', 'string', 'max:2048'],
            'custom_domain_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('custom_domains', 'id')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id)->where('is_verified', true);
                }),
            ],
        ];
    }

    /**
     * Get custom human-readable attribute names for validator error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'original_url' => 'URL',
            'title' => 'title',
            'custom_alias' => 'custom alias',
            'expires_at' => 'expiration date',
            'password' => 'password',
            'custom_domain_id' => 'custom domain',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_url.required' => 'Please enter a URL to shorten.',
            'original_url.max' => 'The URL must not exceed 2048 characters.',
            'custom_alias.unique' => 'This custom alias is already taken. Please choose another one.',
            'custom_alias.alpha_dash' => 'The custom alias may only contain letters, numbers, dashes, and underscores.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'expires_at.date' => 'Please enter a valid expiration date.',
            'password.min' => 'The password must be at least 4 characters.',
        ];
    }

    /**
     * Build a closure-based validation rule that delegates to UrlNormalizerService.
     *
     * Any InvalidUrlException thrown by the service is caught and converted to
     * a user-friendly validation failure message.
     */
    private function urlValidationRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            try {
                /** @var UrlNormalizerService $normalizer */
                $normalizer = app(UrlNormalizerService::class);
                $normalizer->validate($this->prepareUrl((string) $value));
            } catch (InvalidUrlException $e) {
                $fail($e->getMessage());
            }
        };
    }

    /**
     * Inject `https://` scheme if the submitted value has no scheme,
     * so that validation sees the same URL that normalization would produce.
     */
    private function prepareUrl(string $url): string
    {
        $url = trim($url);
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }

        return $url;
    }
}
