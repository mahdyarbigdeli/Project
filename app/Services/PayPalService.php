To implement a PayPal subscription system in Laravel, here’s a step-by-step guide for setting up the database and integrating PayPal for subscriptions.
Step 1: Setup the Database
Create Subscription Table

Create a table to store user subscriptions:

php artisan make:migration create_subscriptions_table

Edit the migration file:

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Link to users table
            $table->string('paypal_subscription_id'); // PayPal Subscription ID
            $table->string('status'); // active, canceled, etc.
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}

Run the migration:

php artisan migrate

Step 2: Install PayPal SDK

Install the PayPal SDK via Composer:

composer require paypal/rest-api-sdk-php

Step 3: PayPal API Credentials

    Go to PayPal Developer Dashboard.
    Create a sandbox app and get:
        Client ID
        Secret
    Add them to your .env file:

    PAYPAL_CLIENT_ID=your-client-id
    PAYPAL_SECRET=your-secret
    PAYPAL_MODE=sandbox

Step 4: Create PayPal Service

Create a service to handle PayPal API calls.
PayPalService.php

namespace App\Services;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Plan;
use PayPal\Api\Agreement;

class PayPalService
{
    protected $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            )
        );

        $this->apiContext->setConfig([
            'mode' => config('services.paypal.mode'),
        ]);
    }

    public function createPlan()
    {
        // Create a billing plan (one-time setup)
        $plan = new Plan();
        $plan->setName('Subscription Plan')
            ->setDescription('Monthly Subscription')
            ->setType('fixed');

        // Add pricing and billing cycle details
        // [Customize this part]

        return $plan->create($this->apiContext);
    }

    public function createSubscription($planId)
    {
        // Create a billing agreement (subscription)
        $agreement = new Agreement();
        $agreement->setName('Subscription Agreement')
            ->setDescription('Agreement for monthly subscription')
            ->setStartDate(gmdate("Y-m-d\TH:i:s\Z", strtotime("+1 minute")));

        $agreement->setPlan(['id' => $planId]);
        $agreement->setPayer(['payment_method' => 'paypal']);

        return $agreement->create($this->apiContext);
    }
}
