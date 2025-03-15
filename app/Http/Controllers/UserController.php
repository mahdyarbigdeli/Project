<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Repositories\User\UserRepository;
use App\Http\Resources\UserResource;
use App\Mail\UserMail;
use App\Models\Applicant;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ApiResponse;

    protected $userRepository;
    function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data =  $this->userRepository->all();

        return $this->successResponse(UserResource::collection($data));
    }


    public function show($jobId, $userId) {}

    public function forgotPassword(Request $request)
    {
        $panelUrl = 'http://tamasha-tv.com:25461/usernopass.php';
        $request->validate(['email' => 'required|email']);
        $email = $request->email;
        try {
            $response = Http::asForm()->post($panelUrl . "?username=" . $email);
            if ($response->failed()) {
                return response()->json(['error' => 'API request failed. Could not connect to the API.'], 500);
            }
            $apiResult = $response->json();
            $apiResult = json_decode($apiResult);
            if (isset($apiResult->error)) {
                return response()->json(['error' => 'API Error: ' . $apiResult->error], 400);
            }
            $res = $this->sendEmail($apiResult->password, $email);
            if ($res && $res['status'])
                return response()->json(['message' => $res['message']]);
            else
                return response()->json(['message' => 'Email failed']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->userRepository->create($request->all());
            return $this->successResponse(UserResource::make($data));
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation Error: ' . $e->getMessage(), 422);
        } catch (AuthenticationException $e) {
            return $this->errorResponse('Authentication Error: ' . $e->getMessage(), 401);
        } catch (Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = $this->userRepository->find($id);
            $data = $user->update($request->all());
            return $this->successResponse($data);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation Error: ' . $e->getMessage(), 422);
        } catch (AuthenticationException $e) {
            return $this->errorResponse('Authentication Error: ' . $e->getMessage(), 401);
        } catch (Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = $this->userRepository->find($id);
            $data = $user->delete();
            return $this->successResponse($data);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation Error: ' . $e->getMessage(), 422);
        } catch (AuthenticationException $e) {
            return $this->errorResponse('Authentication Error: ' . $e->getMessage(), 401);
        } catch (Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500);
        }
    }

    public function auth(Request $request)
    {
        // Define the API URL
        $panelUrl = "http://tamasha-tv.com:25461/player_api.php";

        // Validate the input
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Prepare the POST data
        $postData = [
            'username' => $validated['username'],
            'password' => $validated['password'],
        ];

        try {
            // Make the POST request
            $response = Http::asForm()->post($panelUrl . "?username=" . $validated['username'] . "&password=" . $validated['password'], $postData);
            // Check if the response is successful
            if ($response->successful()) {
                $apiResult = $response->json();
                if (!empty($apiResult['user_info'])) {
                    return response()->json(['data' => $apiResult]);
                } else {
                    return response()->json(['error' => 'API response indicates failure.'], 400);
                }
            }

            return response()->json(['error' => 'Failed to fetch data from the API.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function getUserInfo(Request $request)
    {
        // Define the API URL
        $panelUrl = "http://tamasha-tv.com:25461/userinfo.php";

        // Validate the input
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Prepare the POST data
        $postData = [
            'username' => $validated['username'],
            'password' => $validated['password'],
        ];

        try {
            // Make the POST request
            $response = Http::asForm()->post($panelUrl . "?username=" . $validated['username'] . "&password=" . $validated['password'], $postData);
            // Check if the response is successful
            if ($response->successful()) {
                $apiResult = $response->json();
                if (!empty($apiResult['status']) && isset($apiResult['data'])) {
                    $userInfo = $apiResult['data'];
                    return response()->json([
                        'data' => [
                            'user_id' => $userInfo['id'] ?? null,
                            'username' => $userInfo['username'] ?? null,
                            'password' => $userInfo['password'] ?? null,
                            'expire_date' => empty($userInfo['expire_date'])
                                ? 'Unlimited'
                                : $userInfo['expire_date'],
                            'max_connections' => $userInfo['max_connections'] ?? 0
                        ]
                    ]);
                } else {
                    return response()->json(['error' => $apiResult['message']], 400);
                }
            }

            return response()->json(['error' => 'Failed to fetch data from the API.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request)
    {
        // Define the API panel URL
        $panelUrl = 'http://tamasha-tv.com:25461?edituser.php';

        // Validate input from the request
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'period' => 'required|string',
        ]);

        $username = $validated['username'];
        $password = $validated['password'];
        $period = $validated['period'];

        // Configuration settings
        $maxConnections = 1;
        $userUpdateData = [
            'max_connections' => $maxConnections,
            'is_restreamer' => 0,
        ];

        try {
            // Step 1: Fetch current user data from the API
            // $userResponse = Http::get("$panelUrl?action=user&sub=info", [
            //     'username' => $username,
            //     'password' => $password,
            // ]);
            $postResponse = Http::asForm()->post($panelUrl . "?username=" . $username . "&password=" . $password . "&period=" . $period);

            // if ($userResponse->failed()) {
            //     return response()->json(['error' => 'Failed to fetch current user data.'], 500);
            // }

            // Step 2: Calculate the new expiration date
            // if ($currentExpDate < $currentTime) {
            //     $newExpDate = strtotime($period, $currentTime);
            // } else {
            //     $newExpDate = strtotime($period, $currentExpDate);
            // }

            // if ($newExpDate === false) {
            //     return response()->json(['error' => 'Invalid period format.'], 400);
            // }

            // Add the new expiration date to the user update data
            // $userUpdateData['exp_date'] = $newExpDate;

            // Step 3: Prepare and send the POST request to update the user
            // $postResponse = Http::asForm()->post("$panelUrl?action=user&sub=edit", [
            //     'username' => $username,
            //     'password' => $password,
            //     'user_data' => json_encode($userUpdateData),
            // ]);

            if ($postResponse->failed()) {
                return response()->json(['error' => 'API request failed.'], 500);
            }

            $apiResult = $postResponse->json();

            if (isset($apiResult['error'])) {
                return response()->json(['error' => 'API Error: ' . $apiResult['error']], 400);
            }

            // Success response
            return response()->json(['message' => 'User updated successfully.']);
        } catch (\Exception $e) {
            // Handle and log the exception
            // \Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function createUser(Request $request)
    {
        // Define the API panel URL
        $panelUrl = 'http://tamasha-tv.com:25461/createuser.php';
        // $endpoint = "createuser.php?action=user&sub=create";

        // $headers = [
        //     'Content-Type' => 'application/json',
        // ];

        $randomNumber = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);

        // Validate and sanitize input from the request
        $validated = $request->validate([
            'username' => 'required|string',
            // 'password' => 'required|string',
            // 'period' => 'required|string',
        ]);

        $username = $validated['username'];
        $password = '5' . $randomNumber; //$validated['password'];
        $period = '1day'; //$validated['period'];

        // Convert expire_period to a timestamp
        $expireDate = strtotime($period);
        if ($expireDate === false) {
            return response()->json(['error' => 'Invalid period format.'], 400);
        }

        // Configuration settings
        $maxConnections = 1;
        $enabled = 1;
        $memberId = 1;
        $adminEnabled = 1;
        $bouquetIds = [1, 2, 4, 5, 7, 8];

        // Prepare POST data
        $postData = [
            // 'user_data' => [
            'username' => $username,
            'password' => $password,
            'max_connections' => $maxConnections,
            'admin_enabled' => $adminEnabled,
            'enabled' => $enabled,
            'member_id' => $memberId,
            'exp_date' => $expireDate,
            'bouquet' => json_encode($bouquetIds),
            'period' => '1day', //$period
            // ],
        ];

        try {
            // Make the API request
            // $response = Http::asForm()->post("$panelUrl?action=user&sub=create", $postData);
            // $response = Http::withHeaders($headers)->post($panelUrl . $endpoint, $postData);
            $response = Http::asForm()->post($panelUrl . "?username=" . $validated['username'] . "&password=" . $password . "&period=" . $period, $postData);

            // Check if the request failed
            if ($response->failed()) {
                return response()->json(['error' => 'API request failed. Could not connect to the API.'], 500);
            }

            // Decode the JSON response
            $apiResult = $response->json();

            // Check if the API response contains an error
            if (isset($apiResult['error'])) {
                return response()->json(['error' => 'API Error: ' . $apiResult['error']], 400);
            }
            // Success: Return a success response
            //send emial
            $res = $this->sendEmail($password, $username);
            if ($res && $res['status'])
                return response()->json(['message' => $apiResult['message'], 'data' =>  $apiResult['data'], 'status' => $apiResult['success']]);
            else
                return response()->json(['message' => 'Email failed', 'data' =>  $apiResult['data'], 'status' => $apiResult['success']]);
        } catch (\Exception $e) {
            // Log and return the exception message
            // \Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function sendEmail($password, $username)
    {
        try {
            $mailData = [
                'title' => 'Password Information',
                'subject' => 'Password Information',
                'body' => 'Welcome . your password for ' . $username . ' is: ' . $password
            ];
            Mail::send('emails.userMail', ['mailData' => $mailData], function ($mail) use ($username) {
                $mail->from(env('MAIL_USERNAME'), env('MAIL_FROM_NAME'))
                    ->to($username)
                    ->subject('Password Information');
            });
            return ([
                'status' => true,
                'message' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
