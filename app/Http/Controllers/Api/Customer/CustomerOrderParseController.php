<?php

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\OrderParseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerOrderParseRequest;
use App\Http\Resources\ParsedOrderResource;
use App\Services\NaturalLanguageOrderParserService;

class CustomerOrderParseController extends Controller
{
    public function __construct(
        private readonly NaturalLanguageOrderParserService $parserService,
    ) {}

    public function __invoke(CustomerOrderParseRequest $request): ParsedOrderResource
    {
        $parsedOrder = $this->parserService->parse(
            OrderParseData::fromCustomerRequest($request)
        );

        return new ParsedOrderResource($parsedOrder);
    }
}
