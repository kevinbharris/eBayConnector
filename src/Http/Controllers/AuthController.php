<?php

namespace KevinBHarris\EbayConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    /**
     * Handle OAuth callback from eBay.
     * 
     * This endpoint receives the authorization code from eBay after user authorization.
     * It can be expanded to exchange the code for an access token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function callback(Request $request): JsonResponse
    {
        // Get the authorization code from the query parameters
        $code = $request->query('code');
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        // Handle error response from eBay
        if ($error) {
            return response()->json([
                'success' => false,
                'error' => $error,
                'error_description' => $errorDescription ?? 'OAuth authorization failed',
            ], 400);
        }

        // Validate that we received a code
        if (!$code) {
            return response()->json([
                'success' => false,
                'error' => 'missing_code',
                'error_description' => 'Authorization code not provided',
            ], 400);
        }

        // TODO: Exchange the authorization code for an access token
        // This is where you would call eBay's token endpoint to exchange
        // the authorization code for an access token and refresh token.
        // Example:
        // $apiClient = app(EbayApiClient::class);
        // $tokens = $apiClient->exchangeCodeForTokens($code);
        // Store $tokens in configuration or database

        return response()->json([
            'success' => true,
            'message' => 'OAuth callback received successfully',
            'code' => $code,
            'note' => 'Token exchange logic can be implemented here',
        ]);
    }

    /**
     * Handle OAuth acceptance from eBay.
     * 
     * This endpoint is called when the user successfully accepts the OAuth authorization.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function accepted(Request $request): JsonResponse
    {
        // Get any parameters from the request
        $code = $request->query('code');
        $state = $request->query('state');

        // TODO: Process the accepted authorization
        // This is where you would:
        // 1. Verify the state parameter (if using CSRF protection)
        // 2. Exchange the code for tokens
        // 3. Store the tokens
        // 4. Update user's authorization status

        return response()->json([
            'success' => true,
            'message' => 'OAuth authorization accepted',
            'code' => $code,
            'state' => $state,
            'note' => 'Authorization processing logic can be implemented here',
        ]);
    }

    /**
     * Handle OAuth rejection from eBay.
     * 
     * This endpoint is called when the user denies the OAuth authorization.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejected(Request $request): JsonResponse
    {
        // Get error information from the request
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');
        $state = $request->query('state');

        // TODO: Process the rejected authorization
        // This is where you would:
        // 1. Log the rejection
        // 2. Update user's authorization status
        // 3. Optionally redirect to a user-friendly page

        return response()->json([
            'success' => false,
            'message' => 'OAuth authorization rejected by user',
            'error' => $error ?? 'authorization_rejected',
            'error_description' => $errorDescription ?? 'User declined the authorization request',
            'state' => $state,
        ], 403);
    }
}
