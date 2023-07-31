<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace Modules\WebForms\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Fields\Field;
use Modules\Core\Fields\FieldsCollection;
use Modules\Core\Resource\Http\CreateResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\WebForms\Models\WebForm;

class WebFormRequest extends CreateResourceRequest
{
    /**
     * Original input for the request before any modifications.
     */
    protected array $originalInput = [];

    /**
     * The web form instance for the current request.
     */
    protected ?WebForm $webForm = null;

    /**
     * Request state (validating|creating)
     */
    public string $state = 'validating';

    /**
     * Get the web form for the request.
     */
    public function webForm(): WebForm
    {
        if ($this->webForm) {
            return $this->webForm;
        }

        $webForm = WebForm::findByUuid($this->uuid());

        abort_if(! Auth::check() && ! $webForm->isActive(), 404);

        return $this->webForm = $webForm;
    }

    /**
     * Get the form uuid
     */
    public function uuid(): string
    {
        return $this->route('uuid');
    }

    /**
     * Set the resource name for the current request
     */
    public function setResource(string $resourceName): static
    {
        $this->resource = $this->findResource($resourceName);

        $this->replaceInputForCurrentResource();

        return $this;
    }

    /**
     * Replace the request input for the current resource.
     */
    protected function replaceInputForCurrentResource(): void
    {
        // When changing resource, the actual input shoud be replaced from the actual resource
        // available fields/files to avoid any conflicts when saving the records
        // e.q. a company may have name, as well deal may have name
        // when using in FormSubmissionService ->replace method, there may be conflicts

        /** @var array */
        $input = collect($this->webForm()->fileSections())->reduce(function (array $input, array $section) { // merge with initial
            $input[$section['requestAttribute']] = $this->originalInput[$section['requestAttribute']];

            return $input;
        }, $this->fields()->reduce(function (array $input, Field $field) { // initial
            $input[$field->requestAttribute] = $this->originalInput[$field->requestAttribute];

            return $input;
        }, []));

        $this->replace($input);
    }

    /**
     * Get the resource for the request.
     */
    public function resource(): Resource
    {
        return $this->resource;
    }

    /**
     * Get the available resources based on the form sections with fields.
     */
    public function resources(): array
    {
        return $this->fields()->unique(function (Field $field) {
            return $field->meta()['resourceName'];
        })->map(fn (Field $field) => $field->meta()['resourceName'])->values()->all();
    }

    /**
     * Get the resource authorized fields for the request.
     */
    public function authorizedFields(): FieldsCollection
    {
        return $this->fields()->filter->authorizedToSee();
    }

    /**
     * Get all the available fields for the request.
     */
    public function allFields(): FieldsCollection
    {
        return $this->webForm()->fields();
    }

    /**
     * Get the web form fields.
     */
    public function fields(): FieldsCollection
    {
        if ($this->state === 'validating') {
            return $this->allFields();
        }

        return $this->allFields()->filter(function (Field $field) {
            return $field->meta()['resourceName'] === $this->resource->name();
        });
    }

    /**
     * Get the files sections that are required.
     */
    protected function requiredFileSections(): array
    {
        return collect($this->webForm()->fileSections())->where('isRequired', true)->all();
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), collect($this->requiredFileSections())
            ->mapWithKeys(function (array $section) {
                return [$section['requestAttribute'].'.required' => __('validation.required_file')];
            })->all());
    }

    /**
     * Get the error messages that are defined from the resource class.
     */
    public function messagesFromResource(): array
    {
        return [];
    }

    /**
     * Prepare the request for validation.
     */
    public function prepareForValidation(): void
    {
        app()->setLocale($this->webForm()->locale);

        parent::prepareForValidation();

        $this->setOriginalInput();
    }

    /**
     * Set the request original input.
     */
    public function setOriginalInput(): static
    {
        $this->originalInput = $this->all();

        return $this;
    }

    /**
     * Get the request original input.
     */
    public function getOriginalInput(): array
    {
        return $this->originalInput;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = $this->allFields()->mapWithKeys(
            fn (Field $field) => $field->getCreationRules()
        )->all();

        if ($this->privacyPolicyAcceptIsRequired()) {
            $rules['_privacy-policy'] = 'accepted';
        }

        return $this->addFileSectionValidationRules($rules);
    }

    /**
     * Add validation for the file sections.
     */
    protected function addFileSectionValidationRules(array $rules): array
    {
        foreach ($this->requiredFileSections() as $section) {
            $attribute = $section['requestAttribute'];

            $rules[$attribute] = ['required'];

            if ($section['multiple']) {
                $rules[$attribute][] = 'array';
            }

            $rules[$attribute.($section['multiple'] ? '.*' : '')][] = 'max:'.config('mediable.max_size');
            $rules[$attribute.($section['multiple'] ? '.*' : '')][] = 'mimes:'.implode(',', config('mediable.allowed_extensions'));
        }

        return $rules;
    }

    /**
     * Indicates whether the privacy policy must be accepted.
     */
    protected function privacyPolicyAcceptIsRequired(): bool
    {
        return $this->webForm()->submitSection()['privacyPolicyAcceptIsRequired'] ?? false;
    }
}
