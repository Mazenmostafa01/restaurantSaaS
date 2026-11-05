<?php

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\OrderParseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerOrderParseRequest;
use App\Http\Resources\ParsedOrderResource;
use App\Services\NaturalLanguageOrderParserService;
use Illuminate\Http\JsonResponse;

class CustomerOrderParseController extends Controller
{
    public function __construct(
        private readonly NaturalLanguageOrderParserService $parserService,
    ) {}

    public function __invoke(CustomerOrderParseRequest $request): ParsedOrderResource|JsonResponse
    {
        try {
            $parsedOrder = $this->parserService->parse(
                OrderParseData::fromCustomerRequest($request)
            );

            return new ParsedOrderResource($parsedOrder);
        } catch (\Exception $e) {
            info('a', [$e->getMessage()]);

            return response()->json(['message' => 'please try again later'], 404);
        }
    }
}
