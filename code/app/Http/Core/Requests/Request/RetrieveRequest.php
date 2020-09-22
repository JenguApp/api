<?php
declare(strict_types=1);

namespace App\Http\Core\Requests\Request;

use App\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use App\Http\Core\Requests\Traits\HasNoRules;
use App\Models\Request\Request;
use App\Policies\Request\RequestPolicy;

/**
 * Class ViewRequest
 * @package App\Http\Core\Requests\Request
 */
class RetrieveRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     *
     * @return string
     */
    protected function getPolicyAction(): string
    {
        return RequestPolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     *
     * @return string
     */
    protected function getPolicyModel(): string
    {
        return Request::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     *
     * @return array
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('request'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function allowedExpands(): array
    {
        return [
            'completedBy',
            'requestedBy',
            'assets',
            'requestedItems',
            'requestedItems.asset',
            'requestedItems.parentRequestedItem',
        ];
    }
}
