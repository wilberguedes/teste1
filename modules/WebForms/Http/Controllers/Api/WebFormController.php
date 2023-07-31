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

namespace Modules\WebForms\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Core\Rules\ValidLocaleRule;
use Modules\WebForms\Http\Resources\WebFormResource;
use Modules\WebForms\Models\WebForm;

class WebFormController extends ApiController
{
    /**
     * Get all web forms.
     */
    public function index(): JsonResponse
    {
        $forms = WebForm::withCommon()->get();

        return $this->response(
            WebFormResource::collection($forms)
        );
    }

    /**
     * Display web form.
     */
    public function show(WebForm $form): JsonResponse
    {
        return $this->response(
            new WebFormResource($form->loadMissing('user'))
        );
    }

    /**
     * Store a newly created web form in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->validateForm($request);

        $form = WebForm::create($request->all());

        return $this->response(
            new WebFormResource($form->loadMissing('user')),
            201
        );
    }

    /**
     * Update the specified web form in storage.
     */
    public function update(WebForm $form, Request $request): JsonResponse
    {
        $this->validateForm($request);

        $form->fill($request->all())->save();

        return $this->response(
            new WebFormResource($form->loadMissing('user'))
        );
    }

    /**
     * Remove the specified web form from storage.
     */
    public function destroy(WebForm $form): JsonResponse
    {
        $form->delete();

        return $this->response('', 204);
    }

    /**
     * Validate the request.
     */
    protected function validateForm(Request $request): void
    {
        $request->validate([
            'locale' => ['sometimes', 'required', 'string', new ValidLocaleRule],
            'title' => ($request->isMethod('PUT') ? 'sometimes|' : '').'required|string|max:191',
            'styles.primary_color' => ($request->isMethod('PUT') ? 'sometimes|' : '').'required|max:7',
            'styles.background_color' => ($request->isMethod('PUT') ? 'sometimes|' : '').'required|max:7',
            'styles.logo' => 'sometimes|nullable|in:dark,light',
            'notifications.*' => 'sometimes|email',
            'submit_data.pipeline_id' => 'sometimes|required',
            'submit_data.stage_id' => 'sometimes|required',
            'submit_data.action' => 'sometimes|required|in:message,redirect',
            'submit_data.success_title' => 'required_if:submit_data.action,message',
            'submit_data.success_redirect_url' => 'nullable|required_if:submit_data.action,redirect|url',
            'user_id' => 'sometimes|required',
        ]);
    }
}
