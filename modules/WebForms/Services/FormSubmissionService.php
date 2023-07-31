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

namespace Modules\WebForms\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Source;
use Modules\Core\Facades\ChangeLogger;
use Modules\Core\Fields\Field;
use Modules\Core\Fields\User;
use Modules\Core\Models\Changelog;
use Modules\Users\Models\User as ModelsUser;
use Modules\WebForms\Http\Requests\WebFormRequest;
use Modules\WebForms\Mail\WebFormSubmitted;
use Modules\WebForms\Models\WebForm;
use Modules\WebForms\Submission\FormSubmission;
use Modules\WebForms\Submission\FormSubmissionLogger;
use Plank\Mediable\Exceptions\MediaUploadException;
use Plank\Mediable\Facades\MediaUploader;

class FormSubmissionService
{
    /**
     * Process the web form submission
     */
    public function process(WebFormRequest $request): void
    {
        ChangeLogger::disable();
        $webForm = $request->webForm();

        $request->state = 'creating';
        $request->setResource('contacts');
        $resource = $request->resource();

        $firstNameField = $request->fields()->find('first_name');
        $displayName = null;

        $phoneField = $request->fields()->find('phones');
        $emailField = $request->fields()->find('email');

        User::setAssigneer($webForm->creator);

        if (! $firstNameField) {
            $displayName = $emailField ?
            $request[$emailField->requestAttribute()] :
            $request[$phoneField->requestAttribute()][0]['number'] ?? 'Unknown';
        }

        $contact = $this->findDuplicateContact($request, $emailField, $phoneField);

        if ($contact) {
            // Track updated fields
            ChangeLogger::enable();
            $resource->setModel($contact);
            $resource->resourcefulHandler($request)->update($contact);
            $resource->setModel(null);
            ChangeLogger::disable();
        } else {
            $firstNameAttribute = $firstNameField ? $firstNameField->requestAttribute() : 'first_name';
            $firstName = ! $firstNameField ? $displayName : $request[$firstNameField->requestAttribute()];

            $request->merge([
                $firstNameAttribute => $firstName,
                'user_id' => $webForm->user_id,
                'source_id' => $this->getSource()->getKey(),
            ]);

            $contact = $request->resource()->resourcefulHandler($request)->store();
        }

        $this->handleResourceUploadedFiles($request, $contact);

        // Update the displayable fallback name with the actual contact display name
        // for example, if the form had first name and lastname fields, the full name will be used as fallback
        $displayName = $contact->display_name;

        // Handle the deal creation
        $deal = $this->handleDealFields($request, $displayName, $contact);

        $request->setResource('companies');

        if ($request->fields()->isNotEmpty() || $request->webForm()->getFileSectionsForResource('companies')->isNotEmpty()) {
            $company = $this->handleCompanyFields($request, $displayName, $deal, $contact);
            $this->handleResourceUploadedFiles($request, $company);
        }

        $changelog = (new FormSubmissionLogger(array_filter([
            'contacts' => $contact,
            'deals' => $deal,
            'companies' => $company ?? null,
        ]), $request))->log();

        $webForm->increment('total_submissions');

        $this->handleWebFormNotifications($webForm, $changelog);

        ChangeLogger::enable();
    }

    /**
     * Find duplicate company
     */
    protected function findDuplicateCompany(WebFormRequest $request, string $companyName, ?string $companyEmail): ?Company
    {
        $company = $request->findRecordFromUniqueCustomFields();

        if (! $company && $companyEmail) {
            $company = Company::where('email', $companyEmail)->first();
        }

        if (! $company) {
            $company = Company::where('name', $companyName)->first();
        }

        return $company;
    }

    /**
     * Find duplicate contact
     */
    protected function findDuplicateContact(WebFormRequest $request, ?Field $emailField, ?Field $phoneField): ?Contact
    {
        if ($contact = $request->findRecordFromUniqueCustomFields()) {
            return $contact;
        }

        if ($emailField && ! empty($email = $request[$emailField->requestAttribute()])) {
            $contact = Contact::where('email', $email)->first();
        }

        if (! $contact && $phoneField && ! empty($phones = $request[$phoneField->requestAttribute()])) {
            $contact = $request->resource()->finder()->matchByPhone($phones);
        }

        return $contact;
    }

