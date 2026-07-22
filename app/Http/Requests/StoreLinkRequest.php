<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidUrlException;
use App\Services\UrlNormalizerService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and authorizes the request to create a new shortened link.
 *
 * Validation rules:
 *   - `original_url` — required, max 2048 characters, must pass UrlNormalizerService validation.
 *   - `title`        — optional, max 255 characters.
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'original_url' => ['required', 'string', 'max:2048', $this->urlValidationRule()],
            'title'        => ['nullable', 'string', 'max:255'],
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
            'title'        => 'title',
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
            'original_url.max'      => 'The URL must not exceed 2048 characters.',
        ];
    }

    /**
     * Build a closure-based validation rule that delegates to UrlNormalizerService.
     *
     * Any InvalidUrlException thrown by the service is caught and converted to
     * a user-friendly validation failure message.
     *
     * @return \Closure
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
     *
     * @param  string $url
     * @return string
     */
    private function prepareUrl(string $url): string
    {
        $url = trim($url);
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        return $url;
    }
}
