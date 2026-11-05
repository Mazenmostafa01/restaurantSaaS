<?php

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\OrderParseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerOrderParseRequest;
use App\Http\Resources\ParsedOrderResource;
use App\Services\NaturalLanguageOrderParserService;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Throwable;

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
        } catch (ProviderConnectionException $e) {
            return response()->json([
                'message' => 'AI service timed out. Please try again.',
            ], 504);
        } catch (ProviderOverloadedException $e) {
            return response()->json([
                'message' => 'AI service is currently busy. Please try again later.',
            ], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not parse your order right now. Please try again later.',
            ], 500);
        }
    }
}