    /**
     * Handle the web form deal fields
     *
     * @param  string  $fallbackName
     * @param  \Modules\Contacts\Models\Contact  $contact
     * @return \Modules\Deals\Models\Deal
     */
    protected function handleDealFields(WebFormRequest $request, $fallbackName, $contact)
    {
        $request->setResource('deals');

        $dealNameField = $request->fields()->find('name');

        $dealName = $dealNameField ?
            $dealNameField->attributeFromRequest(
                $request,
                $dealNameField->requestAttribute()
            ) :
            $fallbackName.' Deal';

        if (! empty($request->webForm()->title_prefix)) {
            $dealName = $request->webForm()->title_prefix.$dealName;
        }

        $nameAttribute = $dealNameField ? $dealNameField->requestAttribute() : 'name';

        $request->merge([
            $nameAttribute => $dealName,
            'pipeline_id' => $request->webForm()->submit_data['pipeline_id'],
            'stage_id' => $request->webForm()->submit_data['stage_id'],
            'user_id' => $request->webForm()->user_id,
            'web_form_id' => $request->webForm()->id,
        ]);

        return tap(
            $request->resource()->resourcefulHandler($request)->store(),
            function ($deal) use ($request, $contact) {
                $deal->contacts()->attach($contact);
                $this->handleResourceUploadedFiles($request, $deal);
            }
        );
    }

    /**
     * Handle the company fields
     *
     * @param  string  $fallbackName
     * @param  \Modules\Deals\Models\Deal  $deal
     * @param  \Modules\Contacts\Models\Contact  $contact
     * @return \Modules\Contacts\Models\Company|null
     */
    protected function handleCompanyFields(WebFormRequest $request, $fallbackName, $deal, $contact)
    {
        $resource = $request->resource();
        $companyNameField = $request->fields()->find('name');

        $companyName = ! $companyNameField ?
            $fallbackName.' Company' :
            $request[$companyNameField->requestAttribute()];

        if ($companyEmailField = $request->fields()->find('email')) {
            $companyEmail = $request[$companyEmailField->requestAttribute()];
        }

        if ($company = $this->findDuplicateCompany($request, $companyName, $companyEmail ?? null)) {
            $resource->setModel($company);
            $resource->resourcefulHandler($request)->update($company);
            $resource->setModel(null);

            // It can be possible the contact to be already attached to the company e.q. in case the same form
            // is submitted twice, in this case, the company will exists as well the contact
            $company->contacts()->syncWithoutDetaching($contact);
        } else {
            $nameAttribute = $companyNameField?->requestAttribute() ?? 'name';

            $request->merge([
                $nameAttribute => $companyName,
                'user_id' => $request->webForm()->user_id,
                'source_id' => $this->getSource()->getKey(),
            ]);

            $company = $resource->resourcefulHandler($request)->store();
            $company->contacts()->attach($contact);
        }

        $company->deals()->attach($deal);

        return $company;
    }

    /**
     * Handle the resource uploaded files
     *
     * @param  \Modules\WebForms\Http\Requests\WebFormRequest  $request
     * @param  \Modules\Core\Models\Model  $model
     * @return void
     */
    protected function handleResourceUploadedFiles($request, $model)
    {
        // Before this function is called, the resource must be set the resource
        // e.q. $request->setResource('companies') so the sections are properly determined

        $files = $request->webForm()->getFileSectionsForResource($request->resource()->name());

        $files->each(function ($section) use ($request, $model) {
            foreach (Arr::wrap($request[$section['requestAttribute']]) as $uploadedFile) {
                // try {
                $media = MediaUploader::fromSource($uploadedFile)
                    ->toDirectory($model->getMediaDirectory())
                    ->upload();
                // } catch (MediaUploadException $e) {
                // $exception = $this->transformMediaUploadException($e);
                /*
                            return $this->response(
                                ['message' => $exception->getMessage()],
                                $exception->getStatusCode()
                            );*/
                //  }
                $model->attachMedia($media, $model->getMediaTags());
            }
        });
    }

    /**
     * Get the web form source
     */
    protected function getSource(): ?Source
    {
        return Source::where('flag', 'web-form')->first();
    }

    /**
     * Handle the web form notification
     */
    protected function handleWebFormNotifications(WebForm $form, Changelog $changelog): void
    {
        if (count($form->notifications) === 0) {
            return;
        }

        foreach ($this->getNotificationRecipients($form) as $recipient) {
            Mail::to($recipient)->send(
                new WebFormSubmitted($form, new FormSubmission($changelog))
            );
        }
    }

    /**
     * Get the notification recipients
     */
    protected function getNotificationRecipients(WebForm $form): Collection
    {
        $users = ModelsUser::whereIn('email', $form->notifications)->get()->toBase();

        $usersEmails = $users->pluck('email')->all();

        if ($usersEmails != $form->notifications) {
            $nonUsersEmails = array_diff($form->notifications, $usersEmails);
        }

        return $users->merge($nonUsersEmails ?? []);
    }
}
